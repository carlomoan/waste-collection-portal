<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Staff;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\CollectionSession;
use App\Models\ScheduledReport;
use App\Models\BankDeposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Mail\ReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function index()
    {
        $scheduledReports = ScheduledReport::with('user')->where('is_active', true)->get();

        return Inertia::render('Reports/Index', [
            'clients' => Client::with('zone', 'clientType')->get(),
            'staff' => Staff::with('user', 'zone')->get(),
            'months' => $this->getLast12Months(),
            'zones' => \App\Models\Zone::all(),
            'scheduledReports' => $scheduledReports,
            'kpi' => $this->getKpiDashboard(),
        ]);
    }

    // ─── MISSING METHOD: getLast12Months ────────────────────────────────────
    private function getLast12Months(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
            ];
        }
        return $months;
    }

    // ─── KPI Dashboard ──────────────────────────────────────────────────────
    private function getKpiDashboard(): array
    {
        $cacheKey = 'kpi_dashboard_' . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 3600, function () {
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            return [
                'total_collections_mtd' => CollectionSession::whereBetween('session_date', [$monthStart, $monthEnd])->sum('actual_amount'),
                'total_payments_mtd' => Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->where('status', 'paid')->sum('amount'),
                'collection_efficiency' => $this->calculateEfficiency(),
                'active_collectors' => Staff::where('role', 'collector')->where('is_active', true)->count(),
                'pending_invoices' => Invoice::where('status', 'unpaid')->count(),
            ];
        });
    }

    private function calculateEfficiency(): float
    {
        $collected = CollectionSession::whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->sum('actual_amount');

        $planned = CollectionSession::whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->sum('planned_amount');

        return $planned > 0 ? round(($collected / $planned) * 100, 2) : 0;
    }

    // ─── MISSING METHOD: generate ───────────────────────────────────────────
    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:revenue,collection,staff,financial',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'format' => 'nullable|in:pdf,csv',
        ]);

        $type = $request->input('type');
        $month = $request->input('month');
        $year = $request->input('year');
        $format = $request->input('format', 'pdf');

        if ($format === 'csv') {
            return $this->exportCsv($type, $month, $year);
        }

        return $this->generatePdfReport($type, $month, $year);
    }

    // ─── MISSING METHOD: monthly ────────────────────────────────────────────
    public function monthly(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $payments = Payment::with(['client', 'staff.user'])
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->where('status', 'paid')
            ->get();

        $expenses = Expense::with('category')
            ->whereBetween('expense_date', [$monthStart, $monthEnd])
            ->get();

        $totalRevenue = $payments->sum('amount');
        $totalExpenses = $expenses->sum('amount');

        $dailyRevenue = [];
        for ($d = 1; $d <= $monthEnd->day; $d++) {
            $dayPayments = $payments->filter(fn($p) => $p->paid_at->day === $d);
            $dailyRevenue[] = [
                'day' => $d,
                'amount' => $dayPayments->sum('amount'),
                'count' => $dayPayments->count(),
            ];
        }

        $byCollector = $payments->groupBy(fn($p) => $p->staff?->user?->name ?? 'Unknown')
            ->map(fn($group) => [
                'amount' => $group->sum('amount'),
                'count' => $group->count(),
            ])
            ->sortByDesc('amount')
            ->values()
            ->toArray();

        return Inertia::render('Reports/Monthly', [
            'month' => $month,
            'year' => $year,
            'monthLabel' => Carbon::create($year, $month)->format('F Y'),
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $totalRevenue - $totalExpenses,
            'transactionCount' => $payments->count(),
            'dailyRevenue' => $dailyRevenue,
            'byCollector' => $byCollector,
            'payments' => $payments->take(50),
        ]);
    }

    // ─── MISSING METHOD: yearly ─────────────────────────────────────────────
    public function yearly(Request $request)
    {
        $year = $request->input('year', now()->year);

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = Carbon::create($year, $m, 1)->startOfDay();
            $monthEnd = Carbon::create($year, $m, 1)->endOfMonth()->endOfDay();

            $revenue = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])
                ->where('status', 'paid')->sum('amount');

            $expenses = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');

            $monthlyData[] = [
                'month' => Carbon::create($year, $m)->format('M'),
                'monthNumber' => $m,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'netProfit' => $revenue - $expenses,
            ];
        }

        $totalRevenue = collect($monthlyData)->sum('revenue');
        $totalExpenses = collect($monthlyData)->sum('expenses');

        return Inertia::render('Reports/Yearly', [
            'year' => $year,
            'monthlyData' => $monthlyData,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $totalRevenue - $totalExpenses,
            'bestMonth' => collect($monthlyData)->sortByDesc('revenue')->first(),
            'worstMonth' => collect($monthlyData)->sortBy('revenue')->first(),
        ]);
    }

    // ─── MISSING METHOD: collector ──────────────────────────────────────────
    public function collector(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $staffId = $request->input('staff_id');

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $collectors = Staff::with('user', 'zone')
            ->where('role', 'collector')
            ->when($staffId, fn($q) => $q->where('id', $staffId))
            ->get()
            ->map(function ($staff) use ($monthStart, $monthEnd) {
                $payments = Payment::where('staff_id', $staff->id)
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->where('status', 'paid')
                    ->get();

                $sessions = CollectionSession::where('staff_id', $staff->id)
                    ->whereBetween('session_date', [$monthStart, $monthEnd])
                    ->get();

                return [
                    'id' => $staff->id,
                    'name' => $staff->user?->name ?? 'Unknown',
                    'zone' => $staff->zone?->name ?? 'Unassigned',
                    'totalCollected' => $payments->sum('amount'),
                    'transactionCount' => $payments->count(),
                    'sessionsCompleted' => $sessions->where('status', 'completed')->count(),
                    'plannedAmount' => $sessions->sum('planned_amount'),
                    'efficiency' => $sessions->sum('planned_amount') > 0
                        ? round(($payments->sum('amount') / $sessions->sum('planned_amount')) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('totalCollected')
            ->values();

        return Inertia::render('Reports/Collector', [
            'month' => $month,
            'year' => $year,
            'monthLabel' => Carbon::create($year, $month)->format('F Y'),
            'collectors' => $collectors,
            'totalCollected' => $collectors->sum('totalCollected'),
            'totalTransactions' => $collectors->sum('transactionCount'),
        ]);
    }

    // ─── PDF Generation ─────────────────────────────────────────────────────
    public function generatePdfReport(string $type, int $month, int $year)
    {
        $data = match ($type) {
            'revenue' => $this->getRevenueData($month, $year),
            'collection' => $this->getCollectionData($month, $year),
            'staff' => $this->getStaffPerformanceData($month, $year),
            default => [],
        };

        $pdf = Pdf::view("pdf.reports.{$type}", [
            'data' => $data,
            'month' => $month,
            'year' => $year,
        ])->format('A4')->landscape();

        return $pdf->download("{$type}_report_{$year}_{$month}.pdf");
    }

    // ─── Alias for route compatibility ──────────────────────────────────────
    public function generatePdf(Request $request)
    {
        $type = $request->query('type', 'revenue');
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return $this->generatePdfReport($type, $month, $year);
    }

    private function getRevenueData(int $month, int $year): array
    {
        return [
            'total_paid' => Payment::whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year)
                ->where('status', 'paid')
                ->sum('amount'),
            'by_method' => Payment::whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year)
                ->where('status', 'paid')
                ->select('payment_method', DB::raw('sum(amount) as total'))
                ->groupBy('payment_method')
                ->get(),
            'transactions' => Payment::with('client')
                ->whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year)
                ->where('status', 'paid')
                ->limit(100)
                ->get(),
        ];
    }

    private function getCollectionData(int $month, int $year): array
    {
        return [
            'collections' => CollectionSession::whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->with('staff.user')
                ->get(),
            'total_actual' => CollectionSession::whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->sum('actual_amount'),
            'total_planned' => CollectionSession::whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->sum('planned_amount'),
        ];
    }

    private function getStaffPerformanceData(int $month, int $year): array
    {
        return Staff::where('role', 'collector')->get()->map(function ($staff) use ($month, $year) {
            $collections = CollectionSession::where('staff_id', $staff->id)
                ->whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->get();

            return [
                'name' => $staff->user?->name ?? 'Unknown',
                'collected' => $collections->sum('actual_amount'),
                'planned' => $collections->sum('planned_amount'),
                'sessions' => $collections->count(),
            ];
        })->toArray();
    }

    // ─── MISSING METHOD: exportExcel ────────────────────────────────────────
    public function exportExcel(Request $request)
    {
        $type = $request->query('type', 'revenue');
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return $this->exportCsv($type, $month, $year);
    }

    private function exportCsv(string $type, int $month, int $year)
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        return response()->streamDownload(function () use ($type, $monthStart, $monthEnd, $month, $year) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ["WASTE COLLECTION PORTAL — {$type} Report"]);
            fputcsv($handle, ['Period:', Carbon::create($year, $month)->format('F Y')]);
            fputcsv($handle, ['Generated:', now()->format('d M Y H:i')]);
            fputcsv($handle, []);

            if ($type === 'revenue' || $type === 'financial') {
                fputcsv($handle, ['No.', 'Control #', 'Payer', 'Amount (TZS)', 'Method', 'Date']);
                $i = 1;
                Payment::with('client')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->where('status', 'paid')
                    ->orderBy('paid_at')
                    ->chunk(200, function ($payments) use ($handle, &$i) {
                        foreach ($payments as $p) {
                            fputcsv($handle, [
                                $i++,
                                $p->control_number,
                                $p->payer_name ?? $p->client?->name,
                                number_format($p->amount, 2),
                                $p->payment_method,
                                $p->paid_at?->format('d M Y H:i'),
                            ]);
                        }
                    });

                fputcsv($handle, []);
                fputcsv($handle, ['TOTAL', '', '', number_format(
                    Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->where('status', 'paid')->sum('amount'), 2
                )]);
            } elseif ($type === 'collection') {
                fputcsv($handle, ['Collector', 'Sessions', 'Planned (TZS)', 'Actual (TZS)', 'Efficiency %']);
                Staff::where('role', 'collector')->get()->each(function ($staff) use ($handle, $monthStart, $monthEnd) {
                    $sessions = CollectionSession::where('staff_id', $staff->id)
                        ->whereBetween('session_date', [$monthStart, $monthEnd])->get();
                    $planned = $sessions->sum('planned_amount');
                    $actual = $sessions->sum('actual_amount');
                    fputcsv($handle, [
                        $staff->user?->name,
                        $sessions->count(),
                        number_format($planned, 2),
                        number_format($actual, 2),
                        $planned > 0 ? round(($actual / $planned) * 100, 1) . '%' : 'N/A',
                    ]);
                });
            } elseif ($type === 'staff') {
                fputcsv($handle, ['Collector', 'Zone', 'Transactions', 'Total Collected (TZS)']);
                Staff::with('user', 'zone')->where('role', 'collector')->get()->each(function ($staff) use ($handle, $monthStart, $monthEnd) {
                    $payments = Payment::where('staff_id', $staff->id)
                        ->whereBetween('paid_at', [$monthStart, $monthEnd])
                        ->where('status', 'paid')->get();
                    fputcsv($handle, [
                        $staff->user?->name,
                        $staff->zone?->name ?? 'N/A',
                        $payments->count(),
                        number_format($payments->sum('amount'), 2),
                    ]);
                });
            }

            fclose($handle);
        }, "{$type}_report_{$year}_{$month}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ─── MISSING METHOD: download ───────────────────────────────────────────
    public function download(int $reportId)
    {
        $report = ScheduledReport::findOrFail($reportId);

        $data = $this->getReportDataByType($report->type);

        $pdf = Pdf::view("pdf.reports.{$report->type}", $data)->output();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$report->name}.pdf\"");
    }

    // ─── Scheduled Reports ──────────────────────────────────────────────────
    public function scheduleReport(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
        ]);

        ScheduledReport::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'recipients' => json_encode($validated['recipients']),
            'day_of_month' => $validated['day_of_month'] ?? null,
            'day_of_week' => $validated['day_of_week'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Report scheduled.');
    }

    public function sendNow(ScheduledReport $report)
    {
        $data = $this->getReportDataByType($report->type);

        $pdf = Pdf::view("pdf.reports.{$report->type}", $data)->output();

        foreach (json_decode($report->recipients) as $email) {
            Mail::to($email)->send(new ReportMail($pdf, $report->name));
        }

        return back()->with('success', 'Report sent.');
    }

    private function getReportDataByType(string $type): array
    {
        $month = now()->month;
        $year = now()->year;

        return match ($type) {
            'revenue' => $this->getRevenueData($month, $year),
            'collection' => $this->getCollectionData($month, $year),
            'staff' => $this->getStaffPerformanceData($month, $year),
            default => [],
        };
    }

    // ─── Monthly Comparison ─────────────────────────────────────────────────
    public function monthlyComparison(Request $request)
    {
        $currentMonth = $request->query('month', now()->month);
        $currentYear = $request->query('year', now()->year);

        $prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
        $prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

        $current = Payment::whereMonth('paid_at', $currentMonth)
            ->whereYear('paid_at', $currentYear)
            ->where('status', 'paid')
            ->sum('amount');

        $previous = Payment::whereMonth('paid_at', $prevMonth)
            ->whereYear('paid_at', $prevYear)
            ->where('status', 'paid')
            ->sum('amount');

        $growth = $previous > 0 ? round((($current - $previous) / $previous) * 100, 2) : 0;

        return response()->json([
            'current' => $current,
            'previous' => $previous,
            'growth_percent' => $growth,
            'trend' => $growth >= 0 ? 'up' : 'down',
        ]);
    }

    // ─── Daily Reports ──────────────────────────────────────────────────────
    public function dailyCollectorPerformance(Request $request)
    {
        try {
            $date = $request->query('date', now()->format('Y-m-d'));
            $staffId = $request->query('staff_id');

            $query = CollectionSession::whereDate('session_date', $date)
                ->with('staff.user', 'staff.zone');

            if ($staffId) {
                $query->where('staff_id', $staffId);
            }

            $sessions = $query->get();

            $data = $sessions->map(function ($session) {
                return [
                    'staff_name' => $session->staff?->user?->name ?? 'Unknown',
                    'zone' => $session->staff?->zone?->name ?? 'Unassigned',
                    'planned_amount' => (float) ($session->planned_amount ?? 0),
                    'actual_amount' => (float) ($session->actual_amount ?? 0),
                    'completion_rate' => $session->planned_amount > 0
                        ? round(($session->actual_amount / $session->planned_amount) * 100, 2)
                        : 0,
                    'status' => $session->status,
                ];
            });

            return response()->json(['data' => $data, 'date' => $date]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function dailyCompanyPerformance(Request $request)
    {
        try {
            $date = $request->query('date', now()->format('Y-m-d'));

            $totalPlanned = CollectionSession::whereDate('session_date', $date)->sum('planned_amount') ?? 0;
            $totalCollected = CollectionSession::whereDate('session_date', $date)->sum('actual_amount') ?? 0;
            $totalPayments = Payment::whereDate('paid_at', $date)->where('status', 'paid')->sum('amount') ?? 0;
            $completedRoutes = CollectionSession::whereDate('session_date', $date)->where('status', 'completed')->count();
            $pendingRoutes = CollectionSession::whereDate('session_date', $date)->where('status', '!=', 'completed')->count();

            return response()->json([
                'date' => $date,
                'total_planned' => (float) $totalPlanned,
                'total_collected' => (float) $totalCollected,
                'total_payments' => (float) $totalPayments,
                'completion_rate' => $totalPlanned > 0 ? round(($totalCollected / $totalPlanned) * 100, 2) : 0,
                'completed_routes' => $completedRoutes,
                'pending_routes' => $pendingRoutes,
                'total_routes' => $completedRoutes + $pendingRoutes,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function dailyRoutesReport(Request $request)
    {
        try {
            $date = $request->query('date', now()->format('Y-m-d'));

            $sessions = CollectionSession::whereDate('session_date', $date)
                ->with('staff.user', 'staff.zone')
                ->get();

            $completed = $sessions->where('status', 'completed')->map(fn($session) => [
                'staff_name' => $session->staff?->user?->name ?? 'Unknown',
                'zone' => $session->staff?->zone?->name ?? 'Unassigned',
                'planned_amount' => (float) ($session->planned_amount ?? 0),
                'actual_amount' => (float) ($session->actual_amount ?? 0),
            ]);

            $notCompleted = $sessions->where('status', '!=', 'completed')->map(fn($session) => [
                'staff_name' => $session->staff?->user?->name ?? 'Unknown',
                'zone' => $session->staff?->zone?->name ?? 'Unassigned',
                'planned_amount' => (float) ($session->planned_amount ?? 0),
                'actual_amount' => (float) ($session->actual_amount ?? 0),
                'status' => $session->status,
            ]);

            return response()->json([
                'date' => $date,
                'completed' => $completed,
                'not_completed' => $notCompleted,
                'completed_count' => $completed->count(),
                'not_completed_count' => $notCompleted->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─── Weekly Reports ─────────────────────────────────────────────────────
    public function weeklyCollectorPerformance(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->startOfWeek()->format('Y-m-d'));
            $endDate = $request->query('end_date', now()->endOfWeek()->format('Y-m-d'));

            $collectors = Staff::where('role', 'collector')->with('user', 'zone')->get();

            $data = $collectors->map(function ($collector) use ($startDate, $endDate) {
                $collections = CollectionSession::where('staff_id', $collector->id)
                    ->whereBetween('session_date', [$startDate, $endDate])
                    ->get();

                $totalCollected = $collections->sum('actual_amount') ?? 0;
                $totalPlanned = $collections->sum('planned_amount') ?? 0;
                $target = $collector->base_salary * 10;

                return [
                    'staff_name' => $collector->user?->name ?? 'Unknown',
                    'zone' => $collector->zone?->name ?? 'Unassigned',
                    'total_collected' => (float) $totalCollected,
                    'total_planned' => (float) $totalPlanned,
                    'target' => (float) $target,
                    'performance_rate' => $target > 0 ? round(($totalCollected / $target) * 100, 2) : 0,
                    'sessions_count' => $collections->count(),
                ];
            });

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'all_collectors' => $data,
                'best_performers' => $data->sortByDesc('performance_rate')->take(3)->values(),
                'poor_performers' => $data->sortBy('performance_rate')->take(3)->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function weeklyCompanyPerformance(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->startOfWeek()->format('Y-m-d'));
            $endDate = $request->query('end_date', now()->endOfWeek()->format('Y-m-d'));

            $totalCollected = CollectionSession::whereBetween('session_date', [$startDate, $endDate])->sum('actual_amount') ?? 0;
            $totalPlanned = CollectionSession::whereBetween('session_date', [$startDate, $endDate])->sum('planned_amount') ?? 0;
            $totalRevenue = Payment::whereBetween('paid_at', [$startDate, $endDate])->where('status', 'paid')->sum('amount') ?? 0;
            $target = $totalPlanned * 1.1;

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_collected' => (float) $totalCollected,
                'total_planned' => (float) $totalPlanned,
                'total_revenue' => (float) $totalRevenue,
                'target' => (float) $target,
                'performance_vs_target' => $target > 0 ? round(($totalCollected / $target) * 100, 2) : 0,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function weeklyFinancialReport(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->startOfWeek()->format('Y-m-d'));
            $endDate = $request->query('end_date', now()->endOfWeek()->format('Y-m-d'));

            $revenue = Payment::whereBetween('paid_at', [$startDate, $endDate])->where('status', 'paid')->sum('amount') ?? 0;
            $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount') ?? 0;
            $banked = BankDeposit::whereBetween('deposit_date', [$startDate, $endDate])->sum('amount') ?? 0;
            $debts = Invoice::whereBetween('due_date', [$startDate, $endDate])
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->sum('balance') ?? 0;
            $notPaid = Payment::whereBetween('paid_at', [$startDate, $endDate])
                ->where('status', 'pending')
                ->sum('amount') ?? 0;

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'revenue' => (float) $revenue,
                'expenses_incurred' => (float) $expenses,
                'expenditures' => (float) $expenses,
                'banked' => (float) $banked,
                'not_paid' => (float) $notPaid,
                'debts' => (float) $debts,
                'penalties' => 0,
                'net_cash_flow' => (float) ($revenue - $expenses),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function weeklyWasteCollectionReport(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->startOfWeek()->format('Y-m-d'));
            $endDate = $request->query('end_date', now()->endOfWeek()->format('Y-m-d'));

            $sessions = CollectionSession::whereBetween('session_date', [$startDate, $endDate])
                ->with('staff.user', 'staff.zone')
                ->get();

            $byZone = \App\Models\Zone::all()->map(function ($zone) use ($sessions) {
                $zoneSessions = $sessions->filter(fn($session) => $session->staff?->zone_id === $zone->id);

                return [
                    'zone' => $zone->name,
                    'total_sessions' => $zoneSessions->count(),
                    'total_collected' => (float) $zoneSessions->sum('actual_amount'),
                    'total_planned' => (float) $zoneSessions->sum('planned_amount'),
                ];
            });

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_sessions' => $sessions->count(),
                'total_collected' => (float) $sessions->sum('actual_amount'),
                'total_planned' => (float) $sessions->sum('planned_amount'),
                'by_zone' => $byZone,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}