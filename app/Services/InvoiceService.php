<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Debt;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\CollectionSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceService
{
    // Generate invoices for all active clients for a given month
    public function generateMonthlyInvoices(int $month, int $year): int
    {
        $graceConfig = config('wcp.grace_period_days', 15);
        $dueDate = Carbon::create($year, $month)->endOfMonth();
        $graceEnd = $dueDate->copy()->addDays($graceConfig);
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

    // Apply penalty to overdue invoices past grace period
    public function applyPenalties(): int
    {
        $penaltyRate = config('wcp.penalty_rate', 10); // 10%
        $count = 0;

        Invoice::overdue()->where('penalty_applied', false)
               ->with('client')
               ->each(function (Invoice $invoice) use ($penaltyRate, &$count) {
                   $penalty = round($invoice->balance * $penaltyRate / 100, 2);
                   $invoice->update([
                       'penalty_amount' => $penalty,
                       'penalty_applied' => true,
                       'status' => 'penalized',
                   ]);
                   Debt::updateOrCreate(
                       ['invoice_id' => $invoice->id],
                       [
                           'client_id' => $invoice->client_id,
                           'original_amount' => $invoice->amount_due,
                           'outstanding' => $invoice->balance + $penalty,
                           'penalty_amount' => $penalty,
                           'penalty_applied' => true,
                           'penalty_applied_at' => now(),
                       ]
                   );
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
}