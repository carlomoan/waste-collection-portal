<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\BankDeposit;
use App\Services\ReportService;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public function index()
    {
        return Inertia::render('Finance', [
            'invoices' => Invoice::with('client')
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->get(),
            'payments' => Payment::with('client', 'staff')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->latest('paid_at')
                ->get(),
            'expenses' => Expense::with('category', 'staff')
                ->whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->latest('expense_date')
                ->get(),
            'bankDeposits' => BankDeposit::with('staff')
                ->whereMonth('deposit_date', now()->month)
                ->whereYear('deposit_date', now()->year)
                ->latest('deposit_date')
                ->get(),
            'monthlyStats' => app(ReportService::class)->monthlyCompany(now()->month, now()->year),
        ]);
    }
}
