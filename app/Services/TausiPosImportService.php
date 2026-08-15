<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\CollectionSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TausiPosImportService
{
    private array $errors = [];
    private array $warnings = [];
    private array $stats = [];

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    public function previewFromText(string $text): array
    {
        $posNumber = $this->extractPosNumber($text);
        $records = $this->parsePdfText($text, $posNumber);
        return $this->processPreviewRows($records);
    }

    public function importFromText(string $text): array
    {
        $posNumber = $this->extractPosNumber($text);
        $records = $this->parsePdfText($text, $posNumber);
        return $this->processImport($records);
    }

    public function preview(string $filePath, string $mimeType): array
    {
        $rows = $this->extractRows($filePath, $mimeType);
        return $this->processPreviewRows($rows);
    }

    public function import(string $filePath, string $mimeType): array
    {
        $rows = $this->extractRows($filePath, $mimeType);
        return $this->processImport($rows);
    }

    // =========================================================================
    // FILE ROUTING
    // =========================================================================

    private function extractRows(string $filePath, string $mimeType): array
    {
        if (str_contains($mimeType, 'pdf')) {
            $text = (new \Spatie\PdfToText\Pdf())
                ->setPdf($filePath)
                ->addOptions(['-layout'])
                ->text();
            $posNumber = $this->extractPosNumber($text);
            return $this->parsePdfText($text, $posNumber);
        }

        if (str_contains($mimeType, 'spreadsheet') || str_contains($mimeType, 'excel') || str_ends_with($filePath, '.xlsx') || str_ends_with($filePath, '.xls')) {
            return $this->parseTausiExcel($filePath);
        }

        if (str_contains($mimeType, 'csv') || str_ends_with($filePath, '.csv')) {
            return $this->parseTausiCsv($filePath);
        }

        return [];
    }

    private function extractPosNumber(string $text): string
    {
        if (preg_match('/POS:\s*(\d{6}-\d{4}-\d{5})/i', $text, $m)) return $m[1];
        if (preg_match('/\b(\d{6}-\d{4}-\d{5})\b/', $text, $m)) return $m[1];
        return 'UNKNOWN-POS';
    }

    // =========================================================================
    // PDF PARSER
    // =========================================================================

    private function parsePdfText(string $text, string $posNumber): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $blocks = [];
        $currentBlock = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;
            $currentBlock[] = $trimmed;

            if ($this->isDateTimeLine($trimmed)) {
                $blocks[] = $currentBlock;
                $currentBlock = [];
            }
        }

        $rows = [];
        foreach ($blocks as $block) {
            $blockText = implode("\n", $block);
            $row = $this->extractRowFromBlock($blockText, $posNumber);
            if ($row) $rows[] = $row;
        }

        return $rows;
    }

    private function isDateTimeLine(string $line): bool
    {
        $months = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';
        return (bool) preg_match('/\b(' . $months . ')\s+\d{1,2},?\s+\d{4}\s+\d{1,2}:\d{2}/i', $line);
    }

    private function extractRowFromBlock(string $block, string $posNumber): ?array
    {
        // Receipt Number (MC + digits, handles wrapped lines)
        $receipt = null;
        if (preg_match('/MC[\d\s]{15,50}/i', $block, $m)) {
            $receipt = preg_replace('/\s+/', '', $m[0]);
            if (strlen($receipt) > 33) $receipt = substr($receipt, 0, 33);
        }
        if (!$receipt) return null;

        // Amount
        $amount = 0;
        if (preg_match('/([\d,]+\.\d{2})/', $block, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
        }
        if ($amount <= 0) return null;

        // Control Number
        $control = 'UNKNOWN-CTRL';
        if (preg_match('/\b(993\d{9})\b/', $block, $m)) {
            $control = $m[1];
        }

        // Bill Reference (UUID)
        $uuid = 'import-' . Str::uuid();
        if (preg_match('/([0-9a-f]{8})[\s\-]*([0-9a-f]{4})[\s\-]*([0-9a-f]{4})[\s\-]*([0-9a-f]{4})[\s\-]*([0-9a-f]{12})/i', $block, $m)) {
            $uuid = "{$m[1]}-{$m[2]}-{$m[3]}-{$m[4]}-{$m[5]}";
        }

        // Status — captures PAID, NOT PAID, UNPAID
        $status = 'PAID';
        if (preg_match('/\b(NOT\s+PAID|UNPAID)\b/i', $block)) {
            $status = 'NOT_PAID';
        }

        // Payer Name
        $payer = $this->extractPayerName($block);

        // DateTime
        $paidAt = $this->extractDateTime($block) ?? Carbon::now();

        // Collector
        $collector = $this->extractCollectorName($block, $receipt);

        return [
            'receipt_number' => $receipt,
            'control_number' => $control,
            'pos_number'     => $posNumber,
            'bill_reference' => $uuid,
            'amount'         => $amount,
            'collector_name' => $collector,
            'payer_name'     => $payer, // may be null — handled in processImport
            'paid_at'        => $paidAt,
            'payment_method' => 'cash',
            'status'         => $status,
        ];
    }

    private function extractPayerName(string $block): ?string
    {
        $months = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';

        // After PAID/NOT PAID, before the date
        if (preg_match('/(?:PAID|NOT\s+PAID)\s+([A-Z][A-Za-z\s\.\-]{2,50}?)(?:\s+(?:' . $months . ')\s|\s*$)/i', $block, $m)) {
            $candidate = trim($m[1]);
            if (strlen($candidate) >= 2 && !$this->isHeaderWord($candidate)) {
                return $candidate;
            }
        }

        return null; // empty payer is OK — processImport generates a fallback
    }

    private function extractDateTime(string $text): ?Carbon
    {
        $months = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';
        if (preg_match('/(' . $months . ')\s+(\d{1,2}),?\s+(\d{4})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?/i', $text, $m)) {
            try {
                $month = $this->monthNameToNumber($m[1]);
                if ($month) {
                    return Carbon::create((int)$m[3], $month, (int)$m[2], (int)$m[4], (int)$m[5], isset($m[6]) ? (int)$m[6] : 0);
                }
            } catch (\Exception $e) {}
        }
        return null;
    }

    private function extractCollectorName(string $block, string $receipt): string
    {
        $receiptPos = strpos($block, 'MC');
        if ($receiptPos === false) return 'UNKNOWN COLLECTOR';
        $beforeReceipt = substr($block, 0, $receiptPos);
        if (preg_match('/([A-Z][A-Z\s\.]{3,40}[A-Z\.])/', $beforeReceipt, $m)) {
            $candidate = trim($m[1]);
            if (!$this->isHeaderWord($candidate)) return $candidate;
        }
        return 'UNKNOWN COLLECTOR';
    }

    private function monthNameToNumber(string $name): ?int
    {
        $map = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];
        return $map[strtolower(trim($name))] ?? null;
    }

    private function isHeaderWord(string $text): bool
    {
        $headers = ['NO', 'COLLECTOR', 'RECEIPT', 'AMOUNT', 'ITEM', 'NAME', 'CONTROL', 'NUMBER',
            'BILL', 'REFERENCE', 'STATUS', 'PAYER', 'TRANSACTION', 'TIME', 'TOTAL',
            'PAGE', 'GENERATED', 'POS', 'REFUSE', 'COLLECTION', 'FEE', 'UNITED', 'REPUBLIC'];
        return in_array(strtoupper(trim($text)), $headers);
    }

    // =========================================================================
    // EXCEL / CSV PARSER (Handles Tausi POS Tabular Format)
    // =========================================================================

    private function parseTausiExcel(string $filePath): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $allRows = [];

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = trim((string) $cell->getFormattedValue());
                }
                $allRows[] = $cells;
            }

            return $this->parseTausiTableRows($allRows);
        } catch (\Exception $e) {
            Log::error('Excel parsing failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to parse Excel: ' . $e->getMessage());
        }
    }

    private function parseTausiCsv(string $filePath): array
    {
        try {
            $allRows = [];
            if (($handle = fopen($filePath, 'r')) !== false) {
                while (($data = fgetcsv($handle)) !== false) {
                    $allRows[] = array_map('trim', $data);
                }
                fclose($handle);
            }
            return $this->parseTausiTableRows($allRows);
        } catch (\Exception $e) {
            Log::error('CSV parsing failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to parse CSV: ' . $e->getMessage());
        }
    }

    private function parseTausiTableRows(array $allRows): array
    {
        $posNumber = 'UNKNOWN-POS';
        $headerRowIndex = -1;
        $columnMap = [];

        // Find POS number
        foreach ($allRows as $row) {
            $rowText = implode(' ', array_filter($row));
            if (preg_match('/POS:\s*(\d{6}-\d{4}-\d{5})/i', $rowText, $m)) {
                $posNumber = $m[1];
                break;
            }
            if (preg_match('/\b(\d{6}-\d{4}-\d{5})\b/', $rowText, $m)) {
                $posNumber = $m[1];
                break;
            }
        }

        // Find header row
        foreach ($allRows as $index => $row) {
            $rowLower = array_map('strtolower', array_map('trim', array_filter($row)));
            $hasCollector = in_array('collector', $rowLower);
            $hasReceipt = in_array('receipt', $rowLower);
            $hasAmount = in_array('amount', $rowLower);

            if ($hasCollector && $hasReceipt && $hasAmount) {
                $headerRowIndex = $index;
                $columnMap = $this->buildColumnMap($row);
                break;
            }
        }

        if ($headerRowIndex === -1) return [];

        $rows = [];
        for ($i = $headerRowIndex + 1; $i < count($allRows); $i++) {
            $row = $allRows[$i];
            if (empty(array_filter($row, fn($v) => $v !== '' && $v !== null))) continue;

            $rowText = implode(' ', $row);

            // Skip TOTAL, Generated On, Page, repeated headers
            if (preg_match('/^\s*TOTAL\s*$/i', trim($row[0] ?? ''))) continue;
            if (stripos($rowText, 'Generated On') !== false) continue;
            if (stripos($rowText, 'Generated By') !== false) continue;
            if (preg_match('/Page\s+\d+\s+of\s+\d+/i', $rowText)) continue;

            $rowLower = array_map('strtolower', array_map('trim', $row));
            if (in_array('collector', $rowLower) && in_array('receipt', $rowLower)) continue;
            if (preg_match('/^[\s\-|]+$/', $rowText)) continue;

            $parsed = $this->parseDataRow($row, $columnMap, $posNumber);
            if ($parsed) $rows[] = $parsed;
        }

        return $rows;
    }

    private function buildColumnMap(array $headerRow): array
    {
        $map = [
            'no' => -1, 'collector' => -1, 'receipt' => -1, 'amount' => -1,
            'item_name' => -1, 'control_number' => -1, 'bill_reference' => -1,
            'status' => -1, 'payer_name' => -1, 'transaction_time' => -1,
        ];

        foreach ($headerRow as $index => $header) {
            $h = strtolower(trim($header));
            if ($h === '') continue;

            if ($h === 'no.' && $map['no'] === -1) $map['no'] = $index;
            elseif ($h === 'collector' && $map['collector'] === -1) $map['collector'] = $index;
            elseif ($h === 'receipt' && $map['receipt'] === -1) $map['receipt'] = $index;
            elseif ($h === 'amount' && $map['amount'] === -1) $map['amount'] = $index;
            elseif (($h === 'item name' || $h === 'item_name') && $map['item_name'] === -1) $map['item_name'] = $index;
            elseif (($h === 'control number' || $h === 'control_number') && $map['control_number'] === -1) $map['control_number'] = $index;
            elseif (($h === 'bill reference' || $h === 'bill_reference') && $map['bill_reference'] === -1) $map['bill_reference'] = $index;
            elseif ($h === 'status' && $map['status'] === -1) $map['status'] = $index;
            elseif (($h === 'payer name' || $h === 'payer_name') && $map['payer_name'] === -1) $map['payer_name'] = $index;
            elseif (($h === 'transaction time' || $h === 'transaction_time') && $map['transaction_time'] === -1) $map['transaction_time'] = $index;
        }

        return $map;
    }

    private function parseDataRow(array $row, array $columnMap, string $posNumber): ?array
    {
        $getValue = function (string $field) use ($row, $columnMap): string {
            $idx = $columnMap[$field] ?? -1;
            if ($idx === -1 || $idx >= count($row)) return '';
            return trim($row[$idx] ?? '');
        };

        // Receipt
        $receiptRaw = $getValue('receipt');
        if (empty($receiptRaw)) return null;
        $receipt = preg_replace('/\s+/', '', $receiptRaw);
        if (strlen($receipt) < 10) return null;
        if (strlen($receipt) > 33) $receipt = substr($receipt, 0, 33);

        // Amount
        $amountRaw = $getValue('amount');
        $amount = (float) str_replace([',', ' '], '', $amountRaw);
        if ($amount <= 0) return null;

        // Control Number (may be empty for NOT PAID)
        $control = $getValue('control_number');
        if (empty($control)) $control = 'PENDING-' . Str::random(8);

        // Bill Reference (may be empty)
        $billRef = $getValue('bill_reference');
        if (empty($billRef)) $billRef = 'import-' . Str::uuid();

        // Status — handles PAID, NOT PAID, UNPAID
        $statusRaw = strtoupper($getValue('status'));
        $status = 'PAID';
        if (str_contains($statusRaw, 'NOT PAID') || str_contains($statusRaw, 'NOT_PAID') || $statusRaw === 'UNPAID' || $statusRaw === 'PENDING') {
            $status = 'NOT_PAID';
        }

        // Payer Name (may be empty — handled in processImport)
        $payer = $getValue('payer_name');
        if (empty($payer)) $payer = null;

        // Collector
        $collector = $getValue('collector');
        if (empty($collector)) $collector = 'UNKNOWN COLLECTOR';

        // Transaction Time
        $timeRaw = $getValue('transaction_time');
        $paidAt = $this->parseFlexibleDateTime($timeRaw);

        return [
            'receipt_number' => $receipt,
            'control_number' => $control,
            'pos_number'     => $posNumber,
            'bill_reference' => $billRef,
            'amount'         => $amount,
            'collector_name' => $collector,
            'payer_name'     => $payer,
            'paid_at'        => $paidAt,
            'payment_method' => 'cash',
            'status'         => $status,
        ];
    }

    private function parseFlexibleDateTime(string $raw): Carbon
    {
        if (empty($raw)) return Carbon::now();

        $months = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';
        if (preg_match('/(' . $months . ')\s+(\d{1,2}),?\s+(\d{4})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?/i', $raw, $m)) {
            try {
                $month = $this->monthNameToNumber($m[1]);
                if ($month) {
                    return Carbon::create((int)$m[3], $month, (int)$m[2], (int)$m[4], (int)$m[5], isset($m[6]) ? (int)$m[6] : 0);
                }
            } catch (\Exception $e) {}
        }

        try { return Carbon::parse($raw); } catch (\Exception $e) {}
        return Carbon::now();
    }

    // =========================================================================
    // DATABASE PROCESSING — Enhanced to handle all edge cases
    // =========================================================================

    private function processImport(array $rows): array
    {
        $imported = 0;
        $skipped = 0;
        $importedIds = [];
        $this->errors = [];
        $this->warnings = [];
        $this->stats = [
            'payers_found' => 0, 'payers_created' => 0,
            'collectors_found' => 0, 'collectors_created' => 0,
            'invoices_matched' => 0, 'invoices_not_found' => 0,
        ];
        $recordCounter = 1;

        foreach ($rows as $row) {
            $originalStatus = strtoupper($row['status'] ?? 'PAID');
            $isPaid = ($originalStatus === 'PAID');
            $receiptNum = trim((string) ($row['receipt_number'] ?? ''));
            $ctrlNum = trim((string) ($row['control_number'] ?? ''));

            // ── SMART DUPLICATE CHECK (Receipt OR Control Number) ──
            $isDuplicate = false;
            $duplicateIdentifier = '';

            try {
                if (!empty($receiptNum) && Payment::where('receipt_number', $receiptNum)->exists()) {
                    $isDuplicate = true;
                    $duplicateIdentifier = "Receipt {$receiptNum}";
                } elseif (!empty($ctrlNum) && $ctrlNum !== 'UNKNOWN-CTRL' && !str_starts_with($ctrlNum, 'PENDING-') && Payment::where('control_number', $ctrlNum)->where('receipt_number', $receiptNum)->exists()) {
                    $isDuplicate = true;
                    $duplicateIdentifier = "Control {$ctrlNum} (Receipt {$receiptNum})";
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$recordCounter}: duplicate check failed – {$e->getMessage()}";
                $recordCounter++;
                continue;
            }

            if ($isDuplicate) {
                $skipped++;
                $this->warnings[] = "{$duplicateIdentifier}: Already exists, skipped.";
                $recordCounter++;
                continue;
            }

            // ── SAVE THE RECORD ──
            try {
                DB::transaction(function () use ($row, $isPaid, $originalStatus, $receiptNum, $recordCounter, &$imported, &$importedIds) {

                    // Collector
                    $staff = $this->findOrCreateCollector($row['collector_name'] ?? 'UNKNOWN COLLECTOR');
                    $staff->wasRecentlyCreated ? $this->stats['collectors_created']++ : $this->stats['collectors_found']++;

                    // ── PAYER NAME: Generate fallback if empty ──
                    $payerName = trim($row['payer_name'] ?? '');
                    if (empty($payerName)) {
                        $posNumber = $row['pos_number'] ?? 'UNKNOWN-POS';
                        try {
                            $dateStr = Carbon::parse($row['paid_at'] ?? now())->toDateString();
                        } catch (\Exception $e) {
                            $dateStr = now()->toDateString();
                        }
                        $payerName = sprintf('POS Client %s %s #%d', $posNumber, $dateStr, $recordCounter);
                    }

                    // Client
                    $client = $this->findClient($payerName);
                    if (!$client) {
                        $client = $this->createClientFromPayer($payerName);
                        $this->stats['payers_created']++;
                    } else {
                        $this->stats['payers_found']++;
                    }

                    // Session
                    $session = $this->findOrCreateSession($row, $staff);

                    // Invoice
                    $invoice = $this->matchInvoice($client, Carbon::parse($row['paid_at'] ?? now()));
                    $invoice ? $this->stats['invoices_matched']++ : $this->stats['invoices_not_found']++;

                    // Database status mapping
                    $dbStatus = $isPaid ? 'paid' : 'pending';

                    $payment = Payment::create([
                        'control_number'        => $row['control_number'],
                        'receipt_number'        => $receiptNum,
                        'pos_number'            => $row['pos_number'] ?? null,
                        'bill_reference'        => $row['bill_reference'] ?? 'import-' . Str::uuid(),
                        'invoice_id'            => $invoice?->id,
                        'client_id'             => $client->id,
                        'collection_session_id' => $session->id,
                        'staff_id'              => $staff->id,
                        'amount'                => $row['amount'],
                        'payer_name'            => $payerName,
                        'payment_method'        => $row['payment_method'] ?? 'cash',
                        'status'                => $dbStatus,
                        'paid_at'               => $row['paid_at'] ?? now(),
                        'metadata'              => json_encode([
                            'import_source'   => 'tausi_pos',
                            'original_status' => $originalStatus,
                            'pos_number'      => $row['pos_number'] ?? null,
                            'imported_at'     => now()->toDateTimeString(),
                        ]),
                    ]);
                    $importedIds[] = $payment->id;

                    // Only increment cash session for PAID
                    if ($isPaid) {
                        $session->increment('actual_amount', $row['amount']);
                    } else {
                        $this->warnings[] = "Receipt {$receiptNum}: Bank status NOT PAID – saved as pending.";
                    }

                    $imported++;
                });
            } catch (\Throwable $e) {
                $this->errors[] = "Receipt {$receiptNum}: {$e->getMessage()}";
                Log::error("Tausi import error", ['receipt' => $receiptNum, 'error' => $e->getMessage()]);
            }

            $recordCounter++;
        }

        return [
            'success'      => true,
            'imported'     => $imported,
            'skipped'      => $skipped,
            'imported_ids' => $importedIds,
            'total_amount' => collect($rows)->sum('amount'), // 🚀 ADD THIS LINE (Grand Total)
            'total_amount_paid' => collect($rows)->where('status', 'PAID')->sum('amount'),
            'total_amount_pending' => collect($rows)->where('status', 'NOT_PAID')->sum('amount'),
            'stats'        => $this->stats,
            'warnings'     => $this->warnings,
            'errors'       => $this->errors,
            'message'      => "{$imported} transactions imported.",
        ];
    }

    // =========================================================================
    // PREVIEW PROCESSING
    // =========================================================================

    private function processPreviewRows(array $rows): array
    {
        $processedRows = [];
        $duplicates = 0;
        $willImport = 0;
        $newClientsMap = [];

        foreach ($rows as $row) {
            $rcpt = trim((string) ($row['receipt_number'] ?? ''));
            $ctrl = trim((string) ($row['control_number'] ?? ''));

            $alreadyExists = false;
            if (!empty($rcpt) && Payment::where('receipt_number', $rcpt)->exists()) {
                $alreadyExists = true;
            }

            $willCreateClient = false;
            $payerName = trim($row['payer_name'] ?? '');

            if ($alreadyExists) {
                $duplicates++;
            } else {
                $willImport++;
                if (!empty($payerName)) {
                    $client = $this->findClient($payerName);
                    if (!$client) {
                        $willCreateClient = true;
                        $newClientsMap[strtolower($payerName)] = $payerName;
                    }
                }
            }

            $row['already_exists'] = $alreadyExists;
            $row['will_create_client'] = $willCreateClient;
            $row['client_name'] = $payerName ?: '—';
            $processedRows[] = $row;
        }

        return [
            'success'     => true,
            'rows'        => $processedRows,
            'data'        => $processedRows,
            'total'       => count($processedRows),
            'will_import' => $willImport,
            'duplicates'  => $duplicates,
            'new_clients' => array_values($newClientsMap),
            'summary'     => $this->buildPreviewSummary($processedRows),
        ];
    }

    private function buildPreviewSummary(array $rows): array
    {
        $amounts = collect($rows)->pluck('amount');
        return [
            'total_rows'   => count($rows),
            'total_amount' => $amounts->sum(),
            'min_amount'   => $amounts->min() ?? 0,
            'max_amount'   => $amounts->max() ?? 0,
            'avg_amount'   => round($amounts->avg() ?? 0, 0),
            'collectors'   => collect($rows)->pluck('collector_name')->unique()->values()->toArray(),
            'receipts'     => collect($rows)->pluck('receipt_number')->unique()->count(),
            'date_range'   => [
                'from' => collect($rows)->min('paid_at') ? Carbon::parse(collect($rows)->min('paid_at'))->toDateString() : null,
                'to'   => collect($rows)->max('paid_at') ? Carbon::parse(collect($rows)->max('paid_at'))->toDateString() : null,
            ],
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    private function findOrCreateSession(array $row, Staff $staff): CollectionSession
    {
        $sessionRef = $row['control_number'] ?? ($row['pos_number'] ?? 'UNKNOWN');
        $session = CollectionSession::where('session_reference', $sessionRef)->first();
        if ($session) return $session;

        return CollectionSession::create([
            'session_reference' => $sessionRef,
            'staff_id'          => $staff->id,
            'session_date'      => Carbon::parse($row['paid_at'] ?? now())->toDateString(),
            'status'            => 'submitted',
            'expected_amount'   => 0,
            'actual_amount'     => 0,
        ]);
    }

    private function matchInvoice(Client $client, Carbon $date): ?Invoice
    {
        return Invoice::where('client_id', $client->id)
            ->where('billing_month', $date->month)
            ->where('billing_year', $date->year)
            ->first();
    }

    private function findClient(?string $payerName): ?Client
    {
        if (empty(trim((string) $payerName))) return null;
        $name = trim($payerName);

        $client = Client::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($client) return $client;

        $client = Client::whereRaw('LOWER(name) LIKE ?', [strtolower($name) . '%'])->first();
        if ($client) return $client;

        $client = Client::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%'])->first();
        if ($client) return $client;

        return null;
    }

    private function createClientFromPayer(string $payerName): Client
    {
        return Client::create([
            'name'           => ucwords(strtolower(trim($payerName))),
            'status'         => 'active',
            'monthly_fee'    => 3000,
            'client_type_id' => $this->getDefaultClientTypeId(),
            'zone_id'        => $this->getDefaultZoneId(),
            'credit_balance' => 0,
        ]);
    }

    private function findOrCreateCollector(string $name): Staff
    {
        $name = trim($name);
        $staff = Staff::whereHas('user', fn($q) => $q->whereRaw('LOWER(name) = ?', [strtolower($name)]))->first();
        if ($staff) return $staff;

        $staff = Staff::whereHas('user', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%']))->first();
        if ($staff) return $staff;

        $staff = Staff::where('role', 'collector')->first();
        if ($staff) return $staff;

        return $this->createDefaultStaff($name);
    }

    private function createDefaultStaff(string $name): Staff
    {
        $email = Str::slug($name) . '@import.wcp';
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => ucwords(strtolower($name)), 'password' => bcrypt(Str::random(16))]
        );
        return Staff::firstOrCreate(
            ['user_id' => $user->id],
            [
                'staff_number' => 'WCP-STF-' . str_pad(Staff::count() + 1, 3, '0', STR_PAD_LEFT),
                'phone'        => '000',
                'role'         => 'collector',
                'hire_date'    => now()->toDateString(),
                'base_salary'  => 0,
            ]
        );
    }

    private function getDefaultZoneId(): int
    {
        $zone = Zone::first();
        if ($zone) return $zone->id;
        return Zone::create([
            'name'        => 'Default Zone',
            'code'        => 'DF',
            'description' => 'Default imported zone',
        ])->id;
    }

    private function getDefaultClientTypeId(): int
    {
        return ClientType::first()?->id ?? ClientType::create([
            'name' => 'Individual',
            'category' => 'residential',
            'default_monthly_fee' => 3000,
        ])->id;
    }
}
