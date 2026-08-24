<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CollectionController extends Controller
{
    /**
     * Collections view — filter by any combination of zone, collector,
     * month, or custom date range, with breakdowns by zone and collector.
     */
    public function index(Request $request)
    {
        $query = Payment::query()
            ->where('payments.status', 'paid')
            ->with(['client.zone', 'staff.user']);

        // ── Filters ─────────────────────────────────────────────────────
        if ($request->filled('zone_id')) {
            $query->whereHas('client', fn ($q) => $q->where('clients.zone_id', $request->zone_id));
        }

        if ($request->filled('collector_id')) {
            $query->where('payments.staff_id', $request->collector_id);
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('payments.paid_at', $request->month)
                ->whereYear('payments.paid_at', $request->year);
        } elseif ($request->filled('month')) {
            $query->whereMonth('payments.paid_at', $request->month)
                ->whereYear('payments.paid_at', now()->year);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('payments.paid_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay(),
            ]);
        }

        // ── Summary ─────────────────────────────────────────────────────
        $totalAmount = (float) (clone $query)->sum('payments.amount');
        $totalCount = (clone $query)->count();
        $uniqueClients = (clone $query)->distinct('payments.client_id')->count('payments.client_id');

        // ── Breakdown by zone ───────────────────────────────────────────
        $byZone = (clone $query)
            ->join('clients', 'payments.client_id', '=', 'clients.id')
            ->join('zones', 'clients.zone_id', '=', 'zones.id')
            ->selectRaw('zones.name as label, SUM(payments.amount) as total, COUNT(*) as count')
            ->groupBy('zones.id', 'zones.name')
            ->orderByDesc('total')
            ->get();

        // ── Breakdown by collector ──────────────────────────────────────
        $byCollector = (clone $query)
            ->join('staff', 'payments.staff_id', '=', 'staff.id')
            ->leftJoin('users', 'staff.user_id', '=', 'users.id')
            ->selectRaw("COALESCE(users.name, 'Unassigned') as label, SUM(payments.amount) as total, COUNT(*) as count")
            ->groupBy('staff.id', 'users.name')
            ->orderByDesc('total')
            ->get();

        // ── Paginated transaction list ─────────────────────────────────
        $transactions = (clone $query)
            ->orderByDesc('payments.paid_at')
            ->paginate(50)
            ->through(fn ($p) => [
                'id' => $p->id,
                'control_number' => $p->control_number,
                'receipt_number' => $p->receipt_number,
                'payer_name' => $p->payer_name ?? $p->client?->name ?? 'Unknown',
                'amount' => (float) $p->amount,
                'method' => $p->payment_method,
                'paid_at' => $p->paid_at?->toDateTimeString(),
                'zone' => $p->client?->zone?->name ?? '—',
                'collector' => $p->staff?->user?->name ?? '—',
            ]);

        return Inertia::render('Collections/Index', [
            'transactions' => $transactions,
            'summary' => [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'unique_clients' => $uniqueClients,
            ],
            'byZone' => $byZone,
            'byCollector' => $byCollector,
            'zones' => Zone::orderBy('name')->get(['id', 'name']),
            'collectors' => Staff::collectors()->active()->with('user:id,name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name ?? "Staff #{$s->id}"]),
            'filters' => $request->only(['zone_id', 'collector_id', 'month', 'year', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Export the filtered collections as CSV.
     */
    public function export(Request $request)
    {
        $query = Payment::query()
            ->where('payments.status', 'paid')
            ->with(['client.zone', 'staff.user']);

        if ($request->filled('zone_id')) {
            $query->whereHas('client', fn ($q) => $q->where('clients.zone_id', $request->zone_id));
        }
        if ($request->filled('collector_id')) {
            $query->where('payments.staff_id', $request->collector_id);
        }
        if ($request->filled('month')) {
            $query->whereMonth('payments.paid_at', $request->month)
                ->whereYear('payments.paid_at', $request->year ?? now()->year);
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('payments.paid_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay(),
            ]);
        }

        return response()->stream(function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['Control #', 'Receipt #', 'Payer Name', 'Client #', 'Zone', 'Collector', 'Amount (TZS)', 'Method', 'Date & Time']);

            $query->orderBy('payments.paid_at')->chunk(500, function ($payments) use ($file) {
                foreach ($payments as $p) {
                    fputcsv($file, [
                        $p->control_number,
                        $p->receipt_number,
                        $p->payer_name ?? $p->client?->name,
                        $p->client?->client_number,
                        $p->client?->zone?->name,
                        $p->staff?->user?->name,
                        number_format((float) $p->amount, 2),
                        $p->payment_method,
                        $p->paid_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=collections_'.now()->format('Ymd_His').'.csv',
        ]);
    }

    /**
     * Merge duplicate client records into a single canonical record.
     * All payments, invoices and debts move to the target; duplicates are soft-deleted.
     */
    public function mergeClients(Request $request)
    {
        $validated = $request->validate([
            'target_id' => 'required|exists:clients,id',
            'duplicate_ids' => 'required|array|min:1',
            'duplicate_ids.*' => 'exists:clients,id|different:target_id',
            'canonical_name' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $target = Client::lockForUpdate()->findOrFail($validated['target_id']);
            $duplicates = Client::whereIn('id', $validated['duplicate_ids'])->lockForUpdate()->get();

            if ($duplicates->contains(fn ($c) => $c->id === $target->id)) {
                throw new \InvalidArgumentException('A duplicate cannot be the same as the target.');
            }

            $movedPayments = 0;
            $movedInvoices = 0;
            $movedDebts = 0;

            foreach ($duplicates as $dup) {
                $movedPayments += Payment::where('client_id', $dup->id)->update(['client_id' => $target->id]);
                $movedInvoices += Invoice::where('client_id', $dup->id)->update(['client_id' => $target->id]);
                $movedDebts += Debt::where('client_id', $dup->id)->update(['client_id' => $target->id]);

                AuditLog::log('client.merged_away', 'Client', $dup->id, [
                    'merged_into' => $target->id,
                    'name' => $dup->name,
                ]);

                $dup->delete(); // soft delete keeps history intact
            }

            // Optionally fix the canonical name on the surviving record.
            if (! empty($validated['canonical_name'])) {
                $oldName = $target->name;
                $target->update(['name' => trim($validated['canonical_name'])]);
                AuditLog::log('client.rename', 'Client', $target->id, [
                    'from' => $oldName,
                    'to' => $target->name,
                ]);
            }

            AuditLog::log('client.merge', 'Client', $target->id, [
                'duplicates' => $duplicates->pluck('id'),
                'moved_payments' => $movedPayments,
                'moved_invoices' => $movedInvoices,
                'moved_debts' => $movedDebts,
            ]);

            DB::commit();

            return back()->with(
                'success',
                "Merged {$duplicates->count()} record(s) into {$target->name}. "
                ."{$movedPayments} payment(s), {$movedInvoices} invoice(s) and {$movedDebts} debt(s) transferred."
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Merge failed: '.$e->getMessage());
        }
    }

    /**
     * Reassign individual payments to a different client
     * (for receipts that were written under the wrong name).
     */
    public function reassignPayments(Request $request)
    {
        $validated = $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
            'target_id' => 'required|exists:clients,id',
        ]);

        DB::beginTransaction();
        try {
            $target = Client::findOrFail($validated['target_id']);
            $count = Payment::whereIn('id', $validated['payment_ids'])
                ->update(['client_id' => $target->id]);

            AuditLog::log('payment.reassign', 'Payment', null, [
                'payment_ids' => $validated['payment_ids'],
                'new_client' => $target->id,
                'count' => $count,
            ]);

            DB::commit();

            return back()->with('success', "{$count} payment(s) reassigned to {$target->name}.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Reassignment failed: '.$e->getMessage());
        }
    }

    /**
     * Search clients for the merge tool (fuzzy name search).
     */
    public function searchClients(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Client::query()
            ->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$term}%")
                ->orWhere('client_number', 'like', "%{$term}%"))
            ->withCount('payments')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'client_number' => $c->client_number,
                'phone' => $c->phone,
                'payments_count' => $c->payments_count,
            ]);

        return response()->json(['results' => $results]);
    }
}
