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
        try {
            return Inertia::render('Reports/Index', [
                'clients' => Client::with('zone', 'clientType')
                    ->get()
                    ->map(function ($client) {
                        return [
                            'id' => $client->id,
                            'name' => $client->name,
                            'code' => $client->code,
                            'zone' => $client->zone?->name,
                            'type' => $client->clientType?->name,
                            'status' => $client->status,
                        ];
                    }),
                'staff' => Staff::with('user', 'zone')
                    ->get()
                    ->map(function ($staff) {
                        return [
                            'id' => $staff->id,
                            'name' => $staff->user?->name,
                            'phone' => $staff->phone,
                            'zone' => $staff->zone?->name,
                            'role' => $staff->role,
                            'status' => $staff->is_active,
                        ];
                    }),
                'months' => $this->getLast12Months(),
                'zones' => \App\Models\Zone::all()->map(function ($zone) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load reports data: ' . $e->getMessage());
        }
    }

    public function generate(Request $request)
    {
        try {
            $type = $request->input('type');
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $data = match($type) {
                'revenue' => [
                    'totalRevenue' => Payment::whereMonth('paid_at', $month)
                        ->whereYear('paid_at', $year)
                        ->sum('amount') ?? 0,
                    'invoiceRevenue' => Invoice::where('billing_month', $month)
                        ->where('billing_year', $year)
                        ->sum('amount') ?? 0,
                    'collectionRevenue' => CollectionSession::whereMonth('session_date', $month)
                        ->whereYear('session_date', $year)
                        ->sum('actual_amount') ?? 0,
                ],
                'collection' => [
                    'totalSessions' => CollectionSession::whereMonth('session_date', $month)
                        ->whereYear('session_date', $year)
                        ->count() ?? 0,
                    'totalCollected' => CollectionSession::whereMonth('session_date', $month)
                        ->whereYear('session_date', $year)
                        ->sum('actual_amount') ?? 0,
                    'totalPlanned' => CollectionSession::whereMonth('session_date', $month)
                        ->whereYear('session_date', $year)
                        ->sum('planned_amount') ?? 0,
                    'byZone' => \App\Models\Zone::all()->map(function ($zone) use ($month, $year) {
                        return [
                            'zone' => $zone->name,
                            'collected' => CollectionSession::whereMonth('session_date', $month)
                                ->whereYear('session_date', $year)
                                ->whereHas('staff', fn($q) => $q->where('zone_id', $zone->id))
                                ->sum('actual_amount') ?? 0,
                        ];
                    }),
                ],
                'staff' => Staff::with('user', 'zone')
                    ->where('role', 'collector')
                    ->get()
                    ->map(function ($staff) use ($month, $year) {
                        return [
                            'name' => $staff->user?->name ?? 'Unknown',
                            'zone' => $staff->zone?->name ?? 'Unassigned',
                            'collections' => CollectionSession::where('staff_id', $staff->id)
                                ->whereMonth('session_date', $month)
                                ->whereYear('session_date', $year)
                                ->sum('actual_amount') ?? 0,
                            'sessions' => CollectionSession::where('staff_id', $staff->id)
                                ->whereMonth('session_date', $month)
                                ->whereYear('session_date', $year)
                                ->count() ?? 0,
                        ];
                    }),
                'financial' => [
                    'totalExpenses' => Expense::whereMonth('expense_date', $month)
                        ->whereYear('expense_date', $year)
                        ->sum('amount') ?? 0,
                    ],
                default => [],
            };

            return Inertia::render('Reports/Index', [
                'reportData' => $data,
                'selectedReportType' => $type,
                'month' => $month,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
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
