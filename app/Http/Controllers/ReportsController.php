<?php

namespace App\Http\Controllers;

use App\Mail\ReportMail;
use App\Models\BankDeposit;
use App\Models\Client;
use App\Models\CollectionSession;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ScheduledReport;
use App\Models\Staff;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class ReportsController extends Controller
{
    /** All available report types shown on the Reports page. */
    public const REPORT_TYPES = [
        'revenue' => 'Revenue Report',
        'collection' => 'Collection Report',
        'staff' => 'Staff Performance Report',
        'financial' => 'Financial Report (P&L)',
        'debts' => 'Debts & Outstanding Report',
        'clients' => 'Clients Report',
        'banking' => 'Banking & Deposits Report',
    ];

    // ─── Index ──────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $scheduledReports = ScheduledReport::with('user')->where('is_active', true)->get();

        return Inertia::render('Reports/Index', [
            'reportTypes' => self::REPORT_TYPES,
            'staff' => Staff::query()->with('user', 'zone')->get(),
            'months' => $this->getLast12Months(),
            'zones' => Zone::orderBy('name')->get(['id', 'name']),
            'scheduledReports' => $scheduledReports->map(fn (ScheduledReport $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'type' => $r->type,
                'type_label' => self::REPORT_TYPES[$r->type] ?? $r->type,
                'frequency' => $r->frequency,
                'recipients' => implode(', ', $r->recipients ?? []),
                'is_active' => $r->is_active,
                'last_sent_at' => $r->last_sent_at?->toDateTimeString(),
            ]),
            'kpi' => $this->getKpiDashboard(),
        ]);
    }

    private function getLast12Months(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'month' => (int) $date->format('n'),
                'year' => (int) $date->format('Y'),
                'label' => $date->format('M Y'),
            ];
        }

        return $months;
    }

    private function getKpiDashboard(): array
    {
        return Cache::remember('kpi_dashboard_'.now()->format('Y-m-d'), 3600, function () {
            [$monthStart, $monthEnd] = $this->monthRange(now()->month, now()->year);

            return [
                'total_collections_mtd' => (float) CollectionSession::whereBetween('session_date', [$monthStart, $monthEnd])->sum('actual_amount'),
                'total_payments_mtd' => (float) Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->paid()->sum('amount'),
                'collection_efficiency' => $this->calculateEfficiency(),
                'active_collectors' => Staff::query()->collectors()->active()->count(),
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

    // ─── Generate / Export entry point ──────────────────────────────────────

    /**
     * Unified generate endpoint: view in browser or download as CSV / PDF.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:'.implode(',', array_keys(self::REPORT_TYPES)),
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2035',
            'format' => 'nullable|in:view,pdf,csv',
            'zone_id' => 'nullable|exists:zones,id',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $type = $validated['type'];
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];
        $format = $validated['format'] ?? 'view';
        $filters = [
            'zone_id' => $validated['zone_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
        ];

        return match ($format) {
            'csv' => $this->exportCsv($type, $month, $year, $filters),
            'pdf' => $this->downloadPdf($type, $month, $year, $filters),
            default => redirect()->route('reports.show', [
                'type' => $type, 'month' => $month, 'year' => $year,
                'zone_id' => $filters['zone_id'], 'staff_id' => $filters['staff_id'],
            ]),
        };
    }

    /**
     * Render any report type in the browser.
     */
    public function show(Request $request): Response
    {
        $type = $request->query('type', 'revenue');
        abort_unless(array_key_exists($type, self::REPORT_TYPES), 404);

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $filters = [
            'zone_id' => $request->query('zone_id'),
            'staff_id' => $request->query('staff_id'),
        ];

        $data = $this->buildReport($type, $month, $year, $filters);

        return Inertia::render('Reports/Show', [
            'reportType' => $type,
            'reportLabel' => self::REPORT_TYPES[$type],
            'month' => $month,
            'year' => $year,
            'monthLabel' => Carbon::create($year, $month, 1)->format('F Y'),
            'filters' => $filters,
            'zones' => Zone::orderBy('name')->get(['id', 'name']),
            'staff' => Staff::query()->with('user:id,name')->collectors()->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name ?? "Staff #{$s->id}"]),
            'data' => $data,
        ]);
    }

    // ─── Report data builder (shared by view / CSV / PDF) ──────────────────

    private function buildReport(string $type, int $month, int $year, array $filters = []): array
    {
        [$start, $end] = $this->monthRange($month, $year);

        return match ($type) {
            'revenue' => $this->revenueReport($start, $end, $filters),
            'collection' => $this->collectionReport($start, $end, $filters),
            'staff' => $this->staffReport($start, $end, $filters),
            'financial' => $this->financialReport($start, $end),
            'debts' => $this->debtsReport($start, $end),
            'clients' => $this->clientsReport($filters),
            'banking' => $this->bankingReport($start, $end),
            default => [],
        };
    }

    private function revenueReport(Carbon $start, Carbon $end, array $filters = []): array
    {
        $payments = Payment::query()
            ->paid()
            ->with(['client.zone', 'staff.user'])
            ->whereBetween('paid_at', [$start, $end])
            ->when($filters['zone_id'] ?? null, fn ($q, $z) => $q->whereHas('client', fn ($c) => $c->where('zone_id', $z)))
            ->when($filters['staff_id'] ?? null, fn ($q, $s) => $q->where('staff_id', $s))
            ->orderBy('paid_at')
            ->get();

        $byMethod = $payments->groupBy('payment_method')
            ->map(fn ($g) => ['method' => $g->first()->payment_method, 'total' => (float) $g->sum('amount'), 'count' => $g->count()])
            ->values();

        $byZone = $payments->groupBy(fn ($p) => $p->client?->zone?->name ?? 'Unassigned')
            ->map(fn ($g) => ['label' => $g->first()->client?->zone?->name ?? 'Unassigned', 'total' => (float) $g->sum('amount'), 'count' => $g->count()])
            ->sortByDesc('total')
            ->values();

        $daily = [];
        for ($d = 1; $d <= $end->day; $d++) {
            $dayPayments = $payments->filter(fn ($p) => $p->paid_at->day === $d);
            if ($dayPayments->isNotEmpty()) {
                $daily[] = ['day' => $d, 'amount' => (float) $dayPayments->sum('amount'), 'count' => $dayPayments->count()];
            }
        }

        return [
            'summary' => [
                'total' => (float) $payments->sum('amount'),
                'count' => $payments->count(),
                'unique_clients' => $payments->unique('client_id')->count(),
                'avg_transaction' => $payments->count() > 0 ? round($payments->avg('amount'), 2) : 0,
            ],
            'by_method' => $byMethod,
            'by_zone' => $byZone,
            'daily' => $daily,
            'rows' => $payments->take(500)->map(fn ($p) => $this->paymentRow($p))->all(),
        ];
    }

    private function collectionReport(Carbon $start, Carbon $end, array $filters = []): array
    {
        $sessions = CollectionSession::query()
            ->with(['staff.user', 'staff.zone'])
            ->whereBetween('session_date', [$start, $end])
            ->when($filters['staff_id'] ?? null, fn ($q, $s) => $q->where('staff_id', $s))
            ->when($filters['zone_id'] ?? null, fn ($q, $z) => $q->whereHas('staff', fn ($c) => $c->where('zone_id', $z)))
            ->orderBy('session_date')
            ->get();

        $planned = (float) $sessions->sum('planned_amount');
        $actual = (float) $sessions->sum('actual_amount');

        return [
            'summary' => [
                'total_planned' => $planned,
                'total_actual' => $actual,
                'efficiency' => $planned > 0 ? round(($actual / $planned) * 100, 1) : 0,
                'sessions' => $sessions->count(),
                'completed' => $sessions->where('status', 'completed')->count(),
            ],
            'rows' => $sessions->map(fn ($s) => [
                'date' => $s->session_date?->toDateString(),
                'reference' => $s->session_reference,
                'collector' => $s->staff?->user?->name ?? '—',
                'zone' => $s->staff?->zone?->name ?? '—',
                'planned' => (float) $s->planned_amount,
                'actual' => (float) $s->actual_amount,
                'efficiency' => $s->planned_amount > 0 ? round(($s->actual_amount / $s->planned_amount) * 100, 1) : 0,
                'status' => $s->status,
            ])->all(),
        ];
    }

    private function staffReport(Carbon $start, Carbon $end, array $filters = []): array
    {
        $collectors = Staff::query()
            ->with(['user', 'zone'])
            ->collectors()
            ->when($filters['staff_id'] ?? null, fn ($q, $s) => $q->where('id', $s))
            ->when($filters['zone_id'] ?? null, fn ($q, $z) => $q->where('zone_id', $z))
            ->get();

        $rows = $collectors->map(function ($staff) use ($start, $end) {
            $payments = Payment::query()->paid()
                ->where('staff_id', $staff->id)
                ->whereBetween('paid_at', [$start, $end])
                ->get();

            $sessions = CollectionSession::where('staff_id', $staff->id)
                ->whereBetween('session_date', [$start, $end])
                ->get();

            $planned = (float) $sessions->sum('planned_amount');
            $collected = (float) $payments->sum('amount');

            return [
                'name' => $staff->user?->name ?? 'Unknown',
                'zone' => $staff->zone?->name ?? 'Unassigned',
                'transactions' => $payments->count(),
                'collected' => $collected,
                'sessions' => $sessions->count(),
                'sessions_completed' => $sessions->where('status', 'completed')->count(),
                'planned' => $planned,
                'efficiency' => $planned > 0 ? round(($collected / $planned) * 100, 1) : 0,
            ];
        })->sortByDesc('collected')->values();

        return [
            'summary' => [
                'total_collected' => (float) $rows->sum('collected'),
                'total_transactions' => (int) $rows->sum('transactions'),
                'collector_count' => $rows->count(),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function financialReport(Carbon $start, Carbon $end): array
    {
        $revenue = (float) Payment::query()->paid()->whereBetween('paid_at', [$start, $end])->sum('amount');
        $expensesByCategory = Expense::query()->with('category')
            ->whereBetween('expense_date', [$start, $end])
            ->get()
            ->groupBy(fn ($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn ($g) => ['category' => $g->first()->category?->name ?? 'Uncategorized', 'total' => (float) $g->sum('amount')])
            ->sortByDesc('total')
            ->values();

        $totalExpenses = (float) $expensesByCategory->sum('total');

        return [
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $totalExpenses,
                'net_profit' => $revenue - $totalExpenses,
                'margin' => $revenue > 0 ? round((($revenue - $totalExpenses) / $revenue) * 100, 1) : 0,
                'expenses_by_category_count' => $expensesByCategory->count(),
            ],
            'expense_categories' => $expensesByCategory->all(),
        ];
    }

    private function debtsReport(Carbon $start, Carbon $end): array
    {
        $invoices = Invoice::query()->with('client.zone')
            ->whereIn('status', ['unpaid', 'partial', 'overdue', 'penalized'])
            ->orderByDesc('balance')
            ->get();

        $debts = Debt::query()->with('client')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return [
            'summary' => [
                'total_outstanding' => (float) $invoices->sum('balance'),
                'invoice_count' => $invoices->count(),
                'penalties' => (float) $invoices->sum('penalty_amount'),
                'debt_records' => $debts->count(),
            ],
            'rows' => $invoices->map(fn ($i) => [
                'client' => $i->client?->name ?? '—',
                'client_number' => $i->client?->client_number ?? '—',
                'zone' => $i->client?->zone?->name ?? '—',
                'billing_month' => $i->billing_month,
                'amount_due' => (float) $i->amount_due,
                'balance' => (float) $i->balance,
                'penalty' => (float) ($i->penalty_amount ?? 0),
                'status' => $i->status,
                'due_date' => $i->due_date?->toDateString(),
            ])->all(),
        ];
    }

    private function clientsReport(array $filters = []): array
    {
        $clients = Client::query()->with(['zone', 'clientType'])
            ->when($filters['zone_id'] ?? null, fn ($q, $z) => $q->where('zone_id', $z))
            ->withCount(['payments' => fn ($q) => $q->paid()])
            ->get();

        return [
            'summary' => [
                'total_clients' => $clients->count(),
                'active' => $clients->where('status', 'active')->count(),
                'monthly_recurring' => (float) $clients->where('status', 'active')->sum('monthly_fee'),
            ],
            'rows' => $clients->map(fn ($c) => [
                'client_number' => $c->client_number,
                'name' => $c->name,
                'phone' => $c->phone,
                'zone' => $c->zone?->name ?? '—',
                'type' => $c->clientType?->name ?? '—',
                'monthly_fee' => (float) $c->monthly_fee,
                'payments_count' => $c->payments_count,
                'total_paid' => (float) $c->total_paid,
                'outstanding' => (float) $c->outstanding_balance,
                'status' => $c->status,
            ])->all(),
        ];
    }

    private function bankingReport(Carbon $start, Carbon $end): array
    {
        $deposits = BankDeposit::query()->with(['bankAccount', 'staff.user'])
            ->whereBetween('deposit_date', [$start, $end])
            ->orderByDesc('deposit_date')
            ->get();

        $cashCollected = (float) Payment::query()->paid()
            ->where('payment_method', 'cash')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        return [
            'summary' => [
                'cash_collected' => $cashCollected,
                'total_deposited' => (float) $deposits->sum('amount'),
                'pending_deposits' => (float) $deposits->where('status', 'pending')->sum('amount'),
                'confirmed_deposits' => (float) $deposits->where('status', 'confirmed')->sum('amount'),
                'unbanked_cash' => $cashCollected - (float) $deposits->sum('amount'),
            ],
            'rows' => $deposits->map(fn ($d) => [
                'date' => $d->deposit_date?->toDateString(),
                'reference' => $d->deposit_reference,
                'account' => $d->bankAccount ? "{$d->bankAccount->bank_name} — {$d->bankAccount->account_number}" : '—',
                'amount' => (float) $d->amount,
                'deposited_by' => $d->staff?->user?->name ?? '—',
                'status' => $d->status,
            ])->all(),
        ];
    }

    private function paymentRow(Payment $p): array
    {
        return [
            'control_number' => $p->control_number,
            'receipt_number' => $p->receipt_number,
            'payer' => $p->payer_name ?? $p->client?->name ?? 'Unknown',
            'client_number' => $p->client?->client_number ?? '',
            'zone' => $p->client?->zone?->name ?? '—',
            'collector' => $p->staff?->user?->name ?? '—',
            'amount' => (float) $p->amount,
            'method' => $p->payment_method,
            'paid_at' => $p->paid_at?->format('Y-m-d H:i:s'),
        ];
    }

    // ─── CSV export (all types) ─────────────────────────────────────────────

    public function exportCsv(Request $request)
    {
        $type = (string) $request->query('type', 'revenue');
        abort_unless(array_key_exists($type, self::REPORT_TYPES), 404);

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $filters = [
            'zone_id' => $request->query('zone_id'),
            'staff_id' => $request->query('staff_id'),
        ];

        $data = $this->buildReport($type, $month, $year, $filters);
        $period = Carbon::create($year, $month, 1)->format('F_Y');

        return response()->streamDownload(function () use ($type, $data, $month, $year) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['WASTE COLLECTION PORTAL - '.self::REPORT_TYPES[$type]]);
            fputcsv($handle, ['Period:', Carbon::create($year, $month, 1)->format('F Y')]);
            fputcsv($handle, ['Generated:', now()->format('d M Y H:i')]);
            fputcsv($handle, []);

            foreach ($data['summary'] ?? [] as $key => $value) {
                fputcsv($handle, [ucwords(str_replace('_', ' ', $key)), is_numeric($value) ? number_format((float) $value, 2) : $value]);
            }
            fputcsv($handle, []);

            foreach ($this->reportSections($type, $data) as $section) {
                fputcsv($handle, ["--- {$section['title']} ---"]);
                fputcsv($handle, $section['headers']);
                foreach ($section['rows'] as $row) {
                    fputcsv($handle, array_map(
                        fn ($v) => is_float($v) || is_int($v) ? number_format((float) $v, 2) : $v,
                        array_values($row)
                    ));
                }
                fputcsv($handle, []);
            }

            fclose($handle);
        }, str_replace(' ', '_', strtolower(self::REPORT_TYPES[$type]))."_{$period}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Normalise each report's data into titled sections of headers + rows.
     *
     * @return array<int, array{title: string, headers: array<int, string>, rows: array<int, array>}>
     */
    private function reportSections(string $type, array $data): array
    {
        return match ($type) {
            'revenue' => [
                ['title' => 'Transactions', 'headers' => ['Control #', 'Receipt #', 'Payer', 'Client #', 'Zone', 'Collector', 'Amount (TZS)', 'Method', 'Date & Time'], 'rows' => $data['rows']],
                ['title' => 'By Payment Method', 'headers' => ['Method', 'Transactions', 'Total (TZS)'], 'rows' => collect($data['by_method'])->map(fn ($m) => [$m['method'], $m['count'], $m['total']])->all()],
                ['title' => 'By Zone', 'headers' => ['Zone', 'Transactions', 'Total (TZS)'], 'rows' => collect($data['by_zone'])->map(fn ($z) => [$z['label'], $z['count'], $z['total']])->all()],
            ],
            'collection' => [
                ['title' => 'Collection Sessions', 'headers' => ['Date', 'Reference', 'Collector', 'Zone', 'Planned (TZS)', 'Actual (TZS)', 'Efficiency %', 'Status'], 'rows' => $data['rows']],
            ],
            'staff' => [
                ['title' => 'Collector Performance', 'headers' => ['Collector', 'Zone', 'Transactions', 'Collected (TZS)', 'Sessions', 'Completed', 'Planned (TZS)', 'Efficiency %'], 'rows' => $data['rows']],
            ],
            'financial' => [
                ['title' => 'Expenses by Category', 'headers' => ['Category', 'Total (TZS)'], 'rows' => $data['expense_categories']],
            ],
            'debts' => [
                ['title' => 'Outstanding Invoices', 'headers' => ['Client', 'Client #', 'Zone', 'Billing Month', 'Amount Due', 'Balance', 'Penalty', 'Status', 'Due Date'], 'rows' => $data['rows']],
            ],
            'clients' => [
                ['title' => 'Clients', 'headers' => ['Client #', 'Name', 'Phone', 'Zone', 'Type', 'Monthly Fee', 'Payments', 'Total Paid', 'Outstanding', 'Status'], 'rows' => $data['rows']],
            ],
            'banking' => [
                ['title' => 'Bank Deposits', 'headers' => ['Date', 'Reference', 'Account', 'Amount (TZS)', 'Deposited By', 'Status'], 'rows' => $data['rows']],
            ],
            default => [],
        };
    }

    // ─── PDF export (all types) ─────────────────────────────────────────────

    public function exportPdf(Request $request)
    {
        $type = $request->query('type', 'revenue');
        abort_unless(array_key_exists($type, self::REPORT_TYPES), 404);

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $filters = [
            'zone_id' => $request->query('zone_id'),
            'staff_id' => $request->query('staff_id'),
        ];

        return $this->downloadPdf($type, $month, $year, $filters);
    }

    private function downloadPdf(string $type, int $month, int $year, array $filters = [])
    {
        $data = $this->buildReport($type, $month, $year, $filters);
        $sections = $this->reportSections($type, $data);
        $period = Carbon::create($year, $month, 1)->format('F_Y');

        return Pdf::view('pdf.report-generic', [
            'reportTitle' => self::REPORT_TYPES[$type],
            'period' => Carbon::create($year, $month, 1)->format('F Y'),
            'summary' => $data['summary'] ?? [],
            'sections' => $sections,
        ])
            ->format('A4')
            ->landscape()
            ->download(str_replace(' ', '_', strtolower(self::REPORT_TYPES[$type]))."_{$period}.pdf");
    }

    // ─── Legacy page reports (kept for existing routes/links) ──────────────

    public function monthly(Request $request): Response
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        [$monthStart, $monthEnd] = $this->monthRange($month, $year);

        $payments = Payment::query()->paid()
            ->with(['client', 'staff.user'])
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->get();

        $expenses = Expense::query()->with('category')
            ->whereBetween('expense_date', [$monthStart, $monthEnd])
            ->get();

        $totalRevenue = (float) $payments->sum('amount');
        $totalExpenses = (float) $expenses->sum('amount');

        $dailyRevenue = [];
        for ($d = 1; $d <= $monthEnd->day; $d++) {
            $dayPayments = $payments->filter(fn ($p) => $p->paid_at->day === $d);
            $dailyRevenue[] = [
                'day' => $d,
                'amount' => (float) $dayPayments->sum('amount'),
                'count' => $dayPayments->count(),
            ];
        }

        $byCollector = $payments->groupBy(fn ($p) => $p->staff?->user?->name ?? 'Unknown')
            ->map(fn ($group) => [
                'amount' => (float) $group->sum('amount'),
                'count' => $group->count(),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

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

    public function yearly(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            [$monthStart, $monthEnd] = $this->monthRange($m, $year);

            $revenue = (float) Payment::query()->paid()->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount');
            $expenses = (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');

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

    public function collector(Request $request): Response
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $staffId = $request->input('staff_id');
        [$monthStart, $monthEnd] = $this->monthRange($month, $year);

        $collectors = Staff::query()->with('user', 'zone')
            ->collectors()
            ->when($staffId, fn ($q) => $q->where('id', $staffId))
            ->get()
            ->map(function ($staff) use ($monthStart, $monthEnd) {
                $payments = Payment::query()->paid()
                    ->where('staff_id', $staff->id)
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->get();

                $sessions = CollectionSession::where('staff_id', $staff->id)
                    ->whereBetween('session_date', [$monthStart, $monthEnd])
                    ->get();
                $planned = (float) $sessions->sum('planned_amount');

                return [
                    'id' => $staff->id,
                    'name' => $staff->user?->name ?? 'Unknown',
                    'zone' => $staff->zone?->name ?? 'Unassigned',
                    'totalCollected' => (float) $payments->sum('amount'),
                    'transactionCount' => $payments->count(),
                    'sessionsCompleted' => $sessions->where('status', 'completed')->count(),
                    'plannedAmount' => $planned,
                    'efficiency' => $planned > 0
                        ? round(((float) $payments->sum('amount') / $planned) * 100, 1)
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

    // ─── Scheduled reports ──────────────────────────────────────────────────

    public function scheduleReport(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'in:'.implode(',', array_keys(self::REPORT_TYPES))],
            'frequency' => 'required|in:daily,weekly,monthly',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
        ]);

        ScheduledReport::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'recipients' => $validated['recipients'],
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Report scheduled.');
    }

    public function toggleSchedule(ScheduledReport $report)
    {
        $report->update(['is_active' => ! $report->is_active]);

        return back()->with('success', $report->is_active ? 'Report activated.' : 'Report paused.');
    }

    public function sendNow(ScheduledReport $report)
    {
        $data = $this->buildReport($report->type, now()->month, now()->year);
        $sections = $this->reportSections($report->type, $data);

        $pdf = Pdf::view('pdf.report-generic', [
            'reportTitle' => self::REPORT_TYPES[$report->type] ?? $report->type,
            'period' => now()->format('F Y'),
            'summary' => $data['summary'] ?? [],
            'sections' => $sections,
        ])->format('A4')->landscape()->output();

        foreach ($report->recipients ?? [] as $email) {
            Mail::to($email)->send(new ReportMail($pdf, $report->name));
        }

        $report->update(['last_sent_at' => now()]);

        return back()->with('success', 'Report sent to '.count($report->recipients ?? []).' recipient(s).');
    }

    public function download(int $reportId)
    {
        $report = ScheduledReport::findOrFail($reportId);
        $data = $this->buildReport($report->type, now()->month, now()->year);

        $pdf = Pdf::view('pdf.report-generic', [
            'reportTitle' => self::REPORT_TYPES[$report->type] ?? $report->type,
            'period' => now()->format('F Y'),
            'summary' => $data['summary'] ?? [],
            'sections' => $this->reportSections($report->type, $data),
        ])->format('A4')->landscape()->output();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$report->name}.pdf\"");
    }

    // ─── Monthly comparison ─────────────────────────────────────────────────

    public function monthlyComparison(Request $request)
    {
        $currentMonth = (int) $request->query('month', now()->month);
        $currentYear = (int) $request->query('year', now()->year);

        $prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
        $prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

        [$curStart, $curEnd] = $this->monthRange($currentMonth, $currentYear);
        [$prevStart, $prevEnd] = $this->monthRange($prevMonth, $prevYear);

        $metricsFor = fn ($s, $e) => [
            'revenue' => (float) Payment::query()->paid()->whereBetween('paid_at', [$s, $e])->sum('amount'),
            'collections' => (float) CollectionSession::whereBetween('session_date', [$s, $e])->sum('actual_amount'),
            'expenses' => (float) Expense::whereBetween('expense_date', [$s, $e])->sum('amount'),
        ];

        $current = $metricsFor($curStart, $curEnd);
        $previous = $metricsFor($prevStart, $prevEnd);

        $diff = fn (string $key) => [
            'diff' => $current[$key] - $previous[$key],
            'percent' => $previous[$key] > 0 ? round((($current[$key] - $previous[$key]) / $previous[$key]) * 100, 2) : 0,
        ];

        $rev = $diff('revenue');
        $col = $diff('collections');
        $exp = $diff('expenses');

        return response()->json([
            'current_period' => Carbon::create($currentYear, $currentMonth, 1)->format('M Y'),
            'previous_period' => Carbon::create($prevYear, $prevMonth, 1)->format('M Y'),
            'current' => $current,
            'previous' => $previous,
            'revenue_diff' => $rev['diff'],
            'revenue_percent' => $rev['percent'],
            'collections_diff' => $col['diff'],
            'collections_percent' => $col['percent'],
            'expenses_diff' => $exp['diff'],
            'expenses_percent' => $exp['percent'],
        ]);
    }

    // ─── Daily / weekly JSON reports (kept for dashboard widgets) ──────────

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

            $data = $query->get()->map(fn ($session) => [
                'staff_name' => $session->staff?->user?->name ?? 'Unknown',
                'zone' => $session->staff?->zone?->name ?? 'Unassigned',
                'planned_amount' => (float) ($session->planned_amount ?? 0),
                'actual_amount' => (float) ($session->actual_amount ?? 0),
                'completion_rate' => $session->planned_amount > 0
                    ? round(($session->actual_amount / $session->planned_amount) * 100, 2)
                    : 0,
                'status' => $session->status,
            ]);

            return response()->json(['data' => $data, 'date' => $date]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function dailyCompanyPerformance(Request $request)
    {
        try {
            $date = $request->query('date', now()->format('Y-m-d'));

            $totalPlanned = (float) CollectionSession::whereDate('session_date', $date)->sum('planned_amount');
            $totalCollected = (float) CollectionSession::whereDate('session_date', $date)->sum('actual_amount');
            $totalPayments = (float) Payment::query()->paid()->whereDate('paid_at', $date)->sum('amount');
            $completedRoutes = CollectionSession::whereDate('session_date', $date)->where('status', 'completed')->count();
            $pendingRoutes = CollectionSession::whereDate('session_date', $date)->where('status', '!=', 'completed')->count();

            return response()->json([
                'date' => $date,
                'total_planned' => $totalPlanned,
                'total_collected' => $totalCollected,
                'total_payments' => $totalPayments,
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

            $mapSession = fn ($session) => [
                'staff_name' => $session->staff?->user?->name ?? 'Unknown',
                'zone' => $session->staff?->zone?->name ?? 'Unassigned',
                'planned_amount' => (float) ($session->planned_amount ?? 0),
                'actual_amount' => (float) ($session->actual_amount ?? 0),
            ];

            $completed = $sessions->where('status', 'completed')->map($mapSession)->values();
            $notCompleted = $sessions->where('status', '!=', 'completed')
                ->map(fn ($s) => $mapSession($s) + ['status' => $s->status])
                ->values();

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

    public function weeklyCollectorPerformance(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->weekRange($request);

            $data = Staff::query()->with('user', 'zone')->collectors()->get()
                ->map(function ($collector) use ($startDate, $endDate) {
                    $collections = CollectionSession::where('staff_id', $collector->id)
                        ->whereBetween('session_date', [$startDate, $endDate])
                        ->get();

                    $totalCollected = (float) ($collections->sum('actual_amount') ?? 0);
                    $target = (float) $collector->base_salary * 10;

                    return [
                        'staff_name' => $collector->user?->name ?? 'Unknown',
                        'zone' => $collector->zone?->name ?? 'Unassigned',
                        'total_collected' => $totalCollected,
                        'total_planned' => (float) ($collections->sum('planned_amount') ?? 0),
                        'target' => $target,
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
            [$startDate, $endDate] = $this->weekRange($request);

            $totalCollected = (float) CollectionSession::whereBetween('session_date', [$startDate, $endDate])->sum('actual_amount');
            $totalPlanned = (float) CollectionSession::whereBetween('session_date', [$startDate, $endDate])->sum('planned_amount');
            $totalRevenue = (float) Payment::query()->paid()->whereBetween('paid_at', [$startDate, $endDate])->sum('amount');
            $target = $totalPlanned * 1.1;

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_collected' => $totalCollected,
                'total_planned' => $totalPlanned,
                'total_revenue' => $totalRevenue,
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
            [$startDate, $endDate] = $this->weekRange($request);

            $revenue = (float) Payment::query()->paid()->whereBetween('paid_at', [$startDate, $endDate])->sum('amount');
            $expenses = (float) Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');
            $banked = (float) BankDeposit::whereBetween('deposit_date', [$startDate, $endDate])->sum('amount');
            $debts = (float) Invoice::whereBetween('due_date', [$startDate, $endDate])
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->sum('balance');
            $notPaid = (float) Payment::whereBetween('paid_at', [$startDate, $endDate])->where('status', 'pending')->sum('amount');

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'revenue' => $revenue,
                'expenses_incurred' => $expenses,
                'expenditures' => $expenses,
                'banked' => $banked,
                'not_paid' => $notPaid,
                'debts' => $debts,
                'penalties' => 0,
                'net_cash_flow' => $revenue - $expenses,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function weeklyWasteCollectionReport(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->weekRange($request);

            $sessions = CollectionSession::whereBetween('session_date', [$startDate, $endDate])
                ->with('staff.user', 'staff.zone')
                ->get();

            $byZone = Zone::all()->map(function ($zone) use ($sessions) {
                $zoneSessions = $sessions->filter(fn ($session) => $session->staff?->zone_id === $zone->id);

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

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function monthRange(int $month, int $year): array
    {
        return [
            Carbon::create($year, $month, 1)->startOfDay(),
            Carbon::create($year, $month, 1)->endOfMonth()->endOfDay(),
        ];
    }

    private function weekRange(Request $request): array
    {
        return [
            $request->query('start_date', now()->startOfWeek()->format('Y-m-d')),
            $request->query('end_date', now()->endOfWeek()->format('Y-m-d')),
        ];
    }
}
