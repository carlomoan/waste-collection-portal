<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\CollectionSession;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function collectorPerformance(int $staffId, int $month, int $year): array
    {
        $staff = Staff::with('user')->findOrFail($staffId);
        $sessions = CollectionSession::where('staff_id', $staffId)
            ->whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->with('payments')
            ->get();

        return [
            'staff' => $staff,
            'total_collected' => $sessions->sum('actual_amount'),
            'total_transactions' => $sessions->flatMap->payments->count(),
            'sessions_count' => $sessions->count(),
            'daily_breakdown' => $sessions->groupBy(fn($s) => $s->session_date->format('Y-m-d'))
                ->map(fn($g) => $g->sum('actual_amount')),
            'average_per_transaction' => $sessions->flatMap->payments->avg('amount'),
        ];
    }

    public function monthlyCompany(int $month, int $year): array
    {
        $invoices = Invoice::where('billing_month', $month)
                           ->where('billing_year', $year)
                           ->get();
        $payments = Payment::whereMonth('paid_at', $month)
                           ->whereYear('paid_at', $year)
                           ->where('status', 'paid')
                           ->get();
        $expenses = Expense::where('status', 'approved')
                           ->whereMonth('expense_date', $month)
                           ->whereYear('expense_date', $year)
                           ->sum('amount');

        return [
            'total_invoiced' => $invoices->sum('amount_due'),
            'total_collected' => $payments->sum('amount'),
            'total_unpaid' => $invoices->whereIn('status', ['unpaid','partial','overdue'])->sum('balance'),
            'total_penalties' => $invoices->sum('penalty_amount'),
            'total_expenses' => $expenses,
            'collection_rate' => $invoices->sum('amount_due') > 0
                ? round($payments->sum('amount') / $invoices->sum('amount_due') * 100, 1)
                : 0,
            'clients_paid' => $invoices->where('status', 'paid')->count(),
            'clients_unpaid' => $invoices->whereIn('status', ['unpaid','overdue','penalized'])->count(),
            'net_revenue' => $payments->sum('amount') - $expenses,
        ];
    }
}