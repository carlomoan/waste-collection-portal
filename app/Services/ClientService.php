<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function applyCreditBalance(Client $client): void
    {
        // Apply credit balance to outstanding invoices
        if ($client->credit_balance <= 0) {
            return;
        }

        $outstandingInvoices = $client->invoices()
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->orderBy('due_date')
            ->get();

        $remainingCredit = $client->credit_balance;

        foreach ($outstandingInvoices as $invoice) {
            if ($remainingCredit <= 0) {
                break;
            }

            $amountToApply = min($remainingCredit, $invoice->balance);
            
            DB::transaction(function () use ($invoice, $amountToApply, $client) {
                // Create a payment record for the credit application
                Payment::create([
                    'client_id' => $client->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amountToApply,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'control_number' => 'CREDIT-' . $client->client_number,
                    'payer_name' => $client->name,
                ]);

                // Update client credit balance
                $client->decrement('credit_balance', $amountToApply);
            });

            $remainingCredit -= $amountToApply;
        }
    }

    public function calculateMonthlyFee(Client $client): float
    {
        // Use client's individual fee if set, otherwise use client type default
        if ($client->monthly_fee > 0) {
            return $client->monthly_fee;
        }

        return $client->clientType?->default_monthly_fee ?? 0;
    }

    public function suspendClient(Client $client, string $reason): void
    {
        $client->update([
            'status' => 'suspended',
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);
    }

    public function activateClient(Client $client): void
    {
        $client->update([
            'status' => 'active',
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);
    }

    public function getClientStats(Client $client): array
    {
        return [
            'total_invoices' => $client->invoices()->count(),
            'paid_invoices' => $client->invoices()->where('status', 'paid')->count(),
            'unpaid_invoices' => $client->invoices()->whereIn('status', ['unpaid', 'partial', 'overdue'])->count(),
            'total_paid' => $client->total_paid,
            'outstanding_balance' => $client->outstanding_balance,
            'credit_balance' => $client->credit_balance,
            'last_payment' => $client->payments()->latest()->first(),
        ];
    }
}
