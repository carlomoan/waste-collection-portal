<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function applyCreditBalance(Client $client): void
    {
        if ($client->credit_balance <= 0) return;

        $outstandingInvoices = $client->invoices()
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->orderBy('due_date')
            ->get();

        $remaining = $client->credit_balance;

        foreach ($outstandingInvoices as $invoice) {
            if ($remaining <= 0) break;
            $apply = min($remaining, $invoice->balance);
            DB::transaction(function () use ($invoice, $apply, $client) {
                Payment::create([
                    'client_id' => $client->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $apply,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'control_number' => 'CREDIT-' . $client->client_number,
                    'payer_name' => $client->name,
                ]);
                $client->decrement('credit_balance', $apply);
            });
            $remaining -= $apply;
        }
    }

    public function calculateMonthlyFee(Client $client): float
    {
        return $client->monthly_fee > 0 ? $client->monthly_fee : ($client->clientType?->default_monthly_fee ?? 0);
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

    public function generateStatement(Client $client, ?int $year = null, ?int $month = null): array
    {
        $query = $client->payments()->with('invoice')->orderBy('paid_at');
        if ($year) $query->whereYear('paid_at', $year);
        if ($month) $query->whereMonth('paid_at', $month);
        $payments = $query->get();

        $invoices = $client->invoices()
            ->when($year, fn($q) => $q->where('billing_year', $year))
            ->when($month, fn($q) => $q->where('billing_month', $month))
            ->orderBy('due_date')
            ->get();

        return [
            'client' => $client,
            'invoices' => $invoices,
            'payments' => $payments,
            'balance_forward' => $client->outstanding_balance,
        ];
    }
}