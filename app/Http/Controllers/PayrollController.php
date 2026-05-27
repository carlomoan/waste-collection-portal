<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\SalaryPayment;
use App\Models\AttendanceRecord;
use App\Models\CollectionSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index()
    {
        $month = now()->month;
        $year = now()->year;

        return Inertia::render('Payroll/Index', [
            'staff' => Staff::with('user', 'zone')
                ->where('is_active', true)
                ->where('role', 'collector')
                ->orderBy('created_at')
                ->get(),
            'salaryPayments' => SalaryPayment::with('staff.user')
                ->where('pay_month', $month)
                ->where('pay_year', $year)
                ->get(),
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $month = $validated['month'];
        $year = $validated['year'];

        $staff = Staff::with('user', 'zone')
            ->where('is_active', true)
            ->where('role', 'collector')
            ->get();

        $payrollData = $staff->map(function ($staffMember) use ($month, $year) {
            // Calculate attendance
            $attendance = AttendanceRecord::where('staff_id', $staffMember->id)
                ->whereMonth('work_date', $month)
                ->whereYear('work_date', $year)
                ->get();

            $presentDays = $attendance->where('status', 'present')->count();
            $halfDays = $attendance->where('status', 'half_day')->count();
            $totalDays = $presentDays + ($halfDays * 0.5);

            // Calculate collections
            $collections = CollectionSession::where('staff_id', $staffMember->id)
                ->whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->sum('actual_amount');

            // Calculate commission (5% of collections)
            $commission = $collections * 0.05;

            // Calculate net salary
            $baseSalary = $staffMember->base_salary;
            $allowances = 0;
            $deductions = 0;
            $netSalary = $baseSalary + $allowances + $commission - $deductions;

            return [
                'staff' => $staffMember,
                'attendance' => [
                    'present' => $presentDays,
                    'half_days' => $halfDays,
                    'total_days' => $totalDays,
                ],
                'collections' => $collections,
                'commission' => $commission,
                'salary' => [
                    'base' => $baseSalary,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'net' => $netSalary,
                ],
            ];
        });

        return Inertia::render('Payroll/Generate', [
            'payrollData' => $payrollData,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'payments' => 'required|array',
            'payments.*.staff_id' => 'required|exists:staff,id',
            'payments.*.base_salary' => 'required|numeric',
            'payments.*.allowances' => 'required|numeric',
            'payments.*.commissions' => 'required|numeric',
            'payments.*.deductions' => 'required|numeric',
            'payments.*.net_salary' => 'required|numeric',
        ]);

        foreach ($validated['payments'] as $payment) {
            SalaryPayment::updateOrCreate(
                [
                    'staff_id' => $payment['staff_id'],
                    'pay_month' => $validated['month'],
                    'pay_year' => $validated['year'],
                ],
                [
                    'base_salary' => $payment['base_salary'],
                    'allowances' => $payment['allowances'],
                    'commissions' => $payment['commissions'],
                    'deductions' => $payment['deductions'],
                    'net_salary' => $payment['net_salary'],
                    'status' => 'pending',
                ]
            );
        }

        return redirect()->route('payroll.index')->with('success', 'Payroll generated successfully.');
    }

    public function markAsPaid(SalaryPayment $salaryPayment)
    {
        $salaryPayment->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);

        return redirect()->back()->with('success', 'Payment marked as paid.');
    }
}
