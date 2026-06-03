<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $invoices = Invoice::with('client')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->orderBy('due_date', 'desc')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('client', 'payments');
        return Inertia::render('Invoices/Show', ['invoice' => $invoice]);
    }

    public function generate(Request $request)
    {
        $request->validate(['month' => 'required|integer|min:1|max:12', 'year' => 'required|integer']);
        $count = $this->invoiceService->generateMonthlyInvoices($request->month, $request->year);
        return back()->with('success', "Generated {$count} invoices.");
    }

    public function applyPenalties()
    {
        $count = $this->invoiceService->applyPenalties();
        return back()->with('success', "Applied penalties to {$count} invoices.");
    }

    public function download(Invoice $invoice)
    {
        // Generate PDF invoice
        return \Spatie\LaravelPdf\Facades\Pdf::view('pdf.invoice', ['invoice' => $invoice])
            ->download("invoice-{$invoice->invoice_number}.pdf");
    }
}