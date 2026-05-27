<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Staff;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index', [
            'clients' => Client::with('zone', 'clientType')->get(),
            'staff' => Staff::with('user', 'zone')->get(),
            'months' => $this->getLast12Months(),
        ]);
    }

    public function generate(Request $request)
    {
        $type = $request->input('type');
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $data = match($type) {
            'collector_performance' => Staff::with('user', 'zone')
                ->where('role', 'collector')
                ->get()
                ->map(fn($staff) => [
                    'staff' => $staff,
                    'stats' => app(ReportService::class)->collectorPerformance($staff->id, $month, $year),
                ]),
            'monthly_company' => [app(ReportService::class)->monthlyCompany($month, $year)],
            'client_summary' => Client::with('zone', 'clientType')
                ->with(['invoices' => fn($q) => $q->where('billing_month', $month)->where('billing_year', $year)])
                ->with(['payments' => fn($q) => $q->whereMonth('paid_at', $month)->whereYear('paid_at', $year)])
                ->get(),
            'expense_report' => Expense::with('category', 'staff')
                ->whereMonth('expense_date', $month)
                ->whereYear('expense_date', $year)
                ->get(),
            default => [],
        };

        return Inertia::render('Reports/Show', [
            'type' => $type,
            'month' => $month,
            'year' => $year,
            'data' => $data,
        ]);
    }

    private function getLast12Months(): array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'),
                'month' => $date->month,
                'year' => $date->year,
            ];
        }
        return $months;
    }
}
