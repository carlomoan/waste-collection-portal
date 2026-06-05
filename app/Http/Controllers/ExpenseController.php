<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseApproval;
use App\Models\BudgetAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::with(['expenses' => function ($q) {
            $q->whereYear('expense_date', now()->year)->whereMonth('expense_date', now()->month);
        }])->get()->map(function ($cat) {
            $spent = $cat->expenses->sum('amount');
            $budget = $cat->monthly_budget ?? 0;
            $alert = $budget > 0 && $spent > $budget * 0.9 ? 'warning' : null;
            return ['id' => $cat->id, 'name' => $cat->name, 'budget' => $budget, 'spent' => $spent, 'alert' => $alert];
        });

        return Inertia::render('Expenses/Index', [
            'categories' => $categories,
            'recentExpenses' => Expense::with(['category', 'approvals.approver'])
                ->orderBy('expense_date', 'desc')->limit(20)->get(),
            'pendingApprovals' => Expense::where('status', 'pending')->where('current_approval_level', 1)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:500',
            'receipt' => 'nullable|file|image|max:2048',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly',
        ]);

        DB::beginTransaction();
        try {
            $receiptPath = $request->hasFile('receipt') ? $request->file('receipt')->store('expenses/receipts', 'public') : null;

            $expense = Expense::create([
                'expense_category_id' => $request->expense_category_id,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'description' => $request->description,
                'receipt_path' => $receiptPath,
                'status' => 'pending',
                'current_approval_level' => 1,
                'staff_id' => auth()->id(),
                'is_recurring' => $request->is_recurring ?? false,
                'recurrence_pattern' => $request->recurrence_pattern,
                'parent_expense_id' => null,
            ]);

            if ($request->is_recurring) {
                $this->createRecurringInstances($expense);
            }

            $this->checkBudgetAlert($expense);

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Expense submitted for approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function createRecurringInstances(Expense $parent)
    {
        // Create next 12 occurrences
        for ($i = 1; $i <= 12; $i++) {
            $nextDate = now()->parse($parent->expense_date)->add($parent->recurrence_pattern, $i);
            Expense::create([
                'expense_category_id' => $parent->expense_category_id,
                'amount' => $parent->amount,
                'expense_date' => $nextDate,
                'description' => $parent->description.' (Recurring)',
                'status' => 'pending',
                'current_approval_level' => 1,
                'staff_id' => $parent->staff_id,
                'is_recurring' => true,
                'recurrence_pattern' => $parent->recurrence_pattern,
                'parent_expense_id' => $parent->id,
            ]);
        }
    }

    public function approve(Expense $expense, Request $request)
    {
        $user = auth()->user();
        $level = $expense->current_approval_level;

        // Determine if user is authorized for this level
        if (!$this->canApprove($user, $expense, $level)) {
            return back()->with('error', 'You are not authorized to approve this expense.');
        }

        DB::beginTransaction();
        try {
            ExpenseApproval::create([
                'expense_id' => $expense->id,
                'approver_id' => $user->id,
                'level' => $level,
                'status' => 'approved',
                'comments' => $request->comments,
            ]);

            $nextLevel = $level + 1;
            if ($nextLevel <= $expense->category->approval_levels) {
                $expense->update(['current_approval_level' => $nextLevel]);
            } else {
                $expense->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $user->id]);
            }

            DB::commit();
            return back()->with('success', 'Expense approved.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function canApprove($user, $expense, $level)
    {
        // Logic: level 1 -> supervisor, level 2 -> manager, level 3 -> director
        return $user->hasRole(['supervisor', 'manager', 'admin']);
    }

    private function checkBudgetAlert(Expense $expense)
    {
        $monthSpent = Expense::where('expense_category_id', $expense->expense_category_id)
            ->whereMonth('expense_date', $expense->expense_date->month)
            ->whereYear('expense_date', $expense->expense_date->year)
            ->sum('amount');

        $category = $expense->category;
        if ($category->monthly_budget && $monthSpent > $category->monthly_budget * 0.9) {
            BudgetAlert::create([
                'expense_category_id' => $expense->expense_category_id,
                'threshold' => 90,
                'current_spent' => $monthSpent,
                'budget' => $category->monthly_budget,
                'notified_at' => now(),
            ]);
            // Notification::route('mail', 'finance@example.com')->notify(new BudgetExceededAlert($alert));
        }
    }

    public function analytics(Request $request)
    {
        $year = $request->input('year', now()->year);
        $monthlyTrend = Expense::selectRaw('MONTH(expense_date) as month, SUM(amount) as total')
            ->whereYear('expense_date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $topCategories = Expense::with('category')
            ->whereYear('expense_date', $year)
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json(['monthlyTrend' => $monthlyTrend, 'topCategories' => $topCategories]);
    }

    public function show(Expense $expense)
    {
        return Inertia::render('Expenses/Show', [
            'expense' => $expense->load(['category', 'approvals.approver']),
        ]);
    }

    public function export(Request $request)
    {
        $expenses = Expense::with(['category', 'approvals.approver'])
            ->when($request->filled('category_id'), fn($q) => $q->where('expense_category_id', $request->category_id))
            ->when($request->filled('start_date'), fn($q) => $q->where('expense_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->where('expense_date', '<=', $request->end_date))
            ->orderBy('expense_date', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expenses.csv"',
        ];

        $callback = function () use ($expenses) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Category', 'Amount', 'Date', 'Description', 'Status']);
            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->id,
                    $expense->category->name,
                    $expense->amount,
                    $expense->expense_date,
                    $expense->description,
                    $expense->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'budget' => 'required|numeric|min:0']);
        ExpenseCategory::create($request->only('name', 'budget'));
        return back()->with('success', 'Category created.');
    }
}