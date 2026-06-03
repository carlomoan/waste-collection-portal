<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\BankDeposit;
use App\Models\Budget;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $invoices = Invoice::with('client')
            ->whereMonth('billing_month', $month)
            ->whereYear('billing_year', $year)
            ->orderBy('due_date')
            ->get();

        $payments = Payment::with('client', 'staff')
            ->whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->latest('paid_at')
            ->get();

        $expenses = Expense::with('category', 'staff')
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->latest('expense_date')
            ->get();

        $deposits = BankDeposit::with('bankAccount')
            ->whereMonth('deposit_date', $month)
            ->whereYear('deposit_date', $year)
            ->latest('deposit_date')
            ->get();

        // Profit & Loss
        $revenue = $payments->sum('amount');
        $expenseTotal = $expenses->sum('amount');
        $netProfit = $revenue - $expenseTotal;

        // Budget vs Actual
        $budget = Budget::where('year', $year)->where('month', $month)->first();
        $budgetRevenue = $budget->revenue_target ?? 0;
        $budgetExpense = $budget->expense_budget ?? 0;
        $revenueVariance = $revenue - $budgetRevenue;
        $expenseVariance = $expenseTotal - $budgetExpense;

        // Cash Flow
        $cashIn = $payments->where('payment_method', 'cash')->sum('amount');
        $cashOut = $expenses->where('payment_method', 'cash')->sum('amount');
        $netCashFlow = $cashIn - $cashOut;

        return Inertia::render('Finance', [
            'invoices' => $invoices,
            'payments' => $payments,
            'expenses' => $expenses,
            'bankDeposits' => $deposits,
            'monthlyStats' => app(ReportService::class)->monthlyCompany($month, $year),
            'profitLoss' => [
                'revenue' => (float) $revenue,
                'expenses' => (float) $expenseTotal,
                'net_profit' => (float) $netProfit,
                'profit_margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
            ],
            'budgetVsActual' => [
                'revenue_target' => (float) $budgetRevenue,
                'revenue_actual' => (float) $revenue,
                'revenue_variance' => (float) $revenueVariance,
                'expense_budget' => (float) $budgetExpense,
                'expense_actual' => (float) $expenseTotal,
                'expense_variance' => (float) $expenseVariance,
            ],
            'cashFlow' => [
                'cash_in' => (float) $cashIn,
                'cash_out' => (float) $cashOut,
                'net_cash_flow' => (float) $netCashFlow,
            ],
            'selectedMonth' => $month,
            'selectedYear' => $year,
        ]);
    }

    public function exportReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $format = $request->input('format', 'csv');

        $payments = Payment::with('client')->whereMonth('paid_at', $month)->whereYear('paid_at', $year)->get();
        $expenses = Expense::with('category')->whereMonth('expense_date', $month)->whereYear('expense_date', $year)->get();

        if ($format === 'csv') {
            $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=finance_{$year}_{$month}.csv"];
            $callback = function () use ($payments, $expenses, $month, $year) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ["Financial Report - $month/$year"]);
                fputcsv($file, []);
                fputcsv($file, ['Revenue Transactions']);
                fputcsv($file, ['Date', 'Control #', 'Client', 'Amount', 'Method']);
                foreach ($payments as $p) {
                    fputcsv($file, [$p->paid_at->format('Y-m-d'), $p->control_number, $p->client->name, $p->amount, $p->payment_method]);
                }
                fputcsv($file, []);
                fputcsv($file, ['Expenses']);
                fputcsv($file, ['Date', 'Category', 'Description', 'Amount']);
                foreach ($expenses as $e) {
                    fputcsv($file, [$e->expense_date->format('Y-m-d'), $e->category->name, $e->description, $e->amount]);
                }
                fputcsv($file, []);
                fputcsv($file, ['Total Revenue', $payments->sum('amount')]);
                fputcsv($file, ['Total Expenses', $expenses->sum('amount')]);
                fputcsv($file, ['Net Profit', $payments->sum('amount') - $expenses->sum('amount')]);
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }
        return back()->with('error', 'Unsupported format');
    }

    public function budget(Request $request)
    {
        $year = $request->input('year', now()->year);
        $budgets = Budget::where('year', $year)->orderBy('month')->get();

        return Inertia::render('Finance/Budget', [
            'budgets' => $budgets,
            'year' => $year,
        ]);
    }

    public function storeBudget(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'revenue_target' => 'nullable|numeric|min:0',
            'expense_budget' => 'nullable|numeric|min:0',
        ]);

        Budget::updateOrCreate(
            ['year' => $request->year, 'month' => $request->month],
            ['revenue_target' => $request->revenue_target, 'expense_budget' => $request->expense_budget]
        );

        return back()->with('success', 'Budget saved.');
    }
}