<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CollectionSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\CollectionSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'monthly');

        [$start, $end] = $this->getPeriodRange($period);
        [$prevStart, $prevEnd] = $this->getPreviousPeriodRange($period);

        // ── Core stats ────────────────────────────────────────────────────────
        $totalCollected = Payment::whereBetween('paid_at', [$start, $end])
            ->where('status', 'paid')
            ->sum('amount');

        $prevCollected = Payment::whereBetween('paid_at', [$prevStart, $prevEnd])
            ->where('status', 'paid')
            ->sum('amount');

        $totalTransactions = Payment::whereBetween('paid_at', [$start, $end])
            ->where('status', 'paid')
            ->count();

        $prevTransactions = Payment::whereBetween('paid_at', [$prevStart, $prevEnd])
            ->where('status', 'paid')
            ->count();

        $totalOutstanding = Invoice::whereIn('status', ['unpaid', 'partial', 'overdue', 'penalized'])
            ->sum('balance');

        $totalPenalties = Invoice::where('penalty_applied', true)
            ->where('billing_year', now()->year)
            ->sum('penalty_amount');

        $currentMonthYear = (int)(now()->format('Ym'));
        $clientsUnpaid = Invoice::whereIn('status', ['unpaid', 'overdue', 'penalized'])
            ->where('billing_month', $currentMonthYear)
            ->distinct('client_id')
            ->count('client_id');

        $totalInvoiced = Invoice::where('billing_month', $currentMonthYear)
            ->sum('amount_due');

        $collectionRate = $totalInvoiced > 0
            ? round($totalCollected / $totalInvoiced * 100, 1)
            : 0;

        // ── Trend chart data ──────────────────────────────────────────────────
        $chartData = $this->buildChartData($start, $end, $period);

        // ── Amount band chart ─────────────────────────────────────────────────
        $bandData = $this->buildBandData($start, $end);

        // ── Recent transactions ───────────────────────────────────────────────
        $recentTransactions = Payment::with(['client', 'staff.user'])
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(6)
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'payerName'      => $p->payer_name ?? $p->client?->name ?? 'Unknown',
                'controlNumber'  => $p->control_number,
                'amount'         => (float) $p->amount,
                'status'         => $p->status,
                'paidAt'         => $p->paid_at?->toDateTimeString(),
            ]);

        // ── Collector performance ─────────────────────────────────────────────
        $collectors = Staff::with('user')
            ->where('role', 'collector')
            ->where('is_active', true)
            ->get()
            ->map(function ($staff) use ($start, $end) {
                $collected = Payment::where('staff_id', $staff->id)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$start, $end])
                    ->sum('amount');

                $transactions = Payment::where('staff_id', $staff->id)
                    ->whereBetween('paid_at', [$start, $end])
                    ->count();

                return [
                    'name'         => $staff->user?->name ?? 'Unknown',
                    'collected'    => (float) $collected,
                    'transactions' => $transactions,
                    'zone'         => $staff->zone?->name ?? 'Unassigned',
                    'target'       => 1200000,
                ];
            })
            ->sortByDesc('collected')
            ->values();

        // ── This week's schedule ──────────────────────────────────────────────
        $weekStart   = Carbon::now()->startOfWeek();
        $weekEnd     = Carbon::now()->endOfWeek();
        $weekSchedule = $this->buildWeekSchedule($weekStart, $weekEnd);

        // ── Period label for display ──────────────────────────────────────────
        $periodLabel = $this->getPeriodLabel($period, $start, $end);

        return Inertia::render('Dashboard', [
            'period' => $period,
            'periodLabel' => $periodLabel,

            'stats' => [
                'total_collected'   => (float) $totalCollected,
                'total_transactions'=> $totalTransactions,
                'total_outstanding' => (float) $totalOutstanding,
                'total_penalties'   => (float) $totalPenalties,
                'clients_unpaid'    => $clientsUnpaid,
                'collection_rate'   => $collectionRate,
                'collected_change'  => $this->percentChange($prevCollected, $totalCollected),
                'tx_change'         => $this->percentChange($prevTransactions, $totalTransactions),
            ],

            'chartData' => $chartData,
            'bandData'  => $bandData,

            'recentTransactions' => $recentTransactions,
            'collectors'         => $collectors,
            'weekSchedule'       => $weekSchedule,

            'totals' => [
                'active_clients' => Client::where('status', 'active')->count(),
                'total_clients'  => Client::count(),
                'monthly_target' => Staff::where('role','collector')->count() * 1200000,
            ],
        ]);
    }

    // ─── Period helpers ───────────────────────────────────────────────────────

    private function getPeriodRange(string $period): array
    {
        return match ($period) {
            'weekly'  => [
                Carbon::now()->startOfWeek()->startOfDay(),
                Carbon::now()->endOfWeek()->endOfDay(),
            ],
            'yearly'  => [
                Carbon::now()->startOfYear()->startOfDay(),
                Carbon::now()->endOfYear()->endOfDay(),
            ],
            default   => [ // monthly
                Carbon::now()->startOfMonth()->startOfDay(),
                Carbon::now()->endOfMonth()->endOfDay(),
            ],
        };
    }

    private function getPreviousPeriodRange(string $period): array
    {
        return match ($period) {
            'weekly'  => [
                Carbon::now()->subWeek()->startOfWeek()->startOfDay(),
                Carbon::now()->subWeek()->endOfWeek()->endOfDay(),
            ],
            'yearly'  => [
                Carbon::now()->subYear()->startOfYear()->startOfDay(),
                Carbon::now()->subYear()->endOfYear()->endOfDay(),
            ],
            default   => [
                Carbon::now()->subMonth()->startOfMonth()->startOfDay(),
                Carbon::now()->subMonth()->endOfMonth()->endOfDay(),
            ],
        };
    }

    private function getPeriodLabel(string $period, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'weekly' => 'Week ' . $start->weekOfYear . ' · '
                . $start->format('M d') . ' – ' . $end->format('M d, Y'),
            'yearly' => $start->format('Y'),
            default  => $start->format('F Y'),
        };
    }

    // ─── Chart builders ───────────────────────────────────────────────────────

    private function buildChartData(Carbon $start, Carbon $end, string $period): array
    {
        // Group format depends on period
        $groupBy = match ($period) {
            'yearly' => "TO_CHAR(paid_at, 'YYYY-MM')",
            default  => "TO_CHAR(paid_at, 'YYYY-MM-DD')",
        };

        $rows = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw("{$groupBy} as period_key, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('period_key')
            ->orderBy('period_key')
            ->get()
            ->keyBy('period_key');

        // Build complete date spine (no gaps)
        $labels = [];
        $amounts = [];
        $counts  = [];

        if ($period === 'yearly') {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $labels[]  = $cursor->format('M');
                $amounts[] = (float) ($rows[$key]->total ?? 0);
                $counts[]  = (int)   ($rows[$key]->count ?? 0);
                $cursor->addMonth();
            }
        } else {
            // Daily spine
            $cursor = $start->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m-d');
                $dayLabel  = $period === 'monthly'
                    ? $cursor->format('M d')
                    : $cursor->format('D M d');
                $labels[]  = $dayLabel;
                $amounts[] = (float) ($rows[$key]->total ?? 0);
                $counts[]  = (int)   ($rows[$key]->count ?? 0);
                $cursor->addDay();
            }
        }

        return compact('labels', 'amounts', 'counts');
    }

    private function buildBandData(Carbon $start, Carbon $end): array
    {
        $bands = [
            ['label' => '3,000',         'min' => 0,      'max' => 3000  ],
            ['label' => '6,000',         'min' => 3001,   'max' => 6000  ],
            ['label' => '7k – 15k',      'min' => 6001,   'max' => 15000 ],
            ['label' => '15k – 50k',     'min' => 15001,  'max' => 50000 ],
            ['label' => '50k+',          'min' => 50001,  'max' => PHP_INT_MAX ],
        ];

        $total = Payment::whereBetween('paid_at', [$start, $end])->count() ?: 1;

        $data = [];
        foreach ($bands as $band) {
            $count = Payment::whereBetween('paid_at', [$start, $end])
                ->whereBetween('amount', [$band['min'], min($band['max'], 999999999)])
                ->count();
            $data[] = [
                'label'   => $band['label'],
                'count'   => $count,
                'percent' => round($count / $total * 100, 1),
            ];
        }

        return $data;
    }

    // ─── Week schedule ────────────────────────────────────────────────────────

    private function buildWeekSchedule(Carbon $weekStart, Carbon $weekEnd): array
    {
        // Try to load from schedules table, fall back to zone/staff assignments
        $schedules = CollectionSchedule::with(['zone', 'staff.user'])
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            // Show staff + zone assignments if no schedules configured
            return Staff::with(['user', 'zone'])
                ->where('role', 'collector')
                ->where('is_active', true)
                ->limit(7)
                ->get()
                ->map(fn($s) => [
                    'dayLabel'   => Carbon::now()->startOfWeek()->addDays($s->id % 5)->format('l · \W\e\e\k W'),
                    'zoneName'   => $s->zone?->name ?? 'Zone TBA',
                    'zoneColor'  => $s->zone?->color ?? '#4caf76',
                    'staffName'  => $s->user?->name ?? 'Unassigned',
                    'clientCount'=> $s->zone
                        ? Client::where('zone_id', $s->zone_id)->where('status','active')->count()
                        : 0,
                ])
                ->values()
                ->toArray();
        }

        return $schedules->map(fn($s) => [
            'dayLabel'    => implode(', ', array_map(
                fn($d) => Carbon::now()->startOfWeek()->addDays($d - 1)->format('l'),
                json_decode($s->days_of_week, true) ?? []
            )) . ' · Week ' . now()->weekOfYear,
            'zoneName'    => $s->zone?->name ?? 'Zone TBA',
            'zoneColor'   => $s->zone?->color ?? '#4caf76',
            'staffName'   => $s->staff?->user?->name ?? 'Unassigned',
            'clientCount' => $s->zone
                ? Client::where('zone_id', $s->zone_id)->where('status','active')->count()
                : 0,
        ])->values()->toArray();
    }

    // ─── Utilities ────────────────────────────────────────────────────────────

    private function percentChange(float $prev, float $current): float
    {
        if ($prev == 0) return $current > 0 ? 100 : 0;
        return round(($current - $prev) / $prev * 100, 1);
    }

    // ─── Export monthly report ────────────────────────────────────────────────

    public function exportMonthly(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year',  now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        return response()->streamDownload(function () use ($start, $end, $month, $year) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            $i = 1;

            fputcsv($handle, ['WASTE COLLECTION PORTAL — Monthly Report']);
            fputcsv($handle, ['Period:', Carbon::create($year, $month)->format('F Y')]);
            fputcsv($handle, ['Generated:', now()->format('d M Y H:i')]);
            fputcsv($handle, []);

            // Summary
            $total      = Payment::whereBetween('paid_at', [$start, $end])->sum('amount');
            $txCount    = Payment::whereBetween('paid_at', [$start, $end])->count();
            $invoiced   = Invoice::where('billing_month', $month)->where('billing_year', $year)->sum('amount_due');
            $outstanding = Invoice::where('billing_month', $month)->where('billing_year', $year)->sum('balance');

            fputcsv($handle, ['--- SUMMARY ---']);
            fputcsv($handle, ['Total Invoiced (TZS)', number_format($invoiced, 2)]);
            fputcsv($handle, ['Total Collected (TZS)', number_format($total, 2)]);
            fputcsv($handle, ['Outstanding (TZS)', number_format($outstanding, 2)]);
            fputcsv($handle, ['Collection Rate', round($invoiced > 0 ? $total / $invoiced * 100 : 0, 1) . '%']);
            fputcsv($handle, ['Total Transactions', $txCount]);
            fputcsv($handle, []);

            // Transactions
            fputcsv($handle, ['--- TRANSACTIONS ---']);
            fputcsv($handle, ['No.', 'Control Number', 'Payer Name', 'Client Number', 'Amount (TZS)', 'Collector', 'Receipt', 'Date & Time']);

            Payment::with(['client', 'staff.user', 'collectionSession'])
                ->whereBetween('paid_at', [$start, $end])
                ->where('status', 'paid')
                ->orderBy('paid_at')
                ->chunk(200, function ($payments) use ($handle, &$i) {
                    foreach ($payments as $p) {
                        fputcsv($handle, [
                            $i++,
                            $p->control_number,
                            $p->payer_name ?? $p->client?->name,
                            $p->client?->client_number,
                            number_format($p->amount, 2),
                            $p->staff?->user?->name,
                            $p->collectionSession?->session_reference,
                            $p->paid_at?->format('d M Y H:i:s'),
                        ]);
                    }
                });

            fputcsv($handle, []);
            fputcsv($handle, ['--- COLLECTOR PERFORMANCE ---']);
            fputcsv($handle, ['Collector', 'Transactions', 'Total Collected (TZS)', 'Zone']);

            Staff::with(['user', 'zone'])
                ->where('role', 'collector')
                ->get()
                ->each(function ($s) use ($handle, $start, $end) {
                    $collected = Payment::where('staff_id', $s->id)
                        ->whereBetween('paid_at', [$start, $end])
                        ->sum('amount');
                    $txCount = Payment::where('staff_id', $s->id)
                        ->whereBetween('paid_at', [$start, $end])
                        ->count();
                    fputcsv($handle, [
                        $s->user?->name,
                        $txCount,
                        number_format($collected, 2),
                        $s->zone?->name ?? 'N/A',
                    ]);
                });

            fclose($handle);
        }, "monthly-report-{$year}-{$month}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ─── Flag non-payers (redirect to debts with current month filter) ─────────

    public function flagNonPayers()
    {
        return redirect()->route('debts.index', [
            'month'  => now()->format('Y-m'),
            'status' => 'active',
        ]);
    }
    public function exportDashboard(Request $request)
{
    $period = $request->get('period', 'monthly');
    [$start, $end] = $this->getPeriodRange($period);

    $payments = Payment::with(['client', 'staff.user'])
        ->whereBetween('paid_at', [$start, $end])
        ->where('status', 'paid')
        ->get();

    $pdf = Pdf::view('pdf.dashboard', [
        'period' => $period,
        'stats' => $this->getStats($start, $end),
        'payments' => $payments,
        'collectors' => $this->getCollectorPerformance($start, $end),
    ])->format('A4')->landscape();

    return $pdf->download("dashboard_{$period}_{$start->format('Y-m-d')}.pdf");
}

private function getStats($start, $end)
{
    $totalCollected = Payment::whereBetween('paid_at', [$start, $end])->sum('amount');
    $totalInvoiced = Invoice::whereBetween('created_at', [$start, $end])->sum('amount_due');
    return [
        'total_collected' => $totalCollected,
        'collection_rate' => $totalInvoiced > 0 ? round($totalCollected / $totalInvoiced * 100, 1) : 0,
        'transactions' => Payment::whereBetween('paid_at', [$start, $end])->count(),
        'active_clients' => Client::where('status', 'active')->count(),
    ];
}

private function getCollectorPerformance($start, $end)
{
    return Staff::where('role', 'collector')->get()->map(function ($staff) use ($start, $end) {
        $collected = Payment::where('staff_id', $staff->id)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
        return [
            'name' => $staff->user?->name,
            'collected' => $collected,
            'target' => 1200000,
        ];
    })->sortByDesc('collected')->values();
}

public function getAlerts()
{
    // Real-time alerts for dashboard widget
    $alerts = [];

    // Vehicles with low fuel (<20%)
    $lowFuelVehicles = Vehicle::where('fuel_level', '<', 20)->count();
    if ($lowFuelVehicles) {
        $alerts[] = ['type' => 'warning', 'message' => "{$lowFuelVehicles} vehicle(s) have low fuel (<20%)."];
    }

    // Vehicles with expiring insurance (within 30 days)
    $expiringInsurance = Vehicle::where('insurance_expiry', '<=', now()->addDays(30))
        ->where('insurance_expiry', '>', now())
        ->count();
    if ($expiringInsurance) {
        $alerts[] = ['type' => 'info', 'message' => "{$expiringInsurance} vehicle(s) have insurance expiring soon."];
    }

    // Overdue invoices (past due date)
    $overdueInvoices = Invoice::where('due_date', '<', now())
        ->whereIn('status', ['unpaid', 'partial'])
        ->count();
    if ($overdueInvoices) {
        $alerts[] = ['type' => 'danger', 'message' => "{$overdueInvoices} invoice(s) are overdue."];
    }

    return response()->json($alerts);
}
}
