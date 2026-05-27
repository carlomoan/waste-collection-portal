<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\CollectionSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TausiPosImportService
{
    // Parse the PDF data into payment records
    public function importFromArray(array $rows, Staff $collector): ImportResult
    {
        $created = 0; $skipped = 0; $errors = [];

        foreach ($rows as $row) {
            // Skip if control number already exists
            if (Payment::where('control_number', $row['control_number'])->exists()) {
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($row, $collector, &$created) {
                    $session = CollectionSession::firstOrCreate(
                        ['session_reference' => $row['receipt_number']],
                        ['staff_id' => $collector->id, 'session_date' => $row['paid_at']->toDateString()]
                    );

                    // Try to match to a client by name (fuzzy) or manual link later
                    $client = $this->matchClient($row['payer_name']);

                    Payment::create([
                        'control_number' => $row['control_number'],
                        'bill_reference' => $row['bill_reference'],
                        'client_id' => $client?->id ?? $this->getUnknownClientId(),
                        'collection_session_id' => $session->id,
                        'staff_id' => $collector->id,
                        'amount' => $row['amount'],
                        'payer_name' => $row['payer_name'],
                        'paid_at' => $row['paid_at'],
                        'status' => 'paid',
                    ]);
                    $created++;
                });
            } catch (\Exception $e) {
                $errors[] = "Row {$row['control_number']}: {$e->getMessage()}";
            }
        }

        return new ImportResult($created, $skipped, $errors);
    }

    private function matchClient(?string $name): ?Client
    {
        if (!$name) return null;
        return Client::where('name', 'ilike', "%{$name}%")->first();
    }
}