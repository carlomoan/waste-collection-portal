<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Zone;
use App\Models\ClientType;
use App\Models\ClientContact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AuditLog;
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
            ->when($request->filled('zone_id'), fn($q) => $q->where('zone_id', $request->zone_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'zones' => Zone::all(['id', 'name']),
            'clientTypes' => ClientType::all(['id', 'name']),
            'filters' => $request->only(['search', 'zone_id', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_number' => 'required|string|unique:clients',
            'phone' => 'required|string|max:20',
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
            $client = Client::create($request->all());
            AuditLog::log('client.create', 'Client', $client->id, $request->all());
            DB::commit();
            return redirect()->route('clients.index')->with('success', 'Client created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Client $client)
    {
        $client->load(['zone', 'clientType', 'contacts']);

        $recentInvoices = Invoice::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentPayments = Payment::where('client_id', $client->id)
            ->orderBy('paid_at', 'desc')
            ->limit(10)
            ->get();

        $outstandingBalance = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->sum('balance');

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'outstandingBalance' => (float) $outstandingBalance,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_number' => 'required|string|unique:clients,client_number,'.$client->id,
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
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
            $old = $client->toArray();
            $client->update($request->all());
            AuditLog::log('client.update', 'Client', $client->id, ['old' => $old, 'new' => $request->all()]);
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
            $client->delete();
            AuditLog::log('client.delete', 'Client', $client->id);
            DB::commit();
            return redirect()->route('clients.index')->with('success', 'Client deleted.');
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
            'email' => 'nullable|email',
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
            $contact = $client->contacts()->create($request->all());
            AuditLog::log('client.add_contact', 'ClientContact', $contact->id);
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
        $filename = "clients_".now()->format('Ymd_His').".csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $callback = function () use ($clients) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Client #', 'Name', 'Phone', 'Email', 'Zone', 'Type', 'Monthly Fee', 'Status']);
            foreach ($clients as $c) {
                fputcsv($file, [
                    $c->client_number, $c->name, $c->phone, $c->email,
                    $c->zone->name, $c->clientType?->name, $c->monthly_fee, $c->status,
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}