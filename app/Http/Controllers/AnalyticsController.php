<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CollectionSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $compareWith = $request->input('compare', '');

        $comparePeriod = $compareWith
            ? $this->getComparisonPeriod($year, $month, $compareWith)
            : null;

        $currentMetrics = $this->getMetrics($year, $month);
        $previousMetrics = $comparePeriod
            ? $this->getMetrics($comparePeriod['year'], $comparePeriod['month'])
            : $this->getMetrics($month == 1 ? $year - 1 : $year, $month == 1 ? 12 : $month - 1);

        $revenueChange = $this->percentChange($previousMetrics['totalRevenue'], $currentMetrics['totalRevenue']);
        $collectionRateChange = $currentMetrics['collectionRate'] - $previousMetrics['collectionRate'];
        $debtChange = $this->percentChange($previousMetrics['outstandingDebt'], $currentMetrics['outstandingDebt']);

        // Revenue trend — last 12 months, split by revenue type
        $revenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            [$ms, $me] = $this->monthRange($date->month, $date->year);

            $base = Payment::query()->paid()->whereBetween('paid_at', [$ms, $me]);
            $revenueTrend[] = [
                'month' => $date->format('M Y'),
                'revenue' => (float) (clone $base)->sum('amount'),
                'household_waste' => (float) (clone $base)->householdWaste()->sum('amount'),
                'market_levy' => (float) (clone $base)->marketLevy()->sum('amount'),
            ];
        }

        // Collection by zone
        $collectionByZone = Zone::orderBy('name')->get()->map(fn ($zone) => [
            'zone' => $zone->name,
            'amount' => (float) CollectionSession::whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->whereHas('staff', fn ($q) => $q->where('zone_id', $zone->id))
                ->sum('actual_amount'),
        ])->filter(fn ($z) => $z['amount'] > 0)->values();

        // Top collectors — based on actual payments received this month
        $topCollectors = Staff::query()->with(['user', 'zone'])
            ->collectors()
            ->get()
            ->map(function ($staff) use ($month, $year) {
                [$ms, $me] = $this->monthRange($month, $year);

                return [
                    'name' => $staff->user?->name ?? 'Unknown',
                    'collections' => (float) Payment::query()->paid()
                        ->where('staff_id', $staff->id)
                        ->whereBetween('paid_at', [$ms, $me])
                        ->sum('amount'),
                    'transactions' => Payment::query()->paid()
                        ->where('staff_id', $staff->id)
                        ->whereBetween('paid_at', [$ms, $me])
                        ->count(),
                    'zone' => $staff->zone?->name,
                ];
            })
            ->filter(fn ($c) => $c['collections'] > 0 || $c['transactions'] > 0)
            ->sortByDesc('collections')
            ->values()
            ->take(10);

        // Payment methods
        [$ms, $me] = $this->monthRange($month, $year);
        $paymentMethods = Payment::query()->paid()
            ->whereBetween('paid_at', [$ms, $me])
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($pm) => ['method' => $pm->payment_method, 'total' => (float) $pm->total, 'count' => (int) $pm->count]);

        // ── Revenue type breakdown: household waste vs market levy ────────
        $revenueByType = Payment::query()->paid()
            ->whereBetween('paid_at', [$ms, $me])
            ->selectRaw("COALESCE(revenue_type, CASE WHEN amount = 200 THEN 'market_levy' ELSE 'household_waste' END) as rtype")
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('rtype')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->rtype => ['total' => (float) $row->total, 'count' => (int) $row->count],
            ]);

        // Client retention
        $newClientsCount = Client::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)->count();

        $returningClientsCount = Payment::query()->paid()
            ->whereBetween('paid_at', [$ms, $me])
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
                // Revenue type split
                'householdWasteRevenue' => (float) ($revenueByType['household_waste']['total'] ?? 0),
                'marketLevyRevenue' => (float) ($revenueByType['market_levy']['total'] ?? 0),
                'householdWasteCount' => (int) ($revenueByType['household_waste']['count'] ?? 0),
                'marketLevyCount' => (int) ($revenueByType['market_levy']['count'] ?? 0),
                'otherRevenue' => (float) ($revenueByType['other']['total'] ?? 0),
            ],
            'compareMetrics' => $comparePeriod ? $previousMetrics : null,
            'revenueTrend' => $revenueTrend,
            'collectionByZone' => $collectionByZone,
            'topCollectors' => $topCollectors,
            'paymentMethods' => $paymentMethods,
            'retention' => [
                'new_clients' => max(0, $newClientsCount),
                'returning_clients' => max(0, $returningClientsCount),
            ],
            'period' => ['month' => $month, 'year' => $year],
            'comparePeriod' => $comparePeriod,
        ]);
    }

    public function export(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $metrics = $this->getMetrics($year, $month);
        [$ms, $me] = $this->monthRange($month, $year);

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            [$ts, $te] = $this->monthRange($date->month, $date->year);
            $rev = (float) Payment::query()->paid()->whereBetween('paid_at', [$ts, $te])->sum('amount');
            $trend[] = ['month' => $date->format('M Y'), 'revenue' => $rev];
        }

        // Revenue type breakdown for export
        $byType = Payment::query()->paid()
            ->whereBetween('paid_at', [$ms, $me])
            ->selectRaw("COALESCE(revenue_type, CASE WHEN amount = 200 THEN 'market_levy' ELSE 'household_waste' END) as rtype")
            ->selectRaw('SUM(amount) as total, COUNT(*) as count')
            ->groupBy('rtype')
            ->get();

        $typeLabels = [
            'household_waste' => 'Household Waste Collection Fees',
            'market_levy' => 'Ushuru wa Mnada Soko la Kikundi',
            'other' => 'Other Revenue',
        ];

        return response()->stream(function () use ($metrics, $trend, $year, $month, $byType, $typeLabels) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ["Analytics Report - {$month}/{$year}"]);
            fputcsv($file, []);
            fputcsv($file, ['Metric', 'Value']);
            foreach ($metrics as $key => $val) {
                fputcsv($file, [ucwords(str_replace('_', ' ', $key)), is_numeric($val) ? number_format((float) $val, 2) : $val]);
            }
            fputcsv($file, []);
            fputcsv($file, ['--- Revenue by Type ---']);
            fputcsv($file, ['Revenue Type', 'Transactions', 'Total (TZS)']);
            foreach ($byType as $row) {
                fputcsv($file, [$typeLabels[$row->rtype] ?? $row->rtype, $row->count, number_format((float) $row->total, 2)]);
            }
            fputcsv($file, []);
            fputcsv($file, ['Monthly Revenue Trend']);
            fputcsv($file, ['Month', 'Revenue']);
            foreach ($trend as $t) {
                fputcsv($file, [$t['month'], number_format($t['revenue'], 2)]);
            }
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=analytics_{$year}_{$month}.csv",
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    private function getMetrics(int $year, int $month): array
    {
        [$start, $end] = $this->monthRange($month, $year);

        $totalRevenue = (float) Payment::query()->paid()
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $totalPlanned = (float) CollectionSession::whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->sum('planned_amount');

        $totalCollected = (float) CollectionSession::whereMonth('session_date', $month)
            ->whereYear('session_date', $year)
            ->sum('actual_amount');

        $collectionRate = $totalPlanned > 0
            ? ($totalCollected / $totalPlanned) * 100
            : 0;

        return [
            'totalRevenue' => $totalRevenue,
            'collectionRate' => (float) $collectionRate,
            'activeClients' => Client::where('status', 'active')->count(),
            'newClients' => Client::whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),
            'outstandingDebt' => (float) Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance'),
        ];
    }

    private function getComparisonPeriod(int $year, int $month, string $compareWith): array
    {
        if ($compareWith === 'previous_year' || $compareWith === 'last_year') {
            return ['year' => $year - 1, 'month' => $month];
        }
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;

        return ['year' => $prevYear, 'month' => $prevMonth];
    }

    private function percentChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }

        return round(($new - $old) / $old * 100, 1);
    }

    private function monthRange(int $month, int $year): array
    {
        return [
            Carbon::create($year, $month, 1)->startOfDay(),
            Carbon::create($year, $month, 1)->endOfMonth()->endOfDay(),
        ];
    }
}
