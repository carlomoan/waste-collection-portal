<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CollectionSession;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $compareWith = $request->input('compare', '');

        $comparePeriod = $compareWith
            ? $this->getComparisonPeriod($year, $month, $compareWith)
            : null;

        $currentMetrics  = $this->getMetrics($year, $month);
        $previousMetrics = $comparePeriod
            ? $this->getMetrics($comparePeriod['year'], $comparePeriod['month'])
            : $this->getMetrics($month == 1 ? $year - 1 : $year, $month == 1 ? 12 : $month - 1);

        $revenueChange = $this->percentChange($previousMetrics['totalRevenue'], $currentMetrics['totalRevenue']);
        $collectionRateChange = $currentMetrics['collectionRate'] - $previousMetrics['collectionRate'];
        $debtChange = $this->percentChange($previousMetrics['outstandingDebt'], $currentMetrics['outstandingDebt']);

        // Revenue trend — last 12 months
        $revenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $rev  = Payment::whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->where('status', 'paid')
                ->sum('amount');
            $revenueTrend[] = ['month' => $date->format('M Y'), 'revenue' => (float) $rev];
        }

        // Collection by zone
        $zones = \App\Models\Zone::all();
        $collectionByZone = $zones->map(fn($zone) => [
            'zone'   => $zone->name,
            'amount' => (float) CollectionSession::whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->whereHas('staff', fn($q) => $q->where('zone_id', $zone->id))
                ->sum('actual_amount'),
        ]);

        // Top collectors
        $topCollectors = Staff::with('user', 'zone')
            ->where('role', 'collector')
            ->get()
            ->map(fn($staff) => [
                'name'        => $staff->user?->name ?? 'Unknown',
                'collections' => (float) CollectionSession::where('staff_id', $staff->id)
                    ->whereMonth('session_date', $month)
                    ->whereYear('session_date', $year)
                    ->sum('actual_amount'),
                'zone'        => $staff->zone?->name,
            ])
            ->sortByDesc('collections')
            ->values()
            ->take(10);

        // Payment methods
        $paymentMethods = Payment::whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->where('status', 'paid')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn($pm) => ['method' => $pm->payment_method, 'total' => (float) $pm->total]);

        // Client retention
        $newClientsCount = Client::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)->count();

        $returningClientsCount = Payment::whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->distinct('client_id')->count('client_id') - $newClientsCount;

        return Inertia::render('Analytics', [
            'metrics' => [
                'totalRevenue'         => (float) $currentMetrics['totalRevenue'],
                'revenueChange'        => (float) $revenueChange,
                'collectionRate'       => (float) $currentMetrics['collectionRate'],
                'collectionRateChange' => (float) $collectionRateChange,
                'activeClients'        => $currentMetrics['activeClients'],
                'newClients'           => $currentMetrics['newClients'],
                'outstandingDebt'      => (float) $currentMetrics['outstandingDebt'],
                'debtChange'           => (float) $debtChange,
            ],
            'compareMetrics'   => $comparePeriod ? $previousMetrics : null,
            'revenueTrend'     => $revenueTrend,
            'collectionByZone' => $collectionByZone,
            'topCollectors'    => $topCollectors,
            'paymentMethods'   => $paymentMethods,
            'retention'        => [
                'new_clients'       => max(0, $newClientsCount),
                'returning_clients' => max(0, $returningClientsCount),
            ],
            'period'        => ['month' => $month, 'year' => $year],
            'comparePeriod' => $comparePeriod,
        ]);
    }

    public function export(Request $request)
    {
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $metrics = $this->getMetrics($year, $month);

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $rev  = Payment::whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->where('status', 'paid')
                ->sum('amount');
            $trend[] = ['month' => $date->format('M Y'), 'revenue' => (float) $rev];
        }

        return response()->stream(function () use ($metrics, $trend, $year, $month) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ["Analytics Report - {$month}/{$year}"]);
            fputcsv($file, []);
            fputcsv($file, ['Metric', 'Value']);
            foreach ($metrics as $key => $val) {
                fputcsv($file, [$key, $val]);
            }
            fputcsv($file, []);
            fputcsv($file, ['Monthly Revenue Trend']);
            fputcsv($file, ['Month', 'Revenue']);
            foreach ($trend as $t) {
                fputcsv($file, [$t['month'], $t['revenue']]);
            }
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=analytics_{$year}_{$month}.csv",
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function getMetrics(int $year, int $month): array
    {
        $totalRevenue = Payment::whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->where('status', 'paid')
            ->sum('amount');

        $totalPlanned = CollectionSession::whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->sum('planned_amount');

        $totalCollected = CollectionSession::whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->sum('actual_amount');

        $collectionRate = $totalPlanned > 0
            ? ($totalCollected / $totalPlanned) * 100
            : 0;

        return [
            'totalRevenue'    => (float) $totalRevenue,
            'collectionRate'  => (float) $collectionRate,
            'activeClients'   => Client::where('status', 'active')->count(),
            'newClients'      => Client::whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),
            'outstandingDebt' => (float) Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance'),
        ];
    }

    private function getComparisonPeriod(int $year, int $month, string $compareWith): array
    {
        if ($compareWith === 'previous_year' || $compareWith === 'last_year') {
            return ['year' => $year - 1, 'month' => $month];
        }
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear  = $month == 1 ? $year - 1 : $year;
        return ['year' => $prevYear, 'month' => $prevMonth];
    }

    private function percentChange(float $old, float $new): float
    {
        if ($old == 0) return $new > 0 ? 100 : 0;
        return round(($new - $old) / $old * 100, 1);
    }
}
