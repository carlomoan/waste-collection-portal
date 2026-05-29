<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index()
    {
        try {
            // Get expense categories with budget and spent amounts
            $categories = ExpenseCategory::with(['expenses' => function ($query) {
                $query->whereYear('expense_date', now()->year)
                      ->whereMonth('expense_date', now()->month);
            }])
            ->get()
            ->map(function ($category) {
                $spent = $category->expenses->sum('amount') ?? 0;
                return [
                    'id' => $category->id ?? null,
                    'name' => $category->name ?? 'Unknown',
                    'budget' => 5000000, // Default budget - can be stored in categories table
                    'spent' => (float) $spent,
                ];
            });

            // Get recent expenses
            $recentExpenses = Expense::with(['category', 'approvedBy'])
                ->orderBy('expense_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($expense) {
                    return [
                        'id' => $expense->id ?? null,
                        'description' => $expense->description ?? 'N/A',
                        'category' => $expense->category->name ?? 'N/A',
                        'amount' => (float) ($expense->amount ?? 0),
                        'date' => $expense->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                        'status' => $expense->status ?? 'pending',
                        'approved_by' => $expense->approvedBy?->name ?? null,
                    ];
                });

            return Inertia::render('Expenses/Index', [
                'categories' => $categories,
                'recentExpenses' => $recentExpenses,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load expense data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $categories = ExpenseCategory::all()->map(function ($category) {
                return [
                    'id' => $category->id ?? null,
                    'name' => $category->name ?? 'Unknown',
                ];
            });

            return Inertia::render('Expenses/Create', [
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load expense form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:500',
            'receipt_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            Expense::create([
                'expense_category_id' => $request->expense_category_id,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'description' => $request->description,
                'receipt_number' => $request->receipt_number,
                'status' => 'pending',
                'staff_id' => auth()->id(),
            ]);

            return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record expense: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $expense = Expense::with('category')->findOrFail($id);
            $categories = ExpenseCategory::all()->map(function ($category) {
                return [
                    'id' => $category->id ?? null,
                    'name' => $category->name ?? 'Unknown',
                ];
            });

            return Inertia::render('Expenses/Edit', [
                'expense' => [
                    'id' => $expense->id,
                    'expense_category_id' => $expense->expense_category_id,
                    'amount' => (float) $expense->amount,
                    'expense_date' => $expense->expense_date?->format('Y-m-d'),
                    'description' => $expense->description,
                    'receipt_number' => $expense->receipt_number,
                    'status' => $expense->status,
                ],
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load expense: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:500',
            'receipt_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $expense = Expense::findOrFail($id);
            $expense->update([
                'expense_category_id' => $request->expense_category_id,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'description' => $request->description,
                'receipt_number' => $request->receipt_number,
            ]);

            return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update expense: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        try {
            $expense = Expense::findOrFail($id);
            $expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return back()->with('success', 'Expense approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve expense: ' . $e->getMessage());
        }
    }

    public function reject($id)
    {
        try {
            $expense = Expense::findOrFail($id);
            $expense->update([
                'status' => 'rejected',
            ]);

            return back()->with('success', 'Expense rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject expense: ' . $e->getMessage());
        }
    }
}
