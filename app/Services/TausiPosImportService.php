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

    /**
     * Preview data extracted from PDF text.
     */
    public function previewFromText(string $text): array
    {
        $records = $this->parseTextRows($text);
        return [
            'success' => true,
            'data' => $records,
            'total' => count($records),
            'summary' => $this->buildPreviewSummary($records),
        ];
    }

    /**
     * Preview data from file path.
     */
    public function preview(string $filePath, string $mimeType): array
    {
        $rows = $this->extractRows($filePath, $mimeType);
        return [
            'success' => true,
            'data' => $rows,
            'total' => count($rows),
            'summary' => $this->buildPreviewSummary($rows),
        ];
    }

    /**
     * Import payments from extracted text.
     */
    public function importFromText(string $text): array
    {
        $records = $this->parseTextRows($text);
        return $this->processImport($records);
    }

    /**
     * Import payments from file.
     */
    public function import(string $filePath, string $mimeType): array
    {
        $rows = $this->extractRows($filePath, $mimeType);
        return $this->processImport($rows);
    }

    // -------------------------------------------------------------------------
    // Private: Import Processing
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

        DB::transaction(function () use ($rows, &$imported, &$skipped, &$importedIds) {
            foreach ($rows as $row) {
                if (Payment::where('control_number', $row['control_number'])->exists()) {
                    $skipped++;
                    $this->warnings[] = "Control {$row['control_number']}: Already exists, skipped.";
                    continue;
                }

                try {
                    $staff = $this->findOrCreateCollector($row['collector_name'] ?? 'UNKNOWN COLLECTOR');
                    $staff->wasRecentlyCreated ? $this->stats['collectors_created']++ : $this->stats['collectors_found']++;

                    $client = $this->findClient($row['payer_name'] ?? '');
                    if (!$client && !empty($row['payer_name'])) {
                        $client = $this->createClientFromPayer($row['payer_name']);
                        $this->stats['payers_created']++;
                    } elseif ($client) {
                        $this->stats['payers_found']++;
                    }

                    if (!$client) {
                        $client = $this->getOrCreateUnknownClient();
                        $this->warnings[] = "Control {$row['control_number']}: Payer '{$row['payer_name']}' assigned to Unknown.";
                    }

                    $session = $this->findOrCreateSession($row, $staff);
                    $invoice = $this->matchInvoice($client, $row['paid_at']);
                    $invoice ? $this->stats['invoices_matched']++ : $this->stats['invoices_not_found']++;

                    $payment = Payment::create([
                        'control_number'        => $row['control_number'],
                        'bill_reference'        => $row['bill_reference'] ?? 'import-' . Str::uuid(),
                        'invoice_id'            => $invoice?->id,
                        'client_id'             => $client->id,
                        'collection_session_id' => $session->id,
                        'staff_id'              => $staff->id,
                        'amount'                => $row['amount'],
                        'payer_name'            => $row['payer_name'] ?? 'Unknown',
                        'payment_method'        => $row['payment_method'] ?? 'cash',
                        'status'                => 'paid',
                        'paid_at'               => $row['paid_at'],
                        'metadata'              => json_encode([
                            'import_source' => 'tausi_pos',
                            'original_receipt' => $row['receipt_number'] ?? null,
                            'imported_at' => now()->toDateTimeString(),
                        ]),
                    ]);

                    $importedIds[] = $payment->id;
                    $session->increment('actual_amount', $row['amount']);

                    if ($invoice && app()->bound(InvoiceService::class)) {
                        app(InvoiceService::class)->recalculate($invoice);
                    }

                    $imported++;
                } catch (\Throwable $e) {
                    $this->errors[] = "Control {$row['control_number']}: {$e->getMessage()}";
                    Log::error("Tausi import error", ['control' => $row['control_number'], 'error' => $e->getMessage()]);
                }
            }
        });

        return [
            'success'      => true,
            'imported'     => $imported,
            'skipped'      => $skipped,
            'imported_ids' => $importedIds,
            'total_amount' => collect($rows)->sum('amount'),
            'stats'        => $this->stats,
            'warnings'     => $this->warnings,
            'errors'       => $this->errors,
            'message'      => "{$imported} transactions imported, {$skipped} skipped.",
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
            $rows = $this->parseTextRows($text);
            if (empty($rows)) {
                $rows = $this->parseTextRowsLineByLine($text);
            }
            if (empty($rows)) {
                $rows = $this->parsePdfTables($pdf);
            }
            return $rows;
        } catch (\Exception $e) {
            Log::error('PDF parsing failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to parse PDF: ' . $e->getMessage());
        }
    }

    private function parseTextRows(string $text): array
    {
        $rows = [];
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n", $text);
        preg_match_all('/\b(526\d{10})\b/', $text, $ctrlMatches, PREG_OFFSET_CAPTURE);

        foreach ($ctrlMatches[1] as $index => $match) {
            $controlNumber = $match[0];
            $offset = $match[1];
            $nextOffset = $ctrlMatches[1][$index + 1][1] ?? $offset + 900;
            $window = substr($text, $offset, min($nextOffset - $offset + 100, 900));
            $row = $this->parseWindow($window, $controlNumber);
            if ($row) {
                $rows[$controlNumber] = $row;
            }
        }
        return array_values($rows);
    }

    private function parseTextRowsLineByLine(string $text): array
    {
        $rows = [];
        $lines = explode("\n", $text);
        $current = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (preg_match('/\b(526\d{10})\b/', $line, $ctrlMatch)) {
                if ($current && $current['amount'] > 0) $rows[] = $current;
                $current = [
                    'control_number' => $ctrlMatch[1],
                    'bill_reference' => 'import-' . Str::uuid(),
                    'receipt_number' => 'UNKNOWN',
                    'amount' => 0,
                    'collector_name' => 'UNKNOWN COLLECTOR',
                    'payer_name' => null,
                    'paid_at' => Carbon::now(),
                    'payment_method' => 'cash',
                ];
            }
            if ($current) {
                if (preg_match('/\b([\d]{1,3}(?:,\d{3})*\.00)\b/', $line, $amtMatch)) {
                    $current['amount'] = (float) str_replace(',', '', $amtMatch[1]);
                }
                if (preg_match('/([A-Z][a-z]{2,8}\.?\s+\d{1,2},?\s+\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?)/i', $line, $dateMatch)) {
                    try { $current['paid_at'] = Carbon::parse(str_replace(',', '', $dateMatch[1])); } catch (\Exception $e) {}
                }
                if (preg_match('/\b(993\d{9})\b/', $line, $receiptMatch)) {
                    $current['receipt_number'] = $receiptMatch[1];
                }
            }
        }
        if ($current && $current['amount'] > 0) $rows[] = $current;
        return $rows;
    }

    private function parsePdfTables($pdf): array
    {
        $rows = [];
        foreach ($pdf->getPages() as $page) {
            foreach ($page->getDataTables() as $table) {
                foreach ($table as $tableRow) {
                    if (empty($tableRow)) continue;
                    $rowText = implode(' ', $tableRow);
                    if (preg_match('/\b(526\d{10})\b/', $rowText, $ctrlMatch)) {
                        $row = $this->parseWindow($rowText, $ctrlMatch[1]);
                        if ($row) $rows[] = $row;
                    }
                }
            }
        }
        return $rows;
    }

    private function parseWindow(string $window, string $controlNumber): ?array
    {
        $amount = $this->extractAmount($window);
        if ($amount <= 0) return null;

        return [
            'control_number' => $controlNumber,
            'bill_reference' => $this->extractBillReference($window),
            'receipt_number' => $this->extractReceiptNumber($window, $controlNumber),
            'amount'         => $amount,
            'collector_name' => $this->extractCollectorName($window),
            'payer_name'     => $this->extractPayerName($window, $this->extractCollectorName($window)),
            'paid_at'        => $this->extractDate($window),
            'payment_method' => 'cash',
        ];
    }

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

    private function extractReceiptNumber(string $window, string $controlNumber): string
    {
        if (preg_match('/\b(993\d{9})\b/', $window, $m)) return $m[1];
        if (preg_match('/RCT[:\s]*(\d+)/i', $window, $m)) return 'RCT' . $m[1];
        if (preg_match('/Receipt[:\s#]*(\d+)/i', $window, $m)) return $m[1];
        return 'UNKNOWN-' . substr($controlNumber, -6);
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

    private function extractCollectorName(string $window): string
    {
        if (preg_match('/(?:collection fee|Refuse collection fee)\s+([A-Z][A-Z\s\.]+?)\s+[\d,]+\.00/i', $window, $m)) return trim($m[1]);
        if (preg_match('/Collected\s+by[:\s]+([A-Z][A-Z\s\.]+)/i', $window, $m)) return trim($m[1]);
        if (preg_match('/Collector[:\s]+([A-Z][A-Z\s\.]+)/i', $window, $m)) return trim($m[1]);
        return 'SARAH S. SHECHAMBO';
    }

    private function extractPayerName(string $window, string $collectorName): ?string
    {
        if (preg_match('/PAI[D]?\s+([A-Z][A-Z\s\.\-]+?)(?=\s+[A-Z][a-z]{2}|\s+\d|\s*$)/m', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        if (preg_match('/([A-Z][A-Z\s\.]+?)\s+(?:TZS|TSh)\s*[\d,]+\.00/i', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        if (preg_match('/(?:Customer|Client|Payer)[:\s]+([A-Z][A-Z\s\.]+)/i', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        if (preg_match('/526\d{10}\s+([A-Z][A-Z\s\.]{3,})/', $window, $m)) {
            $c = trim($m[1]);
            if ($this->isValidPayerName($c, $collectorName)) return $c;
        }
        return null;
    }

    private function isValidPayerName(string $candidate, string $collectorName): bool
    {
        if (strlen($candidate) < 3) return false;
        if (strcasecmp($candidate, $collectorName) === 0) return false;
        $headers = ['CONTROL', 'NUMBER', 'AMOUNT', 'DATE', 'PAID', 'PAGE', 'TOTAL'];
        if (in_array(strtoupper($candidate), $headers)) return false;
        if (preg_match('/^\d/', $candidate)) return false;
        if (preg_match('/^[A-Z][a-z]{2}\s+\d{1,2}$/', $candidate)) return false;
        return true;
    }

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
            if (empty($ctrl) || $amount <= 0) continue;

            $rows[] = [
                'control_number' => $ctrl,
                'bill_reference' => $mapped['bill_reference'] ?? $mapped['bill ref'] ?? 'excel-' . Str::uuid(),
                'receipt_number' => $mapped['receipt'] ?? $mapped['receipt_number'] ?? 'EXCEL',
                'amount'         => $amount,
                'collector_name' => strtoupper($mapped['collector'] ?? 'UNKNOWN COLLECTOR'),
                'payer_name'     => $mapped['payer_name'] ?? $mapped['payer'] ?? null,
                'paid_at'        => Carbon::parse($mapped['transaction_time'] ?? $mapped['date'] ?? now()),
                'payment_method' => strtolower($mapped['payment_method'] ?? 'cash'),
            ];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // Private: Helpers
    // -------------------------------------------------------------------------

    private function findOrCreateSession(array $row, Staff $staff): CollectionSession
    {
        $receipt = $row['receipt_number'] ?? 'UNKNOWN';
        $session = CollectionSession::where('session_reference', $receipt)->first();
        if ($session) return $session;

        return CollectionSession::create([
            'session_reference' => $receipt,
            'staff_id'          => $staff->id,
            'session_date'      => $row['paid_at']->toDateString(),
            'status'            => 'submitted',
            'expected_amount'   => 0,
            'actual_amount'     => $row['amount'],
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
        $year = now()->year;
        $count = Client::whereYear('created_at', $year)->count() + 1;
        return Client::create([
            'client_number'  => sprintf('WCP-%d-%05d', $year, $count),
            'name'           => ucwords(strtolower(trim($payerName))),
            'status'         => 'active',
            'monthly_fee'    => 3000,
            'client_type_id' => $this->getDefaultClientTypeId(),
            'zone_id'        => null,
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
                'credit_balance' => 0,
            ]
        );
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
}