<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Staff;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\CollectionSession;
use App\Models\ScheduledReport;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Mail\ReportMail;
use Illuminate\Support\Facades\Mail;

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

    private function getKpiDashboard()
    {
        $cacheKey = 'kpi_dashboard_'.now()->format('Y-m-d');
        return Cache::remember($cacheKey, 3600, function () {
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            return [
                'total_collections_mtd' => CollectionSession::whereBetween('session_date', [$monthStart, $monthEnd])->sum('actual_amount'),
                'total_payments_mtd' => Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount'),
                'collection_efficiency' => $this->calculateEfficiency(),
                'active_collectors' => Staff::where('role', 'collector')->where('is_active', true)->count(),
                'pending_invoices' => \App\Models\Invoice::where('status', 'unpaid')->count(),
            ];
        });
    }

    private function calculateEfficiency()
    {
        $collected = CollectionSession::whereMonth('session_date', now()->month)->sum('actual_amount');
        $planned = CollectionSession::whereMonth('session_date', now()->month)->sum('planned_amount');
        return $planned > 0 ? round(($collected / $planned) * 100, 2) : 0;
    }

    public function generatePdf(Request $request)
    {
        $type = $request->query('type');
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $data = match ($type) {
            'revenue' => $this->getRevenueData($month, $year),
            'collection' => $this->getCollectionData($month, $year),
            'staff' => $this->getStaffPerformanceData($month, $year),
            default => [],
        };

        $pdf = Pdf::view("pdf.reports.{$type}", ['data' => $data, 'month' => $month, 'year' => $year])
            ->format('A4')
            ->landscape();

        return $pdf->download("{$type}_report_{$year}_{$month}.pdf");
    }

    private function getRevenueData($month, $year)
    {
        return [
            'total_paid' => Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)->sum('amount'),
            'by_method' => Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)
                ->select('payment_method', DB::raw('sum(amount) as total'))
                ->groupBy('payment_method')->get(),
        ];
    }

    private function getCollectionData($month, $year)
    {
        return [
            'collections' => CollectionSession::whereMonth('session_date', $month)->whereYear('session_date', $year)
                ->with('staff.user')->get(),
            'total_actual' => CollectionSession::whereMonth('session_date', $month)->whereYear('session_date', $year)->sum('actual_amount'),
            'total_planned' => CollectionSession::whereMonth('session_date', $month)->whereYear('session_date', $year)->sum('planned_amount'),
        ];
    }

    private function getStaffPerformanceData($month, $year)
    {
        return Staff::where('role', 'collector')->get()->map(function ($staff) use ($month, $year) {
            $collections = CollectionSession::where('staff_id', $staff->id)
                ->whereMonth('session_date', $month)->whereYear('session_date', $year)->get();
            return [
                'name' => $staff->user->name,
                'collected' => $collections->sum('actual_amount'),
                'planned' => $collections->sum('planned_amount'),
                'sessions' => $collections->count(),
            ];
        });
    }

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

        $schedule = ScheduledReport::create([
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

    private function getReportDataByType($type)
    {
        // similar to generatePdf but returns array
        return [];
    }

    public function monthlyComparison(Request $request)
    {
        $currentMonth = $request->query('month', now()->month);
        $currentYear = $request->query('year', now()->year);
        $prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
        $prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

        $current = Payment::whereMonth('paid_at', $currentMonth)->whereYear('paid_at', $currentYear)->sum('amount');
        $previous = Payment::whereMonth('paid_at', $prevMonth)->whereYear('paid_at', $prevYear)->sum('amount');

        $growth = $previous > 0 ? round((($current - $previous) / $previous) * 100, 2) : 0;

        return response()->json([
            'current' => $current,
            'previous' => $previous,
            'growth_percent' => $growth,
            'trend' => $growth >= 0 ? 'up' : 'down',
        ]);
    }

    // ... existing daily, weekly, yearly methods remain but you can add caching

    // Daily Reports
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

            $totalPlanned = CollectionSession::whereDate('session_date', $date)
                ->sum('planned_amount') ?? 0;
            $totalCollected = CollectionSession::whereDate('session_date', $date)
                ->sum('actual_amount') ?? 0;
            $totalPayments = Payment::whereDate('paid_at', $date)->sum('amount') ?? 0;
            $completedRoutes = CollectionSession::whereDate('session_date', $date)
                ->where('status', 'completed')->count();
            $pendingRoutes = CollectionSession::whereDate('session_date', $date)
                ->where('status', '!=', 'completed')->count();

            $data = [
                'date' => $date,
                'total_planned' => (float) $totalPlanned,
                'total_collected' => (float) $totalCollected,
                'total_payments' => (float) $totalPayments,
                'completion_rate' => $totalPlanned > 0 
                    ? round(($totalCollected / $totalPlanned) * 100, 2) 
                    : 0,
                'completed_routes' => $completedRoutes,
                'pending_routes' => $pendingRoutes,
                'total_routes' => $completedRoutes + $pendingRoutes,
            ];

            return response()->json($data);
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

            $completed = $sessions->where('status', 'completed')->map(function ($session) {
                return [
                    'staff_name' => $session->staff?->user?->name ?? 'Unknown',
                    'zone' => $session->staff?->zone?->name ?? 'Unassigned',
                    'planned_amount' => (float) ($session->planned_amount ?? 0),
                    'actual_amount' => (float) ($session->actual_amount ?? 0),
                ];
            });

            $notCompleted = $sessions->where('status', '!=', 'completed')->map(function ($session) {
                return [
                    'staff_name' => $session->staff?->user?->name ?? 'Unknown',
                    'zone' => $session->staff?->zone?->name ?? 'Unassigned',
                    'planned_amount' => (float) ($session->planned_amount ?? 0),
                    'actual_amount' => (float) ($session->actual_amount ?? 0),
                    'status' => $session->status,
                ];
            });

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

    // Weekly Reports
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
                $target = $collector->base_salary * 10; // Example target calculation

                return [
                    'staff_name' => $collector->user?->name ?? 'Unknown',
                    'zone' => $collector->zone?->name ?? 'Unassigned',
                    'total_collected' => (float) $totalCollected,
                    'total_planned' => (float) $totalPlanned,
                    'target' => (float) $target,
                    'performance_rate' => $target > 0 
                        ? round(($totalCollected / $target) * 100, 2) 
                        : 0,
                    'sessions_count' => $collections->count(),
                ];
            });

            $bestPerformers = $data->sortByDesc('performance_rate')->take(3)->values();
            $poorPerformers = $data->sortBy('performance_rate')->take(3)->values();

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'all_collectors' => $data,
                'best_performers' => $bestPerformers,
                'poor_performers' => $poorPerformers,
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

            $totalCollected = CollectionSession::whereBetween('session_date', [$startDate, $endDate])
                ->sum('actual_amount') ?? 0;
            $totalPlanned = CollectionSession::whereBetween('session_date', [$startDate, $endDate])
                ->sum('planned_amount') ?? 0;
            $totalRevenue = Payment::whereBetween('paid_at', [$startDate, $endDate])->sum('amount') ?? 0;
            $target = $totalPlanned * 1.1; // Example target: 110% of planned

            $data = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_collected' => (float) $totalCollected,
                'total_planned' => (float) $totalPlanned,
                'total_revenue' => (float) $totalRevenue,
                'target' => (float) $target,
                'performance_vs_target' => $target > 0 
                    ? round(($totalCollected / $target) * 100, 2) 
                    : 0,
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function weeklyFinancialReport(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->startOfWeek()->format('Y-m-d'));
            $endDate = $request->query('end_date', now()->endOfWeek()->format('Y-m-d'));

            $revenue = Payment::whereBetween('paid_at', [$startDate, $endDate])->sum('amount') ?? 0;
            $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount') ?? 0;
            $banked = BankDeposit::whereBetween('deposit_date', [$startDate, $endDate])->sum('amount') ?? 0;
            $debts = Invoice::whereBetween('due_date', [$startDate, $endDate])
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->sum('balance') ?? 0;
            $notPaid = Payment::whereBetween('paid_at', [$startDate, $endDate])
                ->where('status', 'pending')
                ->sum('amount') ?? 0;

            $data = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'revenue' => (float) $revenue,
                'expenses_incurred' => (float) $expenses,
                'expenditures' => (float) $expenses,
                'banked' => (float) $banked,
                'not_paid' => (float) $notPaid,
                'debts' => (float) $debts,
                'penalties' => 0, // Add penalty calculation if needed
                'net_cash_flow' => (float) ($revenue - $expenses),
            ];

            return response()->json($data);
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
                $zoneSessions = $sessions->filter(function ($session) use ($zone) {
                    return $session->staff?->zone_id === $zone->id;
                });

                return [
                    'zone' => $zone->name,
                    'total_sessions' => $zoneSessions->count(),
                    'total_collected' => (float) $zoneSessions->sum('actual_amount'),
                    'total_planned' => (float) $zoneSessions->sum('planned_amount'),
                ];
            });

            $data = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_sessions' => $sessions->count(),
                'total_collected' => (float) $sessions->sum('actual_amount'),
                'total_planned' => (float) $sessions->sum('planned_amount'),
                'by_zone' => $byZone,
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
