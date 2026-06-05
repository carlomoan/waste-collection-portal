<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\CollectionSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class TausiPosImportService
{
    private array $errors = [];
    private array $warnings = [];
    private array $stats = [
        'payers_found' => 0,
        'payers_created' => 0,
        'collectors_found' => 0,
        'collectors_created' => 0,
        'invoices_matched' => 0,
        'invoices_not_found' => 0,
    ];

    // -------------------------------------------------------------------------
    // Public Preview Methods
    // -------------------------------------------------------------------------

    public function previewFromText(string $text): array
    {
        $posNumber = $this->extractPosNumber($text);
        $records = $this->parseTextRows($text, $posNumber);
        return $this->processPreviewRows($records);
    }

    public function preview(string $filePath, string $mimeType): array
    {
        $rows = $this->extractRows($filePath, $mimeType);
        return $this->processPreviewRows($rows);
    }

    // -------------------------------------------------------------------------
    // Public Import Methods
    // -------------------------------------------------------------------------

    public function importFromText(string $text): array
    {
        $posNumber = $this->extractPosNumber($text);
        $records = $this->parseTextRows($text, $posNumber);
        return $this->processImport($records);
    }

    private function extractPosNumber(string $text): string
    {
        // Standalone POS ID on its own line: 170896-2024-00106
        if (preg_match('/\b(\d{6}-\d{4}-\d{5})\b/', $text, $m)) {
            return $m[1];
        }
        // Fallback: inline "POS: XXXX"
        if (preg_match('/POS\s*[:\s]\s*([\d\-]{8,})/i', $text, $m)) {
            return trim($m[1]);
        }
        return 'UNKNOWN-POS';
    }

    public function import(string $filePath, string $mimeType): array
    {
        $rows = $this->extractRows($filePath, $mimeType);
        return $this->processImport($rows);
    }

    // -------------------------------------------------------------------------
    // Private: Import Processing (only PAID transactions)
    // -------------------------------------------------------------------------

    private function processImport(array $rows): array
{
    $imported = 0;
    $skipped = 0;
    $importedIds = [];
    $this->errors = [];
    $this->warnings = [];
    $this->stats = [
        'payers_found' => 0,
        'payers_created' => 0,
        'collectors_found' => 0,
        'collectors_created' => 0,
        'invoices_matched' => 0,
        'invoices_not_found' => 0,
    ];

    $recordCounter = 1;
    foreach ($rows as $row) {
        $status = strtoupper($row['status'] ?? '');
        $isPaid = ($status === 'PAID');
        $receiptNum = (string) ($row['receipt_number'] ?? '');

        // Duplicate check outside any transaction to avoid 25P02 cascade
        try {
            if (!empty($receiptNum) && Payment::where('receipt_number', $receiptNum)->exists()) {
                $skipped++;
                $this->warnings[] = "Receipt {$receiptNum}: Already imported, skipped.";
                $recordCounter++;
                continue;
            }
        } catch (\Throwable $e) {
            $this->errors[] = "Receipt {$receiptNum}: duplicate-check failed – {$e->getMessage()}";
            $recordCounter++;
            continue;
        }

        // Each row gets its own transaction (savepoint in PostgreSQL)
        // so a failure in one row never aborts subsequent rows.
        try {
            DB::transaction(function () use ($row, $isPaid, $status, $receiptNum, $recordCounter, &$imported, &$importedIds) {
                $staff = $this->findOrCreateCollector($row['collector_name'] ?? 'UNKNOWN COLLECTOR');
                $staff->wasRecentlyCreated ? $this->stats['collectors_created']++ : $this->stats['collectors_found']++;

                $payerName = trim($row['payer_name'] ?? '');
                if (empty($payerName)) {
                    $posNumber = $this->getPosNumberForRow($row);
                    $dateStr   = Carbon::parse($row['paid_at'] ?? now())->toDateString();
                    $payerName = sprintf('%s %s %d', $posNumber, $dateStr, $recordCounter);
                }

                $client = $this->findClient($payerName);
                if (!$client) {
                    $client = $this->createClientFromPayer($payerName);
                    $this->stats['payers_created']++;
                } else {
                    $this->stats['payers_found']++;
                }

                $session = $this->findOrCreateSession($row, $staff);
                $invoice = $this->matchInvoice($client, $row['paid_at']);
                $invoice ? $this->stats['invoices_matched']++ : $this->stats['invoices_not_found']++;

                $paymentStatus = $isPaid ? 'paid' : 'pending';

                $payment = Payment::create([
                    'control_number'        => $row['control_number'],
                    'receipt_number'        => $row['receipt_number'] ?? null,
                    'pos_number'            => $row['pos_number'] ?? null,
                    'bill_reference'        => $row['bill_reference'] ?? 'import-' . Str::uuid(),
                    'invoice_id'            => $invoice?->id,
                    'client_id'             => $client->id,
                    'collection_session_id' => $session->id,
                    'staff_id'              => $staff->id,
                    'amount'                => $row['amount'],
                    'payer_name'            => $payerName,
                    'payment_method'        => $row['payment_method'] ?? 'cash',
                    'status'                => $paymentStatus,
                    'paid_at'               => $isPaid ? $row['paid_at'] : null,
                    'metadata'              => json_encode([
                        'import_source'   => 'tausi_pos',
                        'original_status' => $status,
                        'pos_number'      => $row['pos_number'] ?? null,
                        'imported_at'     => now()->toDateTimeString(),
                    ]),
                ]);

                $importedIds[] = $payment->id;

                if ($isPaid) {
                    $session->increment('actual_amount', $row['amount']);
                } else {
                    $this->warnings[] = "Receipt {$receiptNum}: NOT PAID – recorded as pending.";
                }

                if ($invoice && $isPaid && app()->bound(InvoiceService::class)) {
                    app(InvoiceService::class)->recalculate($invoice);
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
        'total_amount_paid' => collect($rows)->where('status', 'PAID')->sum('amount'),
        'total_amount_pending' => collect($rows)->where('status', 'NOT_PAID')->sum('amount'),
        'stats'        => $this->stats,
        'warnings'     => $this->warnings,
        'errors'       => $this->errors,
        'message'      => "{$imported} transactions imported (PAID: ".collect($rows)->where('status','PAID')->count().", NOT PAID: ".collect($rows)->where('status','NOT_PAID')->count().").",
    ];
}

    // -------------------------------------------------------------------------
    // Private: File Extraction
    // -------------------------------------------------------------------------

    private function extractRows(string $filePath, string $mimeType): array
    {
        if (str_contains($mimeType, 'pdf')) {
            return $this->parsePdf($filePath);
        }
        return $this->parseExcel($filePath);
    }

    private function parsePdf(string $filePath): array
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            $posNumber = $this->extractPosNumber($text);
            $rows = $this->parseTextRows($text, $posNumber);
            if (empty($rows)) {
                $rows = $this->parseTextRowsLineByLine($text, $posNumber);
            }
            if (empty($rows)) {
                $rows = $this->parsePdfTables($pdf, $posNumber);
            }
            return $rows;
        } catch (\Exception $e) {
            Log::error('PDF parsing failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to parse PDF: ' . $e->getMessage());
        }
    }

    private function parseTextRows(string $text, string $posNumber = 'UNKNOWN-POS'): array
    {
        // Normalise whitespace: collapse horizontal whitespace but keep newlines
        $text = preg_replace('/[^\S\n]+/', ' ', $text);

        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn($l) => $l !== ''
        ));

        $n    = count($lines);
        $rows = [];

        for ($i = 0; $i < $n; $i++) {
            // Each individual receipt line is exactly a 526XXXXXXXXXX number
            if (!preg_match('/^(526\d{10})$/', $lines[$i], $rcptMatch)) {
                continue;
            }

            $receiptNumber = $rcptMatch[1];
            $dataLine      = $lines[$i + 1] ?? '';

            // Data line format — either single line or multi-line payer name:
            // Single: "PAID    Jun 02, 2026 16:03:48MWANAID WENGI1     993110509951"
            // Multi:  "PAID    Jun 01, 2026 18:30:08"  /  "VOICE COMPANY"  /  "LIMITED"  /  "11      993110509951"
            $status        = 'PAID';
            $controlNumber = 'UNKNOWN-CTRL';
            $payerName     = null;
            $paidAt        = Carbon::now();

            $datePattern = '/^(PAID|UNPAID)\s+([A-Z][a-z]{2,}\s+\d{1,2},?\s+\d{4}\s+\d{2}:\d{2}:\d{2})/i';

            if (preg_match(
                '/^(PAID|UNPAID)\s+([A-Z][a-z]{2,}\s+\d{1,2},?\s+\d{4}\s+\d{2}:\d{2}:\d{2})\s*([A-Z][A-Z\s\.\-]*?)\s*\d+\s+(993\d{9})/i',
                $dataLine, $m
            )) {
                // Normal single-line: status + date + payer + recno + ctrl all on one line
                $status        = strtoupper($m[1]) === 'PAID' ? 'PAID' : 'NOT_PAID';
                try { $paidAt = Carbon::parse(str_replace(',', '', $m[2])); } catch (\Exception $e) {}
                $payerName     = trim($m[3]) ?: null;
                $controlNumber = $m[4];
            } elseif (preg_match($datePattern, $dataLine, $m)) {
                // Multi-line: status+date only, payer name and ctrl are on subsequent lines
                $status = strtoupper($m[1]) === 'PAID' ? 'PAID' : 'NOT_PAID';
                try { $paidAt = Carbon::parse(str_replace(',', '', $m[2])); } catch (\Exception $e) {}

                // Any trailing uppercase text on the same date line (partial payer name)
                $trailingName = preg_replace($datePattern, '', $dataLine);
                $payerParts   = (trim($trailingName) !== '') ? [trim($trailingName)] : [];

                // Look up to 5 more lines for payer name parts and control number
                for ($k = $i + 2; $k <= min($i + 6, $n - 1); $k++) {
                    $ahead = $lines[$k];
                    if (preg_match('/\b(993\d{9})\b/', $ahead, $cm)) {
                        $controlNumber = $cm[1];
                        break;
                    }
                    // Uppercase-only words (no digits) → part of payer name
                    if (preg_match('/^[A-Z][A-Z\s\.\-]+$/u', $ahead) && !preg_match('/\d/', $ahead)) {
                        $payerParts[] = $ahead;
                    }
                }
                $payerName = !empty($payerParts) ? trim(implode(' ', $payerParts)) : null;
            } elseif (preg_match('/\b(993\d{9})\b/', $dataLine, $cm)) {
                $controlNumber = $cm[1];
                if (preg_match('/\b(PAID|UNPAID)\b/i', $dataLine, $sm)) {
                    $status = strtoupper($sm[1]) === 'PAID' ? 'PAID' : 'NOT_PAID';
                }
            }

            // Scan backward (up to 12 lines) for amount, collector, bill reference
            $amount         = 0;
            $collectorLines = [];
            $billRefParts   = [];

            for ($j = $i - 1; $j >= max(0, $i - 12); $j--) {
                $prev = $lines[$j];

                // Amount: standalone "6,000.00" or "3,000.00"
                if ($amount === 0 && preg_match('/^([\d,]+\.00)$/', $prev, $am)) {
                    $amount = (float) str_replace(',', '', $am[1]);
                }

                // Bill reference parts (split UUID: "d2803b63-cb75-", "40c6-98bf-", "874d6c931f9e")
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-?$/i', $prev)
                 || preg_match('/^[0-9a-f]{4}-[0-9a-f]{4}-?$/i', $prev)
                 || preg_match('/^[0-9a-f]{12}$/i', $prev)) {
                    array_unshift($billRefParts, rtrim($prev, '-'));
                }

                // Collector name: short all-caps word(s), no digits
                if (!preg_match('/\d/', $prev)
                 && preg_match('/^[A-Z][A-Z\s\.]{1,}[A-Z\.]$/', $prev)
                 && strlen($prev) <= 30) {
                    array_unshift($collectorLines, $prev);
                }
            }

            if ($amount <= 0) continue;

            $collectorName = !empty($collectorLines)
                ? implode(' ', $collectorLines)
                : 'UNKNOWN COLLECTOR';

            // Reconstruct bill reference from collected parts
            $billRef = 'import-' . Str::uuid();
            if (!empty($billRefParts)) {
                $clean = preg_replace('/[^0-9a-f]/i', '', implode('', $billRefParts));
                if (strlen($clean) === 32) {
                    $billRef = substr($clean, 0, 8) . '-'
                             . substr($clean, 8, 4)  . '-'
                             . substr($clean, 12, 4) . '-'
                             . substr($clean, 16, 4) . '-'
                             . substr($clean, 20);
                }
            }

            $rows[$receiptNumber] = [
                'receipt_number' => $receiptNumber,
                'control_number' => $controlNumber,
                'pos_number'     => $posNumber,
                'bill_reference' => $billRef,
                'amount'         => $amount,
                'collector_name' => $collectorName,
                'payer_name'     => $payerName,
                'paid_at'        => $paidAt,
                'payment_method' => 'cash',
                'status'         => $status,
            ];
        }

        return array_values($rows);
    }

    private function parseTextRowsLineByLine(string $text, string $posNumber = 'UNKNOWN-POS'): array
    {
        $rows = [];
        $lines = explode("\n", $text);
        $current = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            // 526XXXXXXXXXX = individual transaction receipt (unique per row)
            if (preg_match('/\b(526\d{10})\b/', $line, $rcptMatch)) {
                if ($current && $current['amount'] > 0) $rows[] = $current;
                $current = [
                    'receipt_number' => $rcptMatch[1],
                    'control_number' => 'UNKNOWN-CTRL',
                    'pos_number'     => $posNumber,
                    'bill_reference' => 'import-' . Str::uuid(),
                    'amount'         => 0,
                    'collector_name' => 'UNKNOWN COLLECTOR',
                    'payer_name'     => null,
                    'paid_at'        => Carbon::now(),
                    'payment_method' => 'cash',
                    'status'         => 'PAID',
                ];
            }
            if ($current) {
                if (preg_match('/\b([\d]{1,3}(?:,\d{3})*\.00)\b/', $line, $amtMatch)) {
                    $current['amount'] = (float) str_replace(',', '', $amtMatch[1]);
                }
                if (preg_match('/([A-Z][a-z]{2,8}\.?\s+\d{1,2},?\s+\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?)/i', $line, $dateMatch)) {
                    try { $current['paid_at'] = Carbon::parse(str_replace(',', '', $dateMatch[1])); } catch (\Exception $e) {}
                }
                // 993XXXXXXXXX = banking control number (shared across rows from same POS session)
                if (preg_match('/\b(993\d{9})\b/', $line, $ctrlMatch)) {
                    $current['control_number'] = $ctrlMatch[1];
                }
                if (preg_match('/\b(PAID|UNPAID|NOT\s+PAID)\b/i', $line, $statusMatch)) {
                    $raw = strtoupper(trim($statusMatch[1]));
                    $current['status'] = ($raw === 'PAID') ? 'PAID' : 'NOT_PAID';
                }
                if (preg_match('/^(\d+\s+)?([A-Z][A-Z\s\.]+?)(?:MC\d+|'.preg_quote($current['receipt_number'], '/').')/', $line, $colMatch)) {
                    $current['collector_name'] = trim($colMatch[2]);
                }
                if (preg_match('/(?:PAID|UNPAID|NOT\s+PAID)\s+([A-Z][A-Z\s\.]+?)(?:\s+\d{1,2}:\d{2}|$)/i', $line, $payerMatch)) {
                    $current['payer_name'] = trim($payerMatch[1]);
                }
            }
        }
        if ($current && $current['amount'] > 0) $rows[] = $current;
        return $rows;
    }

    private function parsePdfTables($pdf, string $posNumber = 'UNKNOWN-POS'): array
    {
        $rows = [];
        foreach ($pdf->getPages() as $page) {
            foreach ($page->getDataTables() as $table) {
                foreach ($table as $tableRow) {
                    if (empty($tableRow)) continue;
                    $rowText = implode(' ', $tableRow);
                    if (preg_match('/\b(526\d{10})\b/', $rowText, $rcptMatch)) {
                        $row = $this->parseWindow($rowText, $rcptMatch[1], $posNumber);
                        if ($row) $rows[] = $row;
                    }
                }
            }
        }
        return $rows;
    }

    private function parseWindow(string $window, string $receiptNumber, string $posNumber = 'UNKNOWN-POS'): ?array
    {
        $amount = $this->extractAmount($window);
        if ($amount <= 0) return null;

        // PAID / UNPAID / NOT PAID
        $status = 'PAID';
        if (preg_match('/\b(NOT\s+PAID|UNPAID)\b/i', $window, $statusMatch)) {
            $status = 'NOT_PAID';
        } elseif (preg_match('/\bPAID\b/i', $window)) {
            $status = 'PAID';
        }
        $collectorName = $this->extractCollectorName($window);
        $payerName = $this->extractPayerName($window, $collectorName);

        // 993XXXXXXXXX = banking control number (shared across rows from same POS session)
        $controlNumber = $this->extractBankingControlNumber($window);

        return [
            'receipt_number' => $receiptNumber,
            'control_number' => $controlNumber,
            'pos_number'     => $posNumber,
            'bill_reference' => $this->extractBillReference($window),
            'amount'         => $amount,
            'collector_name' => $collectorName,
            'payer_name'     => $payerName,
            'paid_at'        => $this->extractDate($window),
            'payment_method' => 'cash',
            'status'         => $status,
        ];
    }

    private function extractBankingControlNumber(string $window): string
    {
        if (preg_match('/\b(993\d{9})\b/', $window, $m)) {
            return $m[1];
        }
        return 'UNKNOWN-CTRL';
    }

    // -------------------------------------------------------------------------
    // Improved Extractor Methods
    // -------------------------------------------------------------------------

    private function extractAmount(string $text): float
    {
        if (preg_match('/\b([\d]{1,3}(?:,\d{3})*\.00)\b/', $text, $m)) return (float) str_replace(',', '', $m[1]);
        if (preg_match('/(?:TZS|TSh)\s*([\d,]+\.?\d*)/i', $text, $m)) return (float) str_replace(',', '', $m[1]);
        if (preg_match('/([\d,]+\.?\d*)\s*\/=/', $text, $m)) return (float) str_replace(',', '', $m[1]);
        return 0;
    }

    private function extractBillReference(string $window): string
    {
        $clean = preg_replace('/[\s\-]+/', '', $window);
        if (preg_match('/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/i', $clean, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}-{$m[4]}-{$m[5]}";
        }
        return 'import-' . Str::uuid();
    }

    private function getPosNumberForRow(array $row): string
    {
        return $row['pos_number'] ?? 'UNKNOWN-POS';
    }

    private function extractDate(string $window): Carbon
    {
        if (preg_match('/([A-Z][a-z]{2,8}\.?\s+\d{1,2},?\s+\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?(?:\s*[AP]M)?)/i', $window, $m)) {
            try { return Carbon::parse(str_replace(',', '', $m[1])); } catch (\Exception $e) {}
        }
        if (preg_match('/(\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}-\d{2})/', $window, $m)) {
            try { return Carbon::parse($m[1]); } catch (\Exception $e) {}
        }
        if (preg_match('/\b(\d{10,13})\b/', $window, $m)) {
            $ts = strlen($m[1]) === 13 ? $m[1] / 1000 : $m[1];
            return Carbon::createFromTimestamp($ts);
        }
        return Carbon::now();
    }

    /**
     * Enhanced collector name extraction:
     * - Looks for all-caps name before the control number or after "Collector"
     * - Also captures name from table row start (e.g., "NAYLATI H. KALIMILWA")
     */
    private function extractCollectorName(string $window): string
    {
        // Pattern 1: Name right before control number (common in PDF)
        if (preg_match('/([A-Z][A-Z\s\.]+?)\s+MC\d+/', $window, $m)) {
            return trim($m[1]);
        }
        // Pattern 2: After "collection fee" or "Refuse collection fee"
        if (preg_match('/(?:collection fee|Refuse collection fee)\s+([A-Z][A-Z\s\.]+?)\s+[\d,]+\.00/i', $window, $m)) {
            return trim($m[1]);
        }
        // Pattern 3: After "Collected by:"
        if (preg_match('/Collected\s+by[:\s]+([A-Z][A-Z\s\.]+)/i', $window, $m)) {
            return trim($m[1]);
        }
        // Pattern 4: After "Collector:"
        if (preg_match('/Collector[:\s]+([A-Z][A-Z\s\.]+)/i', $window, $m)) {
            return trim($m[1]);
        }
        // Pattern 5: First all-caps word(s) at beginning of window (line start)
        if (preg_match('/^(?:No\.?\s+\d+\s+)?([A-Z][A-Z\s\.]+?)(?=\s+MC\d+|\s+\d{13})/m', $window, $m)) {
            return trim($m[1]);
        }
        return 'UNKNOWN COLLECTOR';
    }

    private function extractPayerName(string $window, string $collectorName): ?string
    {
        // Pattern 1: After "PAID" or "NOT PAID"
        if (preg_match('/(?:PAID|NOT\s+PAID)\s+([A-Z][A-Z\s\.\-]+?)(?:\s+\d{1,2}:\d{2}|$)/i', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        // Pattern 2: Before "TZS" or amount
        if (preg_match('/([A-Z][A-Z\s\.]+?)\s+(?:TZS|TSh)\s*[\d,]+\.00/i', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        // Pattern 3: After "Customer/Client/Payer"
        if (preg_match('/(?:Customer|Client|Payer)[:\s]+([A-Z][A-Z\s\.]+)/i', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        // Pattern 4: After control number
        if (preg_match('/526\d{10}\s+([A-Z][A-Z\s\.]{3,})/', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        // Pattern 5: In table row, after status column (e.g., "PAID JOHN DOE")
        if (preg_match('/(?:PAID|NOT\s+PAID)\s+([A-Z][A-Z\s\.\-]+?)(?=\s+[A-Z][a-z]{2}\s+\d{1,2}|$)/i', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        return null;
    }

    private function isValidPayerName(string $candidate, string $collectorName): bool
    {
        if (strlen($candidate) < 3) return false;
        if (strcasecmp($candidate, $collectorName) === 0) return false;
        $headers = ['CONTROL', 'NUMBER', 'AMOUNT', 'DATE', 'PAID', 'PAGE', 'TOTAL', 'REFUSE', 'COLLECTION', 'FEE'];
        if (in_array(strtoupper($candidate), $headers)) return false;
        if (preg_match('/^\d/', $candidate)) return false;
        if (preg_match('/^[A-Z][a-z]{2}\s+\d{1,2}$/', $candidate)) return false;
        return true;
    }

    // -------------------------------------------------------------------------
    // Excel Parsing (with status filtering)
    // -------------------------------------------------------------------------

    private function parseExcel(string $filePath): array
    {
        $rows = [];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [];

        foreach ($sheet->getRowIterator() as $index => $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getFormattedValue());
            }
            if ($index === 1) {
                $headers = array_map('strtolower', array_map('trim', $cells));
                continue;
            }
            if (empty(array_filter($cells))) continue;

            $count = min(count($headers), count($cells));
            $mapped = array_combine(
                array_slice($headers, 0, $count),
                array_slice($cells, 0, $count)
            );

            $ctrl = $mapped['control_number'] ?? $mapped['control no'] ?? $mapped['control'] ?? '';
            $amount = (float) str_replace(',', '', $mapped['amount'] ?? 0);
            $status = strtoupper($mapped['status'] ?? '');
            if (empty($ctrl) || $amount <= 0) continue;

            // Only accept PAID transactions
            if ($status !== 'PAID') continue;

            $rows[] = [
                'control_number' => $ctrl,
                'bill_reference' => $mapped['bill_reference'] ?? $mapped['bill ref'] ?? 'excel-' . Str::uuid(),
                'receipt_number' => $mapped['receipt'] ?? $mapped['receipt_number'] ?? 'EXCEL',
                'amount'         => $amount,
                'collector_name' => strtoupper($mapped['collector'] ?? 'UNKNOWN COLLECTOR'),
                'payer_name'     => $mapped['payer_name'] ?? $mapped['payer'] ?? null,
                'paid_at'        => Carbon::parse($mapped['transaction_time'] ?? $mapped['date'] ?? now()),
                'payment_method' => strtolower($mapped['payment_method'] ?? 'cash'),
                'status'         => $status,
            ];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // Helpers (unchanged but kept for completeness)
    // -------------------------------------------------------------------------

    private function findOrCreateSession(array $row, Staff $staff): CollectionSession
    {
        // Group by the banking control number (993...) which is shared across all rows in a POS session
        $sessionRef = $row['control_number'] ?? ($row['pos_number'] ?? 'UNKNOWN');
        $session = CollectionSession::where('session_reference', $sessionRef)->first();
        if ($session) return $session;

        return CollectionSession::create([
            'session_reference' => $sessionRef,
            'staff_id'          => $staff->id,
            'session_date'      => $row['paid_at']->toDateString(),
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
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            $client = Client::where(function ($q) use ($words) {
                foreach ($words as $word) if (strlen($word) >= 3) $q->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($word) . '%']);
            })->first();
            if ($client) return $client;
        }
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

    private function getOrCreateUnknownClient(): Client
    {
        return Client::firstOrCreate(
            ['client_number' => 'WCP-UNKNOWN'],
            [
                'name'           => 'Unknown / Unmatched Payer',
                'status'         => 'active',
                'monthly_fee'    => 0,
                'client_type_id' => $this->getDefaultClientTypeId(),
                'zone_id'        => $this->getDefaultZoneId(),
                'credit_balance' => 0,
            ]
        );
    }

    private function getDefaultZoneId(): int
    {
        $zone = \App\Models\Zone::first();
        if ($zone) {
            return $zone->id;
        }
        
        return \App\Models\Zone::create([
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

    private function buildPreviewSummary(array $rows): array
    {
        $amounts = collect($rows)->pluck('amount');
        return [
            'total_rows'   => count($rows),
            'total_amount' => $amounts->sum(),
            'min_amount'   => $amounts->min(),
            'max_amount'   => $amounts->max(),
            'avg_amount'   => round($amounts->avg() ?? 0, 0),
            'collectors'   => collect($rows)->pluck('collector_name')->unique()->values()->toArray(),
            'receipts'     => collect($rows)->pluck('receipt_number')->unique()->count(),
            'date_range'   => [
                'from' => collect($rows)->min('paid_at')?->toDateString(),
                'to'   => collect($rows)->max('paid_at')?->toDateString(),
            ],
        ];
    }

    private function processPreviewRows(array $rows): array
    {
        $processedRows = [];
        $duplicates = 0;
        $willImport = 0;
        $newClientsMap = [];

        foreach ($rows as $row) {
            $rcpt = (string) ($row['receipt_number'] ?? '');
            $alreadyExists = false;

            // Duplicate check by receipt_number (526... is unique per transaction)
            if (!empty($rcpt)) {
                $alreadyExists = Payment::where('receipt_number', $rcpt)->exists();
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
            $processedRows[] = $row;
        }

        return [
            'success' => true,
            'rows' => $processedRows,
            'data' => $processedRows,
            'total' => count($processedRows),
            'will_import' => $willImport,
            'duplicates' => $duplicates,
            'new_clients' => array_values($newClientsMap),
            'summary' => $this->buildPreviewSummary($processedRows),
        ];
    }
}