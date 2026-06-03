<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CollectionSession;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $compareWith = $request->input('compare', 'previous_month'); // previous_month, previous_year

        // Get comparison period
        $comparePeriod = $this->getComparisonPeriod($year, $month, $compareWith);
        $currentPeriod = ['year' => $year, 'month' => $month];
        $prevPeriod = $comparePeriod;

        // Fetch metrics
        $currentMetrics = $this->getMetrics($currentPeriod['year'], $currentPeriod['month']);
        $previousMetrics = $this->getMetrics($prevPeriod['year'], $prevPeriod['month']);

        // Calculate changes
        $revenueChange = $this->percentChange($previousMetrics['totalRevenue'], $currentMetrics['totalRevenue']);
        $collectionRateChange = $currentMetrics['collectionRate'] - $previousMetrics['collectionRate'];
        $debtChange = $this->percentChange($previousMetrics['outstandingDebt'], $currentMetrics['outstandingDebt']);

        // Revenue trend (last 12 months)
        $revenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $rev = Payment::whereMonth('paid_at', $date->month)->whereYear('paid_at', $date->year)->sum('amount') ?? 0;
            $revenueTrend[] = ['month' => $date->format('M Y'), 'revenue' => (float) $rev];
        }

        // Collection by zone
        $zones = \App\Models\Zone::all();
        $collectionByZone = $zones->map(fn($zone) => [
            'zone' => $zone->name,
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
                'name' => $staff->user?->name ?? 'Unknown',
                'collections' => (float) CollectionSession::where('staff_id', $staff->id)
                    ->whereMonth('session_date', $month)->whereYear('session_date', $year)
                    ->sum('actual_amount'),
                'zone' => $staff->zone?->name,
            ])
            ->sortByDesc('collections')
            ->values()
            ->take(10);

        // Payment method distribution
        $paymentMethods = Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn($pm) => ['method' => $pm->payment_method, 'total' => (float) $pm->total]);

        // Client retention (new vs returning)
        $newClientsCount = Client::whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
        $returningClientsCount = Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)
            ->distinct('client_id')->count('client_id') - $newClientsCount;

        return Inertia::render('Analytics', [
            'metrics' => [
                'totalRevenue' => (float) $currentMetrics['totalRevenue'],
                'revenueChange' => (float) $revenueChange,
                'collectionRate' => (float) $currentMetrics['collectionRate'],
                'collectionRateChange' => (float) $collectionRateChange,
                'activeClients' => $currentMetrics['activeClients'],
                'newClients' => $currentMetrics['newClients'],
                'outstandingDebt' => (float) $currentMetrics['outstandingDebt'],
                'debtChange' => (float) $debtChange,
            ],
            'revenueTrend' => $revenueTrend,
            'collectionByZone' => $collectionByZone,
            'topCollectors' => $topCollectors,
            'paymentMethods' => $paymentMethods,
            'retention' => [
                'new_clients' => $newClientsCount,
                'returning_clients' => $returningClientsCount,
            ],
            'period' => ['month' => $month, 'year' => $year],
            'comparePeriod' => $comparePeriod,
        ]);
    }

    private function getMetrics($year, $month): array
    {
        $cacheKey = "analytics_metrics_{$year}_{$month}";
        return Cache::remember($cacheKey, 3600, function () use ($year, $month) {
            $totalRevenue = Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)->sum('amount') ?? 0;
            $totalPlanned = CollectionSession::whereMonth('session_date', $month)->whereYear('session_date', $year)->sum('planned_amount') ?? 0;
            $totalCollected = CollectionSession::whereMonth('session_date', $month)->whereYear('session_date', $year)->sum('actual_amount') ?? 0;
            $collectionRate = $totalPlanned > 0 ? ($totalCollected / $totalPlanned) * 100 : 0;
            $activeClients = Client::where('status', 'active')->count();
            $newClients = Client::whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $outstandingDebt = Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance') ?? 0;

            return compact('totalRevenue', 'collectionRate', 'activeClients', 'newClients', 'outstandingDebt');
        });
    }

    private function getComparisonPeriod($year, $month, $compareWith): array
    {
        if ($compareWith === 'previous_year') {
            return ['year' => $year - 1, 'month' => $month];
        }
        // default previous month
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        return ['year' => $prevYear, 'month' => $prevMonth];
    }

    private function percentChange($old, $new): float
    {
        if ($old == 0) return $new > 0 ? 100 : 0;
        return round(($new - $old) / $old * 100, 1);
    }

    public function export(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $format = $request->input('format', 'csv');

        $metrics = $this->getMetrics($year, $month);
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $rev = Payment::whereMonth('paid_at', $date->month)->whereYear('paid_at', $date->year)->sum('amount');
            $trend[] = ['month' => $date->format('M Y'), 'revenue' => (float) $rev];
        }

        if ($format === 'csv') {
            $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=analytics_{$year}_{$month}.csv"];
            $callback = function () use ($metrics, $trend, $year, $month) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ["Analytics Report - $month/$year"]);
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
            };
            return response()->stream($callback, 200, $headers);
        }
        return back()->with('error', 'Unsupported format');
    }
}