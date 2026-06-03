<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateMonthlyInvoices(int $month, int $year): int
    {
        $graceDays = config('wcp.grace_period_days', 15);
        $dueDate = Carbon::create($year, $month)->endOfMonth();
        $graceEnd = $dueDate->copy()->addDays($graceDays);
        $count = 0;

        Client::where('status', 'active')
            ->with('clientType')
            ->chunkById(100, function ($clients) use ($month, $year, $dueDate, $graceEnd, &$count) {
                foreach ($clients as $client) {
                    Invoice::firstOrCreate(
                        ['client_id' => $client->id, 'billing_month' => $month, 'billing_year' => $year],
                        [
                            'amount_due' => $client->monthly_fee,
                            'balance' => $client->monthly_fee,
                            'due_date' => $dueDate,
                            'grace_period_end' => $graceEnd,
                            'status' => 'unpaid',
                        ]
                    );
                    $count++;
                }
            });

        return $count;
    }

    public function applyPenalties(): int
    {
        $penaltyRate = config('wcp.penalty_rate', 10);
        $count = 0;

        Invoice::overdue()
            ->where('penalty_applied', false)
            ->with('client')
            ->each(function (Invoice $invoice) use ($penaltyRate, &$count) {
                $penalty = round($invoice->balance * $penaltyRate / 100, 2);
                $invoice->update([
                    'penalty_amount' => $penalty,
                    'penalty_applied' => true,
                    'status' => 'penalized',
                ]);
                $count++;
            });

        return $count;
    }

    public function recalculate(Invoice $invoice): void
    {
        $paid = $invoice->payments()->where('status', 'paid')->sum('amount');
        $balance = max(0, $invoice->amount_due - $paid);
        $status = match(true) {
            $balance <= 0 => 'paid',
            $paid > 0 => 'partial',
            $invoice->isPastGrace() => 'overdue',
            default => 'unpaid',
        };
        $invoice->update([
            'amount_paid' => $paid,
            'balance' => $balance,
            'status' => $status,
            'paid_at' => $balance <= 0 ? now() : null,
        ]);
    }

    public function markAsPaid(Invoice $invoice, ?Payment $payment = null): void
    {
        DB::transaction(function () use ($invoice, $payment) {
            $invoice->update([
                'amount_paid' => $invoice->amount_due,
                'balance' => 0,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            if ($payment) {
                $payment->update(['invoice_id' => $invoice->id]);
            }
        });
    }
}