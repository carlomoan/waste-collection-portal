<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\SalaryPayment;
use App\Models\AttendanceRecord;
use App\Models\CollectionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index()
    {
        try {
            $month = now()->month;
            $year = now()->year;

            return Inertia::render('Payroll/Index', [
                'staff' => Staff::with('user', 'zone')
                    ->where('is_active', true)
                    ->where('role', 'collector')
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($staffMember) {
                        return [
                            'id' => $staffMember->id ?? null,
                            'name' => $staffMember->user?->name ?? 'Unknown',
                            'phone' => $staffMember->phone ?? 'N/A',
                            'zone' => $staffMember->zone?->name ?? 'Unassigned',
                            'base_salary' => (float) ($staffMember->base_salary ?? 0),
                        ];
                    }),
                'salaryPayments' => SalaryPayment::with('staff.user')
                    ->where('pay_month', $month)
                    ->where('pay_year', $year)
                    ->get()
                    ->map(function ($payment) {
                        return [
                            'id' => $payment->id ?? null,
                            'staff_name' => $payment->staff?->user?->name ?? 'Unknown',
                            'base_salary' => (float) ($payment->base_salary ?? 0),
                            'allowances' => (float) ($payment->allowances ?? 0),
                            'commissions' => (float) ($payment->commissions ?? 0),
                            'deductions' => (float) ($payment->deductions ?? 0),
                            'net_salary' => (float) ($payment->net_salary ?? 0),
                            'status' => $payment->status ?? 'pending',
                            'paid_date' => $payment->paid_date?->format('Y-m-d'),
                        ];
                    }),
                'month' => $month,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load payroll data: ' . $e->getMessage());
        }
    }

    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $month = $request->month;
            $year = $request->year;

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
                    ->sum('actual_amount') ?? 0;

                // Calculate commission (5% of collections)
                $commission = $collections * 0.05;

                // Calculate net salary
                $baseSalary = $staffMember->base_salary ?? 0;
                $allowances = 0;
                $deductions = 0;
                $netSalary = $baseSalary + $allowances + $commission - $deductions;

                return [
                    'staff' => [
                        'id' => $staffMember->id,
                        'name' => $staffMember->user?->name ?? 'Unknown',
                        'phone' => $staffMember->phone ?? 'N/A',
                        'zone' => $staffMember->zone?->name ?? 'Unassigned',
                        'base_salary' => (float) $baseSalary,
                    ],
                    'attendance' => [
                        'present' => $presentDays,
                        'half_days' => $halfDays,
                        'total_days' => $totalDays,
                    ],
                    'collections' => (float) $collections,
                    'commission' => (float) $commission,
                    'salary' => [
                        'base' => (float) $baseSalary,
                        'allowances' => (float) $allowances,
                        'deductions' => (float) $deductions,
                        'net' => (float) $netSalary,
                    ],
                ];
            });

            return Inertia::render('Payroll/Generate', [
                'payrollData' => $payrollData,
                'month' => $month,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate payroll: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'payments' => 'required|array',
            'payments.*.staff_id' => 'required|exists:staff,id',
            'payments.*.base_salary' => 'required|numeric|min:0',
            'payments.*.allowances' => 'required|numeric|min:0',
            'payments.*.commissions' => 'required|numeric|min:0',
            'payments.*.deductions' => 'required|numeric|min:0',
            'payments.*.net_salary' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            foreach ($request->payments as $payment) {
                SalaryPayment::updateOrCreate(
                    [
                        'staff_id' => $payment['staff_id'],
                        'pay_month' => $request->month,
                        'pay_year' => $request->year,
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
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save payroll: ' . $e->getMessage());
        }
    }

    public function markAsPaid(SalaryPayment $salaryPayment)
    {
        try {
            $salaryPayment->update([
                'status' => 'paid',
                'paid_date' => now(),
            ]);

            return redirect()->back()->with('success', 'Payment marked as paid.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark payment as paid: ' . $e->getMessage());
        }
    }
}
