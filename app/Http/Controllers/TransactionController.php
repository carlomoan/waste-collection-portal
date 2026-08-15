<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceipt;
use App\Models\AuditLog;
use App\Models\BankDeposit;
use App\Models\Client;
use App\Models\CollectionSession;
use App\Models\Payment;
use App\Models\Staff;
use App\Services\TausiPosImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\PdfToText\Pdf as PdfTextExtractor;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $payments = QueryBuilder::for(Payment::class)
            ->with(['client', 'staff.user', 'collectionSession'])
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('control_number', 'ilike', "%{$value}%")
                          ->orWhere('payer_name', 'ilike', "%{$value}%")
                          ->orWhereHas('client', fn($cq) => $cq->where('name', 'ilike', "%{$value}%"));
                    });
                }),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('month', function ($query, $value) {
                    [$year, $month] = explode('-', $value);
                    $query->whereYear('paid_at', $year)->whereMonth('paid_at', $month);
                }),
                AllowedFilter::exact('collector_id', 'staff_id'),
                AllowedFilter::exact('payment_method'),
                AllowedFilter::scope('reconciled'),
            ])
            ->allowedSorts(['paid_at', 'amount', 'control_number'])
            ->defaultSort('-paid_at')
            ->paginate(50)
            ->withQueryString();

            $summary = [
                'total' => Payment::count(),
                // 🚀 Total cash physically collected by staff (Paid + Unpaid bank statuses)
                'total_amount' => Payment::sum('amount'),
                'bank_deposited_amount' => Payment::where('status', 'paid')->sum('amount'),
                'pending_deposit_amount' => Payment::where('status', 'pending')->sum('amount'),
                'paid' => Payment::where('status', 'paid')->count(),
                'pending' => Payment::where('status', 'pending')->count(),
                'pending_reconciliation' => Payment::where('is_reconciled', false)->count(),
                'unmatched' => Payment::whereHas('client', fn($q) => $q->where('client_number', 'WCP-UNKNOWN'))->count(),
            ];

        $bankDeposits = BankDeposit::where('status', 'pending')->get()->map(fn($d) => [
            'id' => $d->id,
            'reference' => $d->reference,
            'amount' => (float) $d->amount,
            'date' => $d->deposit_date?->format('Y-m-d'),
        ]);

        return Inertia::render('Transactions/Index', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status', 'month', 'collector_id', 'payment_method', 'reconciled']),
            'collectors' => Staff::with('user')->get()->map(fn($s) => ['id' => $s->id, 'name' => $s->user?->name]),
            'bankDeposits' => $bankDeposits,
        ]);
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,mobile_money,bank',
            'paid_at' => 'required|date',
            'payer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $controlNumber = $this->generateControlNumber();
            $staff = auth()->user()->staff;

            $session = CollectionSession::firstOrCreate(
                ['session_reference' => 'MANUAL-' . now()->format('Ymd')],
                [
                    'staff_id' => $staff?->id ?? 1,
                    'session_date' => now()->toDateString(),
                    'status' => 'open',
                ]
            );

            $payment = Payment::create(array_merge($validated, [
                'control_number' => $controlNumber,
                'staff_id' => $staff?->id,
                'collection_session_id' => $session->id,
                'status' => 'paid',
                'bill_reference' => 'INV-' . Str::random(8),
                'is_reconciled' => $validated['payment_method'] === 'cash',
            ]));

            AuditLog::log('payment.create', 'Payment', $payment->id, $validated);
            DB::commit();

            return redirect()->route('transactions.show', $payment)->with('success', 'Payment recorded.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Generate a unique control number.
     */
    private function generateControlNumber(): string
    {
        do {
            $number = 'PAY-' . now()->format('Ymd') . '-' . rand(1000, 9999);
        } while (Payment::where('control_number', $number)->exists());

        return $number;
    }

    /**
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'refund_amount' => 'required|numeric|min:0|max:' . $payment->amount,
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'refunded',
                'refund_reason' => $request->reason,
                'refunded_at' => now(),
                'refund_amount' => $request->refund_amount,
            ]);

            AuditLog::log('payment.refund', 'Payment', $payment->id, $request->only('reason', 'refund_amount'));
            DB::commit();

            return back()->with('success', 'Payment refunded.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Send a receipt email to the client.
     */
    public function sendReceiptEmail(Payment $payment)
    {
        if (!$payment->client || !$payment->client->email) {
            return back()->with('error', 'Client email not found.');
        }

        Mail::to($payment->client->email)->send(new PaymentReceipt($payment));

        return back()->with('success', 'Receipt sent to ' . $payment->client->email);
    }

    /**
     * Reconcile selected payments with a bank deposit.
     */
    public function reconcileWithBank(Request $request)
    {
        $request->validate([
            'deposit_id' => 'required|exists:bank_deposits,id',
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'exists:payments,id',
        ]);

        DB::beginTransaction();
        try {
            $deposit = BankDeposit::findOrFail($request->deposit_id);
            $total = Payment::whereIn('id', $request->payment_ids)->sum('amount');

            if (abs($total - $deposit->amount) > 0.01) {
                throw new \Exception('Total payment amount does not match deposit amount.');
            }

            Payment::whereIn('id', $request->payment_ids)->update([
                'is_reconciled' => true,
                'reconciled_at' => now(),
                'bank_deposit_id' => $deposit->id,
            ]);

            $deposit->update([
                'status' => 'confirmed',
                'reconciled_at' => now(),
            ]);

            AuditLog::log('payment.reconcile', 'BankDeposit', $deposit->id, ['payment_ids' => $request->payment_ids]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Reconciliation completed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Export a batch of selected payments as PDF.
     */
    public function exportBatch(Request $request)
    {
        $ids = $request->input('ids', []);
        $payments = Payment::whereIn('id', $ids)->with(['client', 'staff.user'])->get();

        return Pdf::view('pdf.transactions-batch', ['payments' => $payments])
            ->landscape()
            ->download('payments-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Show the import page.
     */
    public function importPage()
    {
        return Inertia::render('Transactions/Import');
    }

    /**
     * Preview parsed data from an uploaded file (Step 2 of import wizard).
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv',
            ],
        ]);

        $file = $request->file('file');
        $path = $file->store('imports/temp', 'local');
        $fullPath = Storage::disk('local')->path($path);
        $mime = $file->getMimeType();

        // Handle PDF files using Spatie PDF-to-Text
        if ($mime === 'application/pdf') {
            try {
                $text = (new PdfTextExtractor())
                    ->setPdf($fullPath)
                    ->addOptions(['-layout'])
                    ->text();

                $result = app(TausiPosImportService::class)->previewFromText($text);
                session(['import_temp_path' => $path, 'import_mime' => $mime]);

                return response()->json($result);
            } catch (\Exception $e) {
                Storage::disk('local')->delete($path);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse PDF: ' . $e->getMessage(),
                ], 422);
            }
        }

        // For Excel/CSV files
        try {
            $result = app(TausiPosImportService::class)->preview($fullPath, $mime);
            session(['import_temp_path' => $path, 'import_mime' => $mime]);
            return response()->json($result);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($path);
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm and execute the import (Step 3 of import wizard).
     */
    public function confirmImport(Request $request)
    {
        $path = session('import_temp_path');
        $mime = session('import_mime');

        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session expired. Please re-upload the file.',
            ], 422);
        }

        $fullPath = Storage::disk('local')->path($path);

        try {
            if ($mime === 'application/pdf') {
                $text = (new PdfTextExtractor())
                    ->setPdf($fullPath)
                    ->addOptions(['-layout'])
                    ->text();

                $result = app(TausiPosImportService::class)->importFromText($text);
            } else {
                $result = app(TausiPosImportService::class)->import($fullPath, $mime);
            }

            session(['last_imported_ids' => $result['imported_ids'] ?? []]);
            Storage::disk('local')->delete($path);
            session()->forget(['import_temp_path', 'import_mime']);

            return response()->json($result);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($path);
            session()->forget(['import_temp_path', 'import_mime']);
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Export filtered payments as CSV.
     */
    public function export(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'No.', 'Control Number', 'Payer Name', 'Client Number',
                'Amount (TZS)', 'Collector', 'Receipt', 'Status', 'Date & Time',
            ]);

            Payment::with(['client', 'staff.user', 'collectionSession'])
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
                            $p->amount,
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

    /**
     * Export filtered payments as PDF.
     */
    public function exportPdf(Request $request)
    {
        $payments = Payment::with(['client', 'staff.user', 'collectionSession'])
            ->when($request->month, function ($q, $m) {
                [$year, $month] = explode('-', $m);
                $q->whereYear('paid_at', $year)->whereMonth('paid_at', $month);
            })
            ->when($request->collector_id, fn($q, $id) => $q->where('staff_id', $id))
            ->when($request->search, fn($q, $s) =>
                $q->where('control_number', 'ilike', "%{$s}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('name', 'ilike', "%{$s}%"))
                  ->orWhere('payer_name', 'ilike', "%{$s}%")
            )
            ->latest('paid_at')
            ->get();

        return Pdf::view('pdf.transactions', [
            'payments' => $payments,
            'filters' => $request->only(['month', 'collector_id', 'search']),
        ])
        ->landscape()
        ->download('transactions-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Download a single payment receipt as PDF.
     */
    public function downloadPdf(Payment $payment)
    {
        return Pdf::view('pdf.transaction-single', [
            'payment' => $payment->load(['client', 'staff.user', 'collectionSession', 'invoice']),
        ])
        ->landscape()
        ->download("transaction-{$payment->id}.pdf");
    }

    /**
     * Show a single payment details.
     */
    public function show(Payment $payment, Request $request)
    {
        $payment->load(['client', 'staff.user', 'collectionSession', 'invoice']);

        if ($request->wantsJson()) {
            return response()->json(['payment' => $payment]);
        }

        return Inertia::render('Transactions/Index', [
            'payments' => $this->index($request)->toResponse($request)->getData(true)['props']['payments'] ?? [],
            'selectedTx' => $payment,
        ]);
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'payer_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,mobile_money,bank',
            'status' => 'required|in:paid,pending,refunded',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $old = $payment->only(array_keys($validated));
        $payment->update($validated);

        AuditLog::log('payment.update', 'Payment', $payment->id, $validated, $old);

        return back()->with('success', 'Transaction updated.');
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(Payment $payment)
    {
        AuditLog::log('payment.delete', 'Payment', $payment->id, null, $payment->toArray());
        $payment->delete();

        return back()->with('success', 'Transaction deleted.');
    }
    /**
     * Export the just-imported transactions as PDF.
     */
    public function exportImportedPdf(Request $request)
    {
        $ids = session('last_imported_ids', []);
        $payments = Payment::whereIn('id', $ids)
            ->with(['client', 'staff.user'])
            ->get();

        return Pdf::view('pdf.imported-transactions', [
            'payments' => $payments,
            'title'    => 'Imported Transactions – ' . now()->format('d M Y'),
        ])
            ->landscape()
            ->download('imported-transactions.pdf');
    }

    /**
     * Show the form to create a manual payment.
     */
    public function create(Request $request)
    {
        return Inertia::render('Transactions/Create', [
            'clients' => Client::active()
                ->orderBy('name')
                ->get(['id', 'client_number', 'name', 'monthly_fee']),
        ]);
    }
}
