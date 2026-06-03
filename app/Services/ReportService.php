<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\CollectionSession;
use App\Models\Zone;
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
        $invoices = Invoice::where('billing_month', $month)->where('billing_year', $year)->get();
        $payments = Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)->where('status', 'paid')->get();
        $expenses = Expense::where('status', 'approved')
            ->whereMonth('expense_date', $month)->whereYear('expense_date', $year)->sum('amount');

        return [
            'total_invoiced' => $invoices->sum('amount_due'),
            'total_collected' => $payments->sum('amount'),
            'total_unpaid' => $invoices->whereIn('status', ['unpaid','partial','overdue'])->sum('balance'),
            'total_penalties' => $invoices->sum('penalty_amount'),
            'total_expenses' => $expenses,
            'collection_rate' => $invoices->sum('amount_due') > 0
                ? round($payments->sum('amount') / $invoices->sum('amount_due') * 100, 1) : 0,
            'clients_paid' => $invoices->where('status', 'paid')->count(),
            'clients_unpaid' => $invoices->whereIn('status', ['unpaid','overdue','penalized'])->count(),
            'net_revenue' => $payments->sum('amount') - $expenses,
        ];
    }

    public function yearlyCompany(int $year): array
    {
        $invoices = Invoice::where('billing_year', $year)->get();
        $payments = Payment::whereYear('paid_at', $year)->where('status', 'paid')->get();
        $expenses = Expense::where('status', 'approved')->whereYear('expense_date', $year)->sum('amount');

        return [
            'total_invoiced' => $invoices->sum('amount_due'),
            'total_collected' => $payments->sum('amount'),
            'total_expenses' => $expenses,
            'net_revenue' => $payments->sum('amount') - $expenses,
            'collection_rate' => $invoices->sum('amount_due') > 0
                ? round($payments->sum('amount') / $invoices->sum('amount_due') * 100, 1) : 0,
            'monthly_breakdown' => $this->monthlyBreakdown($year),
        ];
    }

    private function monthlyBreakdown(int $year): array
    {
        $breakdown = [];
        for ($month = 1; $month <= 12; $month++) {
            $breakdown[] = [
                'month' => $month,
                'collected' => Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)->where('status', 'paid')->sum('amount'),
                'invoiced' => Invoice::where('billing_month', $month)->where('billing_year', $year)->sum('amount_due'),
            ];
        }
        return $breakdown;
    }

    public function zonePerformance(int $year, ?int $month = null): array
    {
        $zones = Zone::all();
        $performance = [];

        foreach ($zones as $zone) {
            $query = CollectionSession::whereHas('staff', fn($q) => $q->where('zone_id', $zone->id));
            if ($month) {
                $query->whereMonth('session_date', $month)->whereYear('session_date', $year);
            } else {
                $query->whereYear('session_date', $year);
            }
            $collected = $query->sum('actual_amount');
            $planned = $query->sum('planned_amount');

            $performance[] = [
                'zone' => $zone->name,
                'collected' => $collected,
                'planned' => $planned,
                'completion_rate' => $planned > 0 ? round(($collected / $planned) * 100, 2) : 0,
            ];
        }
        return $performance;
    }

    public function collectorLeaderboard(int $year, ?int $month = null, int $limit = 10): array
    {
        $query = Staff::where('role', 'collector')
            ->with('user', 'zone')
            ->withSum(['collectionSessions' => function ($q) use ($year, $month) {
                $q->whereYear('session_date', $year);
                if ($month) $q->whereMonth('session_date', $month);
            }], 'actual_amount');

        return $query->orderByDesc('collection_sessions_sum_actual_amount')
            ->limit($limit)
            ->get()
            ->map(fn($s) => [
                'name' => $s->user?->name,
                'zone' => $s->zone?->name,
                'collected' => (float) $s->collection_sessions_sum_actual_amount,
            ])
            ->toArray();
    }

    public function revenueByClientType(int $year, ?int $month = null): array
    {
        $query = Payment::whereYear('paid_at', $year)->where('status', 'paid')
            ->join('clients', 'payments.client_id', '=', 'clients.id')
            ->join('client_types', 'clients.client_type_id', '=', 'client_types.id')
            ->select('client_types.name', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('client_types.name');

        if ($month) $query->whereMonth('paid_at', $month);
        return $query->get()->toArray();
    }
}