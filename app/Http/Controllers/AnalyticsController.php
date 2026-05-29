<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Debt;
use App\Models\CollectionSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $lastMonth = now()->subMonth()->month;
            $lastYear = now()->subMonth()->year;

            // Calculate current period metrics
            $currentRevenue = Payment::whereMonth('paid_at', $currentMonth)
                ->whereYear('paid_at', $currentYear)
                ->sum('amount') ?? 0;

            $lastRevenue = Payment::whereMonth('paid_at', $lastMonth)
                ->whereYear('paid_at', $lastYear)
                ->sum('amount') ?? 0;

            $revenueChange = $lastRevenue > 0 ? (($currentRevenue - $lastRevenue) / $lastRevenue) * 100 : 0;

            $totalPlanned = CollectionSession::whereMonth('session_date', $currentMonth)
                ->whereYear('session_date', $currentYear)
                ->sum('planned_amount') ?? 0;

            $totalCollected = CollectionSession::whereMonth('session_date', $currentMonth)
                ->whereYear('session_date', $currentYear)
                ->sum('actual_amount') ?? 0;

            $collectionRate = $totalPlanned > 0 ? ($totalCollected / $totalPlanned) * 100 : 0;

            $lastTotalPlanned = CollectionSession::whereMonth('session_date', $lastMonth)
                ->whereYear('session_date', $lastYear)
                ->sum('planned_amount') ?? 0;

            $lastTotalCollected = CollectionSession::whereMonth('session_date', $lastMonth)
                ->whereYear('session_date', $lastYear)
                ->sum('actual_amount') ?? 0;

            $lastCollectionRate = $lastTotalPlanned > 0 ? ($lastTotalCollected / $lastTotalPlanned) * 100 : 0;

            $collectionRateChange = $collectionRate - $lastCollectionRate;

            $activeClients = Client::where('status', 'active')->count() ?? 0;

            $lastActiveClients = Client::whereMonth('created_at', $lastMonth)
                ->whereYear('created_at', $lastYear)
                ->where('status', 'active')
                ->count() ?? 0;

            $newClients = $activeClients - $lastActiveClients;

            $outstandingDebt = Debt::where('status', 'pending')
                ->where('status', '!=', 'paid')
                ->sum('amount') ?? 0;

            $lastOutstandingDebt = Debt::whereMonth('created_at', $lastMonth)
                ->whereYear('created_at', $lastYear)
                ->where('status', '!=', 'paid')
                ->sum('amount') ?? 0;

            $debtChange = $lastOutstandingDebt > 0 ? (($outstandingDebt - $lastOutstandingDebt) / $lastOutstandingDebt) * 100 : 0;

            // Revenue trend data (last 6 months)
            $revenueTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthRevenue = Payment::whereMonth('paid_at', $date->month)
                    ->whereYear('paid_at', $date->year)
                    ->sum('amount') ?? 0;
                $revenueTrend[] = [
                    'month' => $date->format('M'),
                    'revenue' => (float) $monthRevenue,
                ];
            }

            // Collection by zone data
            $collectionByZone = [];
            $zones = \App\Models\Zone::all();
            foreach ($zones as $zone) {
                $zoneCollections = CollectionSession::whereMonth('session_date', $currentMonth)
                    ->whereYear('session_date', $currentYear)
                    ->whereHas('staff', function ($query) use ($zone) {
                        $query->where('zone_id', $zone->id);
                    })
                    ->sum('actual_amount') ?? 0;
                $collectionByZone[] = [
                    'zone' => $zone->name,
                    'amount' => (float) $zoneCollections,
                ];
            }

            // Top collectors
            $topCollectors = \App\Models\Staff::with('user')
                ->where('role', 'collector')
                ->where('is_active', true)
                ->get()
                ->map(function ($staff) use ($currentMonth, $currentYear) {
                    $collections = CollectionSession::where('staff_id', $staff->id)
                        ->whereMonth('session_date', $currentMonth)
                        ->whereYear('session_date', $currentYear)
                        ->sum('actual_amount') ?? 0;
                    return [
                        'name' => $staff->user?->name ?? 'Unknown',
                        'collections' => (float) $collections,
                        'zone' => $staff->zone?->name ?? 'Unassigned',
                    ];
                })
                ->sortByDesc('collections')
                ->take(5)
                ->values();

            return Inertia::render('Analytics', [
                'metrics' => [
                    'totalRevenue' => (float) $currentRevenue,
                    'revenueChange' => (float) $revenueChange,
                    'collectionRate' => (float) $collectionRate,
                    'collectionRateChange' => (float) $collectionRateChange,
                    'activeClients' => $activeClients,
                    'newClients' => $newClients,
                    'outstandingDebt' => (float) $outstandingDebt,
                    'debtChange' => (float) $debtChange,
                ],
                'revenueTrend' => $revenueTrend,
                'collectionByZone' => $collectionByZone,
                'topCollectors' => $topCollectors,
                'period' => [
                    'month' => $currentMonth,
                    'year' => $currentYear,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load analytics data: ' . $e->getMessage());
        }
    }
}
