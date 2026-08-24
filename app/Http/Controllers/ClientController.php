<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::with(['zone', 'clientType'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('client_number', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            })
            ->when($request->filled('zone_id'), fn ($q) => $q->where('zone_id', $request->zone_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->get();

        return Inertia::render('Clients/Index', [
            'clients' => $clients->map(fn ($c) => array_merge($c->toArray(), [
                'client_type' => $c->clientType?->name,
                'zone_name' => $c->zone?->name,
                'outstanding' => (float) Invoice::where('client_id', $c->id)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->sum('balance'),
            ])),
            'zones' => Zone::all(['id', 'name']),
            'clientTypes' => ClientType::all(['id', 'name']),
            'filters' => $request->only(['search', 'zone_id', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_number' => 'nullable|string|unique:clients',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'zone_id' => 'required|exists:zones,id',
            'client_type_id' => 'nullable|exists:client_types,id',
            'monthly_fee' => 'required|numeric|min:0',
            'billing_day' => 'nullable|integer|min:1|max:31',
            'status' => 'required|in:active,inactive,suspended',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();
            if (empty($data['client_number'])) {
                $data['client_number'] = 'WCP-'.str_pad(Client::count() + 1, 5, '0', STR_PAD_LEFT);
            }

            $client = Client::create($data);
            AuditLog::log('client.create', 'Client', $client->id, $data);
            DB::commit();

            return back()->with('success', 'Client created.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Client $client, Request $request)
    {
        $client->load(['zone', 'clientType']);

        $recentPayments = Payment::where('client_id', $client->id)
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get(['id', 'receipt_number', 'amount', 'status', 'paid_at']);

        $outstandingBalance = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->sum('balance');

        $payload = [
            'client' => $client,
            'recentPayments' => $recentPayments,
            'outstandingBalance' => (float) $outstandingBalance,
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('clients.index');
    }

    public function edit(Client $client, Request $request)
    {
        $client->load(['zone', 'clientType']);

        if ($request->wantsJson()) {
            return response()->json(['client' => $client]);
        }

        return redirect()->route('clients.index');
    }

    public function update(Request $request, Client $client)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_number' => 'required|string|unique:clients,client_number,'.$client->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'zone_id' => 'required|exists:zones,id',
            'client_type_id' => 'nullable|exists:client_types,id',
            'monthly_fee' => 'required|numeric|min:0',
            'billing_day' => 'nullable|integer|min:1|max:31',
            'status' => 'required|in:active,inactive,suspended',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $old = $client->only(array_keys($validator->validated()));
            $client->update($validator->validated());
            AuditLog::log('client.update', 'Client', $client->id, $validator->validated(), $old);
            DB::commit();

            return back()->with('success', 'Client updated.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Client $client)
    {
        if ($client->invoices()->exists() || $client->payments()->exists()) {
            return back()->with('error', 'Cannot delete client with financial records.');
        }

        DB::beginTransaction();
        try {
            AuditLog::log('client.delete', 'Client', $client->id, null, $client->toArray());
            $client->delete();
            DB::commit();

            return back()->with('success', 'Client deleted.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function addContact(Request $request, Client $client)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            if ($request->is_primary) {
                $client->contacts()->update(['is_primary' => false]);
            }

            $contact = $client->contacts()->create($validator->validated());
            AuditLog::log('client.add_contact', 'ClientContact', $contact->id, $validator->validated());
            DB::commit();

            return back()->with('success', 'Contact added.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $clients = Client::with(['zone', 'clientType'])->get();

        return response()->stream(function () use ($clients) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['Client #', 'Name', 'Phone', 'Email', 'Zone', 'Type', 'Monthly Fee', 'Status']);

            foreach ($clients as $c) {
                fputcsv($file, [
                    $c->client_number,
                    $c->name,
                    $c->phone,
                    $c->email,
                    $c->zone?->name,
                    $c->clientType?->name,
                    $c->monthly_fee,
                    $c->status,
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=clients_'.now()->format('Ymd_His').'.csv',
        ]);
    }

    public function profile(Client $client)
    {
        $client->load(['zone', 'clientType']);

        // ── Monthly history: one row per month ─────────────────────────
        $monthlyHistory = Payment::where('client_id', $client->id)
            ->paid()
            ->selectRaw("TO_CHAR(paid_at, 'YYYY-MM') as month_key")
            ->selectRaw('SUM(amount) as total_paid')
            ->selectRaw('COUNT(*) as transaction_count')
            ->groupBy('month_key')
            ->orderByDesc('month_key')
            ->get()
            ->map(function ($row) use ($client) {
                [$year, $month] = explode('-', $row->month_key);
                $fee = (float) $client->monthly_fee;
                $paid = (float) $row->total_paid;

                return [
                    'month_key' => $row->month_key,
                    'label' => Carbon::create((int) $year, (int) $month, 1)->format('F Y'),
                    'total_paid' => $paid,
                    'transaction_count' => (int) $row->transaction_count,
                    'monthly_fee' => $fee,
                    'difference' => round($paid - $fee, 2),
                ];
            });

        // ── All payments with collector + name-mismatch flag ───────────
        $payments = Payment::where('client_id', $client->id)
            ->with(['staff.user'])
            ->orderByDesc('paid_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'control_number' => $p->control_number,
                'receipt_number' => $p->receipt_number,
                'payer_name' => $p->payer_name,
                'amount' => (float) $p->amount,
                'method' => $p->payment_method,
                'status' => $p->status,
                'paid_at' => $p->paid_at?->toDateTimeString(),
                'collector' => $p->staff?->user?->name ?? '—',
                // Flag receipts where the written payer name differs from the canonical client name
                'name_mismatch' => ! empty($p->payer_name)
                    && strcasecmp(trim($p->payer_name), trim($client->name)) !== 0,
            ]);

        // ── Invoice ledger ─────────────────────────────────────────────
        $invoices = Invoice::where('client_id', $client->id)
            ->orderByDesc('billing_month')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number ?? ('INV-'.$inv->id),
                'billing_month' => $inv->billing_month,
                'amount_due' => (float) $inv->amount_due,
                'amount_paid' => (float) ($inv->amount_paid ?? 0),
                'balance' => (float) $inv->balance,
                'penalty_amount' => (float) ($inv->penalty_amount ?? 0),
                'status' => $inv->status,
                'due_date' => $inv->due_date?->toDateString(),
            ]);

        return Inertia::render('Clients/Show', [
            'client' => array_merge($client->toArray(), [
                'zone_name' => $client->zone?->name,
                'client_type_name' => $client->clientType?->name,
                'outstanding_balance' => (float) $client->outstanding_balance,
                'total_paid' => (float) $client->total_paid,
            ]),
            'monthlyHistory' => $monthlyHistory,
            'payments' => $payments,
            'invoices' => $invoices,
        ]);
    }
}
