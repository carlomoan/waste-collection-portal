<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BankingController extends Controller
{
    public function index()
    {
        try {
            // Get bank accounts (grouped by bank_name)
            $bankAccounts = BankDeposit::selectRaw('
                bank_name,
                account_number,
                account_name,
                SUM(CASE WHEN status = "confirmed" THEN amount ELSE 0 END) as balance,
                "active" as status
            ')
            ->groupBy('bank_name', 'account_number', 'account_name')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id ?? null,
                    'bank_name' => $account->bank_name ?? 'Unknown',
                    'account_number' => $account->account_number ?? 'N/A',
                    'account_name' => $account->account_name ?? 'Unknown',
                    'balance' => (float) ($account->balance ?? 0),
                    'status' => $account->status ?? 'active',
                ];
            });

            // Get recent deposits
            $recentDeposits = BankDeposit::orderBy('deposit_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($deposit) {
                    return [
                        'id' => $deposit->id ?? null,
                        'bank_account' => $deposit->bank_name ?? 'Unknown',
                        'amount' => (float) ($deposit->amount ?? 0),
                        'date' => $deposit->deposit_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                        'reference' => $deposit->deposit_reference ?? 'N/A',
                        'status' => $deposit->status ?? 'pending',
                    ];
                });

            return Inertia::render('Banking/Index', [
                'bankAccounts' => $bankAccounts,
                'recentDeposits' => $recentDeposits,
            ]);
        } catch (\Exception $e) {
            \Log::error('Banking error: ' . $e->getMessage());
            return Inertia::render('Banking/Index', [
                'bankAccounts' => [],
                'recentDeposits' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'deposit_date' => 'required|date',
            'slip_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            BankDeposit::create([
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'amount' => $request->amount,
                'deposit_date' => $request->deposit_date,
                'slip_number' => $request->slip_number,
                'status' => 'pending',
                'staff_id' => auth()->id(),
            ]);

            return back()->with('success', 'Deposit recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record deposit: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,rejected',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $deposit = BankDeposit::findOrFail($id);
            $deposit->update([
                'status' => $request->status,
            ]);

            return back()->with('success', 'Deposit status updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update deposit: ' . $e->getMessage());
        }
    }

    public function confirm($id)
    {
        try {
            $deposit = BankDeposit::findOrFail($id);
            $deposit->update(['status' => 'confirmed']);

            return back()->with('success', 'Deposit confirmed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to confirm deposit: ' . $e->getMessage());
        }
    }
}
