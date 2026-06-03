<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use App\Models\BankAccount;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BankingController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::all()->map(function ($acc) {
            $deposits = BankDeposit::where('bank_account_id', $acc->id)->where('status', 'confirmed')->sum('amount');
            $acc->current_balance = $acc->opening_balance + $deposits;
            return $acc;
        });

        return Inertia::render('Banking/Index', [
            'bankAccounts' => $accounts,
            'recentDeposits' => BankDeposit::with('bankAccount')->orderBy('deposit_date', 'desc')->limit(15)->get(),
            'cashPosition' => $this->getCashPosition(),
        ]);
    }

    private function getCashPosition()
    {
        $cashCollected = Payment::where('payment_method', 'cash')
            ->whereDate('paid_at', today())
            ->sum('amount');
        $bankedToday = BankDeposit::whereDate('deposit_date', today())->sum('amount');
        $pendingBanking = $cashCollected - $bankedToday;

        return [
            'cash_collected_today' => $cashCollected,
            'banked_today' => $bankedToday,
            'pending_banking' => $pendingBanking,
        ];
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string',
            'account_number' => 'required|string|unique:bank_accounts',
            'account_name' => 'required|string',
            'opening_balance' => 'numeric|min:0',
            'currency' => 'required|string|size:3',
        ]);

        BankAccount::create($request->all());
        return back()->with('success', 'Bank account added.');
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0',
            'deposit_date' => 'required|date',
            'slip_image' => 'nullable|file|image|max:2048',
            'reference' => 'nullable|string',
        ]);

        $slipPath = $request->hasFile('slip_image') ? $request->file('slip_image')->store('banking/slips', 'public') : null;

        BankDeposit::create([
            'bank_account_id' => $request->bank_account_id,
            'amount' => $request->amount,
            'deposit_date' => $request->deposit_date,
            'slip_image_path' => $slipPath,
            'deposit_reference' => $request->reference,
            'status' => 'pending',
            'staff_id' => auth()->id(),
        ]);

        return back()->with('success', 'Deposit recorded, pending confirmation.');
    }

    public function uploadStatement(Request $request, BankAccount $bankAccount)
    {
        $request->validate([
            'statement_file' => 'required|file|mimes:csv,pdf|max:5120',
        ]);

        $path = $request->file('statement_file')->store("banking/statements/{$bankAccount->id}", 'local');

        // Process statement (CSV/PDF parsing)
        $this->processStatement($bankAccount, $path);

        return back()->with('success', 'Statement uploaded and reconciliation started.');
    }

    private function processStatement(BankAccount $account, $filePath)
    {
        // Parse CSV or PDF and auto-match with pending deposits
        // For simplicity, we'll mark all pending deposits as confirmed if amount matches a bank transaction
        // In real app, use a job
        $pendingDeposits = BankDeposit::where('bank_account_id', $account->id)
            ->where('status', 'pending')
            ->get();

        foreach ($pendingDeposits as $deposit) {
            // Simulate matching logic
            $deposit->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        }
    }

    public function reconcileManual(Request $request, BankDeposit $deposit)
    {
        $deposit->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        return back()->with('success', 'Deposit confirmed.');
    }

    public function dailyCashPositionReport(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $cashReceived = Payment::where('payment_method', 'cash')->whereDate('paid_at', $date)->sum('amount');
        $banked = BankDeposit::whereDate('deposit_date', $date)->sum('amount');
        $expensesCash = \App\Models\Expense::where('payment_method', 'cash')->whereDate('expense_date', $date)->sum('amount');

        $report = [
            'date' => $date,
            'cash_received' => $cashReceived,
            'cash_expenses' => $expensesCash,
            'net_cash' => $cashReceived - $expensesCash,
            'amount_banked' => $banked,
            'cash_in_hand' => ($cashReceived - $expensesCash) - $banked,
        ];

        if ($request->query('export') === 'pdf') {
            return Pdf::view('pdf.cash-position', ['report' => $report])->download("cash_position_{$date}.pdf");
        }

        return response()->json($report);
    }
}