<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\TausiPosImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index — list all payments
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $payments = Payment::with(['client', 'staff.user', 'collectionSession'])
            ->when($request->search, fn($q, $s) =>
                $q->where('control_number', 'ilike', "%{$s}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('name', 'ilike', "%{$s}%"))
                  ->orWhere('payer_name', 'ilike', "%{$s}%")
            )
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->month, function ($q, $m) {
                [$year, $month] = explode('-', $m);
                $q->whereYear('paid_at', $year)->whereMonth('paid_at', $month);
            })
            ->when($request->collector_id, fn($q, $id) => $q->where('staff_id', $id))
            ->latest('paid_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn($p) => [
                'id'             => $p->id,
                'control_number' => $p->control_number,
                'payer_name'     => $p->payer_name ?? $p->client?->name,
                'client_id'      => $p->client_id,
                'client_number'  => $p->client?->client_number,
                'amount'         => $p->amount,
                'status'         => $p->status,
                'paid_at'        => $p->paid_at?->toDateTimeString(),
                'collector'      => $p->staff?->user?->name,
                'receipt'        => $p->collectionSession?->session_reference,
            ]);

        $summary = [
            'total'       => Payment::count(),
            'total_amount'=> Payment::where('status','paid')->sum('amount'),
            'paid'        => Payment::where('status','paid')->count(),
            'partial'     => 0, // placeholder
            'unmatched'   => Payment::where('client_id', function($q) {
                                $q->select('id')->from('clients')
                                  ->where('client_number','WCP-UNKNOWN');
                             })->count(),
        ];

        return Inertia::render('Transactions/Index', [
            'payments'   => $payments,
            'summary'    => $summary,
            'filters'    => $request->only(['search','status','month','collector_id']),
            'collectors' => \App\Models\Staff::with('user')
                            ->get()
                            ->map(fn($s) => ['id'=>$s->id,'name'=>$s->user?->name]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show single payment
    |--------------------------------------------------------------------------
    */
    public function show(Payment $payment)
    {
        return Inertia::render('Transactions/Show', [
            'payment' => $payment->load(['client','staff.user','invoice','collectionSession']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Import page — show upload form
    |--------------------------------------------------------------------------
    */
    public function importPage()
    {
        return Inertia::render('Transactions/Import');
    }

    /*
    |--------------------------------------------------------------------------
    | Preview — parse file, return rows WITHOUT saving (Step 2)
    |--------------------------------------------------------------------------
    */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimetypes:application/pdf,'
                    . 'application/vnd.ms-excel,'
                    . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                    . 'text/csv',
            ],
        ]);

        $file     = $request->file('file');
        $path     = $file->store('imports/temp', 'local');
        $fullPath = Storage::disk('local')->path($path);
        $mime     = $file->getMimeType();

        $result = app(TausiPosImportService::class)->preview($fullPath, $mime);

        // Keep temp path in session for the confirm step
        session(['import_temp_path' => $path, 'import_mime' => $mime]);

        return response()->json($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm import — actually write to DB (Step 3)
    |--------------------------------------------------------------------------
    */
    public function confirmImport(Request $request)
    {
        $path = session('import_temp_path');
        $mime = session('import_mime');

        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'Upload session expired. Please re-upload the file.'], 422);
        }

        $fullPath = Storage::disk('local')->path($path);
        $result   = app(TausiPosImportService::class)->import($fullPath, $mime);

        // Clean up temp file
        Storage::disk('local')->delete($path);
        session()->forget(['import_temp_path', 'import_mime']);

        return response()->json($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */
    public function export(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'No.','Control Number','Payer Name','Client Number',
                'Amount (TZS)','Collector','Receipt','Status','Date & Time',
            ]);

            Payment::with(['client','staff.user','collectionSession'])
                ->when($request->month, function ($q, $m) {
                    [$year, $month] = explode('-', $m);
                    $q->whereYear('paid_at', $year)->whereMonth('paid_at', $month);
                })
                ->latest('paid_at')
                ->chunk(500, function ($payments) use ($handle) {
                    foreach ($payments as $i => $p) {
                        fputcsv($handle, [
                            $i + 1,
                            $p->control_number,
                            $p->payer_name ?? $p->client?->name,
                            $p->client?->client_number,
                            number_format($p->amount, 2),
                            $p->staff?->user?->name,
                            $p->collectionSession?->session_reference,
                            strtoupper($p->status),
                            $p->paid_at?->format('M d, Y H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, 'transactions-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create single payment manually
    |--------------------------------------------------------------------------
    */
    public function create(Request $request)
    {
        return Inertia::render('Transactions/Create', [
            'clients' => \App\Models\Client::active()
                        ->orderBy('name')
                        ->get(['id','client_number','name','monthly_fee']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'amount'         => 'required|numeric|min:1',
            'control_number' => 'required|string|unique:payments,control_number',
            'bill_reference' => 'nullable|string',
            'payer_name'     => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash,mobile_money,bank',
            'paid_at'        => 'required|date',
        ]);

        $staff = auth()->user()->staff;

        $session = \App\Models\CollectionSession::firstOrCreate(
            ['session_reference' => 'MANUAL-' . now()->format('Ymd')],
            [
                'staff_id'     => $staff?->id ?? 1,
                'session_date' => now()->toDateString(),
                'status'       => 'open',
            ]
        );

        $payment = Payment::create(array_merge($validated, [
            'staff_id'              => $staff?->id ?? 1,
            'collection_session_id' => $session->id,
            'bill_reference'        => $validated['bill_reference'] ?? 'manual-' . \Str::uuid(),
            'status'                => 'paid',
        ]));

        return redirect()->route('transactions.show', $payment)
            ->with('success', 'Payment recorded successfully.');
    }
}
