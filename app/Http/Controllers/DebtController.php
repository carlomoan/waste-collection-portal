<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\DebtCollection;
use App\Models\PaymentPlan;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Carbon\Carbon;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with(['client.zone'])
            ->whereIn('status', ['unpaid', 'partial', 'overdue', 'penalized'])
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('zone_id'), fn($q) => $q->whereHas('client', fn($cq) => $cq->where('zone_id', $request->zone_id)))
            ->when($request->filled('aging_bucket'), function ($q) use ($request) {
                $days = (int) $request->aging_bucket;
                $date = now()->subDays($days);
                $q->where('due_date', '<=', $date);
            })
            ->orderBy('due_date')
            ->paginate(50)
            ->withQueryString();

        $totalOutstanding = Invoice::whereIn('status', ['unpaid', 'partial', 'overdue', 'penalized'])->sum('balance');
        $agingSummary = [
            '0-30' => Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->where('due_date', '>=', now()->subDays(30))->sum('balance'),
            '31-60' => Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->whereBetween('due_date', [now()->subDays(60), now()->subDays(31)])->sum('balance'),
            '61-90' => Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->whereBetween('due_date', [now()->subDays(90), now()->subDays(61)])->sum('balance'),
            '90+' => Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->where('due_date', '<', now()->subDays(90))->sum('balance'),
        ];

        return Inertia::render('Debts/Index', [
            'invoices' => $invoices,
            'totalOutstanding' => (float) $totalOutstanding,
            'agingSummary' => $agingSummary,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'zones' => \App\Models\Zone::all(['id', 'name']),
        ]);
    }

    public function createPaymentPlan(Request $request, Client $client)
    {
        $validator = Validator::make($request->all(), [
            'total_debt' => 'required|numeric|min:1',
            'installments' => 'required|integer|min:1|max:24',
            'start_date' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $installmentAmount = $request->total_debt / $request->installments;
            $plan = PaymentPlan::create([
                'client_id' => $client->id,
                'total_amount' => $request->total_debt,
                'installments' => $request->installments,
                'installment_amount' => $installmentAmount,
                'start_date' => $request->start_date,
                'status' => 'active',
            ]);

            for ($i = 1; $i <= $request->installments; $i++) {
                $dueDate = Carbon::parse($request->start_date)->addMonths($i - 1);
                $plan->installments()->create([
                    'installment_number' => $i,
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                ]);
            }

            AuditLog::log('payment_plan.create', 'PaymentPlan', $plan->id);
            DB::commit();
            return back()->with('success', "Payment plan created: {$request->installments} installments of ".number_format($installmentAmount, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function sendReminder(Invoice $invoice)
    {
        $client = $invoice->client;
        if (!$client->email) {
            return back()->with('error', 'Client has no email address.');
        }

        Mail::send('emails.debt_reminder', ['invoice' => $invoice], function ($message) use ($client) {
            $message->to($client->email)
                ->subject('Payment Reminder - Invoice #'.$invoice->invoice_number);
        });

        AuditLog::log('debt.reminder_sent', 'Invoice', $invoice->id);
        return back()->with('success', 'Reminder sent to '.$client->email);
    }

    public function writeOff(Invoice $invoice, Request $request)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        DB::beginTransaction();
        try {
            $invoice->update([
                'status' => 'written_off',
                'written_off_at' => now(),
                'write_off_reason' => $request->reason,
                'written_off_by' => auth()->id(),
            ]);

            AuditLog::log('debt.write_off', 'Invoice', $invoice->id, ['reason' => $request->reason]);
            DB::commit();
            return back()->with('success', 'Debt written off.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $invoices = Invoice::with('client')
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->when($request->filled('aging_bucket'), function ($q) use ($request) {
                $days = (int) $request->aging_bucket;
                $q->where('due_date', '<=', now()->subDays($days));
            })
            ->get();

        $filename = "debts_".now()->format('Ymd_His').".csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice #', 'Client', 'Due Date', 'Amount Due', 'Balance', 'Status']);
            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->invoice_number,
                    $inv->client->name,
                    $inv->due_date->format('Y-m-d'),
                    number_format($inv->amount_due, 2),
                    number_format($inv->balance, 2),
                    $inv->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}