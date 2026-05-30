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
use Illuminate\Support\Str;

class TausiPosImportService
{
    private array $errors = [];

    /*
    |--------------------------------------------------------------------------
    | PUBLIC: Preview (no DB writes — returns parsed rows for user to confirm)
    |--------------------------------------------------------------------------
    */
    public function preview(string $filePath, string $mimeType): array
    {
        $rows    = $this->extractRows($filePath, $mimeType);
        $preview = [];

        foreach ($rows as $row) {
            $client = $this->findClient($row['payer_name']);
            $exists = Payment::where('control_number', $row['control_number'])->exists();

            $preview[] = array_merge($row, [
                'paid_at'            => $row['paid_at']->toDateTimeString(),
                'client_found'       => $client !== null,
                'client_name'        => $client?->name ?? $row['payer_name'],
                'client_id'          => $client?->id,
                'client_number'      => $client?->client_number ?? 'NEW',
                'already_exists'     => $exists,
                'will_create_client' => $client === null && !empty($row['payer_name']),
            ]);
        }

        $newClients = collect($preview)
            ->where('will_create_client', true)
            ->pluck('payer_name')
            ->unique()
            ->values()
            ->toArray();

        return [
            'rows'        => $preview,
            'total'       => count($preview),
            'new_clients' => $newClients,
            'duplicates'  => collect($preview)->where('already_exists', true)->count(),
            'will_import' => collect($preview)->where('already_exists', false)->count(),
            'summary'     => $this->buildPreviewSummary($preview),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC: Import (writes to DB)
    |--------------------------------------------------------------------------
    */
    public function import(string $filePath, string $mimeType): array
    {
        $rows = $this->extractRows($filePath, $mimeType);
        $imported = 0; $skipped = 0; $clientsCreated = 0;

        DB::transaction(function () use ($rows, &$imported, &$skipped, &$clientsCreated) {
            foreach ($rows as $row) {

                // Skip if control number already recorded
                if (Payment::where('control_number', $row['control_number'])->exists()) {
                    $skipped++;
                    continue;
                }

                try {
                    // Collector
                    $staff = $this->findOrCreateCollector($row['collector_name']);

                    // Client — find or auto-create
                    $client = $this->findClient($row['payer_name']);
                    if (!$client && !empty($row['payer_name'])) {
                        $client = $this->createClientFromPayer($row['payer_name']);
                        $clientsCreated++;
                    }
                    if (!$client) {
                        $client = $this->getOrCreateUnknownClient();
                    }

                    // Collection session (receipt batch)
                    $session = CollectionSession::firstOrCreate(
                        ['session_reference' => $row['receipt_number']],
                        [
                            'staff_id'     => $staff->id,
                            'session_date' => $row['paid_at']->toDateString(),
                            'status'       => 'submitted',
                        ]
                    );

                    // Match invoice for client+month if exists
                    $invoice = Invoice::where('client_id', $client->id)
                        ->where('billing_month', $row['paid_at']->month)
                        ->where('billing_year',  $row['paid_at']->year)
                        ->first();

                    // Create payment
                    Payment::create([
                        'control_number'        => $row['control_number'],
                        'bill_reference'        => $row['bill_reference'],
                        'invoice_id'            => $invoice?->id,
                        'client_id'             => $client->id,
                        'collection_session_id' => $session->id,
                        'staff_id'              => $staff->id,
                        'amount'                => $row['amount'],
                        'payer_name'            => $row['payer_name'],
                        'payment_method'        => 'cash',
                        'status'                => 'paid',
                        'paid_at'               => $row['paid_at'],
                    ]);

                    // Update session running total
                    $session->increment('actual_amount', $row['amount']);

                    // Recalculate invoice balance
                    if ($invoice) {
                        app(InvoiceService::class)->recalculate($invoice);
                    }

                    $imported++;

                } catch (\Throwable $e) {
                    $this->errors[] = "Control {$row['control_number']}: {$e->getMessage()}";
                }
            }
        });

        return [
            'imported'        => $imported,
            'skipped'         => $skipped,
            'clients_created' => $clientsCreated,
            'total_amount'    => collect($rows)->sum('amount'),
            'errors'          => $this->errors,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PARSING
    |--------------------------------------------------------------------------
    */
    private function extractRows(string $filePath, string $mimeType): array
    {
        if (str_contains($mimeType, 'pdf')) {
            return $this->parsePdf($filePath);
        }
        return $this->parseExcel($filePath);
    }

    // PDF ─────────────────────────────────────────────────────────────────────
    private function parsePdf(string $filePath): array
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($filePath);
        $text   = $pdf->getText();
        return $this->parseTextRows($text);
    }

    private function parseTextRows(string $text): array
    {
        $rows = [];

        // Normalise whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Anchor: control numbers start with 526 and are 13 digits
        preg_match_all('/\b(526\d{10})\b/', $text, $ctrlMatches, PREG_OFFSET_CAPTURE);

        foreach ($ctrlMatches[1] as $match) {
            $controlNumber = $match[0];
            $offset        = $match[1];

            // Context window around control number
            $start  = max(0, $offset - 350);
            $window = substr($text, $start, 600);

            $row = $this->parseWindow($window, $controlNumber);
            if ($row) {
                $rows[$controlNumber] = $row;
            }
        }

        return array_values($rows);
    }

    private function parseWindow(string $window, string $controlNumber): ?array
    {
        // Amount — e.g. 3,000.00 / 60,000.00 / 1,005,000.00
        preg_match('/\b([\d]{1,3}(?:,\d{3})*\.00)\b/', $window, $amtMatch);
        $amount = isset($amtMatch[1]) ? (float) str_replace(',', '', $amtMatch[1]) : 0;
        if ($amount <= 0) return null;

        // UUID bill reference (may be split across lines — rejoin)
        $windowClean = preg_replace('/[\s\-]+/', '', $window);
        preg_match(
            '/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/i',
            $windowClean,
            $uuidRaw
        );
        $billRef = isset($uuidRaw[1])
            ? "{$uuidRaw[1]}-{$uuidRaw[2]}-{$uuidRaw[3]}-{$uuidRaw[4]}-{$uuidRaw[5]}"
            : 'import-' . Str::uuid();

        // Receipt number — 12 digits starting with 993
        preg_match('/\b(993\d{9})\b/', $window, $receiptMatch);
        $receipt = $receiptMatch[1] ?? 'UNKNOWN-' . substr($controlNumber, -6);

        // Transaction date — "May 15, 2026 18:37:50" or "May 15, 2026 6:58:18"
        preg_match(
            '/([A-Z][a-z]{2,8}\.?\s+\d{1,2},?\s+\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?(?:\s*[AP]M)?)/i',
            $window, $dateMatch
        );
        try {
            $paidAt = isset($dateMatch[1])
                ? Carbon::parse(str_replace(',', '', $dateMatch[1]))
                : Carbon::now();
        } catch (\Exception) {
            $paidAt = Carbon::now();
        }

        // Collector — all-caps name between "fee" and amount
        preg_match(
            '/(?:collection fee|Refuse collection fee)\s+([A-Z][A-Z\s\.]+?)\s+[\d,]+\.00/i',
            $window, $collectorMatch
        );
        $collectorName = isset($collectorMatch[1])
            ? trim($collectorMatch[1])
            : 'SARAH S. SHECHAMBO'; // fallback to report collector

        // Payer name — appears after PAI/PAID, before next date or end
        preg_match('/PAI[D]?\s+([A-Z][A-Z\s]+?)(?=\s+[A-Z][a-z]{2}|\s+\d|$)/m', $window, $payerMatch);
        $payerName = null;
        if (isset($payerMatch[1])) {
            $candidate = trim($payerMatch[1]);
            // Reject if too short, matches collector, or looks like a date fragment
            if (strlen($candidate) >= 3 && $candidate !== $collectorName
                && !preg_match('/^\d/', $candidate)) {
                $payerName = $candidate;
            }
        }

        return [
            'control_number' => $controlNumber,
            'bill_reference' => $billRef,
            'receipt_number' => $receipt,
            'amount'         => $amount,
            'collector_name' => $collectorName,
            'payer_name'     => $payerName,
            'paid_at'        => $paidAt,
            'item_name'      => 'Refuse collection fee',
            'status'         => 'paid',
        ];
    }

    // Excel ───────────────────────────────────────────────────────────────────
    private function parseExcel(string $filePath): array
    {
        $rows        = [];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $headers     = [];

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

            $count   = min(count($headers), count($cells));
            $mapped  = array_combine(
                array_slice($headers, 0, $count),
                array_slice($cells, 0, $count)
            );

            $ctrl   = $mapped['control_number'] ?? $mapped['control no'] ?? $mapped['control'] ?? '';
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
                'item_name'      => 'Refuse collection fee',
                'status'         => 'paid',
            ];
        }

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    private function findClient(?string $payerName): ?Client
    {
        if (empty(trim((string) $payerName))) return null;

        $name = trim($payerName);

        // Exact match first
        $client = Client::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($client) return $client;

        // Starts-with match
        return Client::whereRaw('LOWER(name) LIKE ?', [strtolower($name) . '%'])->first();
    }

    private function createClientFromPayer(string $payerName): Client
    {
        $year  = now()->year;
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
        // Match by user name
        $staff = Staff::whereHas('user', fn($q) =>
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($name)) . '%'])
        )->first();

        if ($staff) return $staff;

        // Fallback: use any existing staff
        $staff = Staff::first();
        if ($staff) return $staff;

        // Last resort: create from name
        return $this->createDefaultStaff($name);
    }

    private function createDefaultStaff(string $name): Staff
    {
        $email = Str::slug($name) . '@import.wcp';
        $user  = User::firstOrCreate(
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
        return ClientType::first()?->id
            ?? ClientType::create([
                'name'                => 'Individual',
                'category'            => 'residential',
                'default_monthly_fee' => 3000,
            ])->id;
    }

    private function buildPreviewSummary(array $rows): array
    {
        $amounts = collect($rows)->pluck('amount');
        return [
            'total_amount' => $amounts->sum(),
            'min_amount'   => $amounts->min(),
            'max_amount'   => $amounts->max(),
            'avg_amount'   => round($amounts->avg() ?? 0, 0),
            'collectors'   => collect($rows)->pluck('collector_name')->unique()->values(),
            'receipts'     => collect($rows)->pluck('receipt_number')->unique()->count(),
        ];
    }
}
