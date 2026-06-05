<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\SalaryPayment;
use App\Models\SalaryAdvance;
use App\Models\AttendanceRecord;
use App\Models\CollectionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Mail\PaySlipMail;
use Illuminate\Support\Facades\Mail;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        return Inertia::render('Payroll/Index', [
            'staff' => Staff::with('user', 'zone')
                ->where('is_active', true)
                ->where('role', 'collector')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->user?->name,
                    'zone' => $s->zone?->name,
                    'base_salary' => $s->base_salary,
                ]),
            'salaryPayments' => SalaryPayment::with('staff.user')
                ->where('pay_month', $month)->where('pay_year', $year)
                ->get(),
            'month' => $month,
            'year' => $year,
            'payrollSummary' => $this->getPayrollSummary($month, $year),
        ]);
    }

    private function getPayrollSummary($month, $year)
    {
        $payments = SalaryPayment::where('pay_month', $month)->where('pay_year', $year)->get();
        return [
            'total_gross' => $payments->sum('base_salary') + $payments->sum('allowances') + $payments->sum('commissions'),
            'total_deductions' => $payments->sum('deductions'),
            'total_net' => $payments->sum('net_salary'),
            'paid_count' => $payments->where('status', 'paid')->count(),
            'pending_count' => $payments->where('status', 'pending')->count(),
        ];
    }

    public function generate(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $staff = Staff::with('user', 'zone')->where('is_active', true)->where('role', 'collector')->get();
        $payrollData = [];

        foreach ($staff as $staffMember) {
            // Attendance
            $attendance = AttendanceRecord::where('staff_id', $staffMember->id)
                ->whereMonth('work_date', $month)->whereYear('work_date', $year)->get();
            $presentDays = $attendance->where('status', 'present')->count();
            $halfDays = $attendance->where('status', 'half_day')->count();
            $effectiveDays = $presentDays + ($halfDays * 0.5);

            // Collections
            $collections = CollectionSession::where('staff_id', $staffMember->id)
                ->whereMonth('session_date', $month)->whereYear('session_date', $year)
                ->sum('actual_amount') ?? 0;
            $commission = $collections * 0.05;

            // Salary Advance deductions
            $advanceDeduction = SalaryAdvance::where('staff_id', $staffMember->id)
                ->where('status', 'approved')
                ->where(function ($q) use ($month, $year) {
                    $q->whereNull('deduction_month')->orWhere('deduction_month', $month);
                })->sum('amount') ?? 0;

            $baseSalary = $staffMember->base_salary ?? 0;
            $allowances = 0;
            $gross = $baseSalary + $allowances + $commission;
            $paye = $this->calculatePAYE($gross);
            $nssf = $this->calculateNSSF($gross);
            $deductions = $advanceDeduction + $paye + $nssf;
            $netSalary = $gross - $deductions;

            $payrollData[] = [
                'staff' => [
                    'id' => $staffMember->id,
                    'name' => $staffMember->user?->name,
                    'zone' => $staffMember->zone?->name,
                    'base_salary' => $baseSalary,
                ],
                'attendance' => ['present' => $presentDays, 'half_days' => $halfDays, 'effective_days' => $effectiveDays],
                'collections' => $collections,
                'commission' => $commission,
                'salary' => [
                    'base' => $baseSalary,
                    'allowances' => $allowances,
                    'gross' => $gross,
                    'paye' => $paye,
                    'nssf' => $nssf,
                    'advance' => $advanceDeduction,
                    'deductions' => $deductions,
                    'net' => $netSalary,
                ],
            ];
        }

        return Inertia::render('Payroll/Generate', ['payrollData' => $payrollData, 'month' => $month, 'year' => $year]);
    }

    private function calculatePAYE($taxableIncome)
    {
        // Tanzania PAYE rates (simplified)
        if ($taxableIncome <= 270000) return 0;
        if ($taxableIncome <= 520000) return ($taxableIncome - 270000) * 0.08;
        if ($taxableIncome <= 760000) return 20000 + ($taxableIncome - 520000) * 0.09;
        if ($taxableIncome <= 1000000) return 41600 + ($taxableIncome - 760000) * 0.10;
        return 65600 + ($taxableIncome - 1000000) * 0.10;
    }

    private function calculateNSSF($income)
    {
        // NSSF Tier I & II simplified: 10% of pensionable income up to cap
        $pensionableCap = 1000000;
        $employeeRate = 0.10;
        return min($income, $pensionableCap) * $employeeRate;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'payments' => 'required|array',
            'payments.*.staff_id' => 'required|exists:staff,id',
            'payments.*.base_salary' => 'required|numeric|min:0',
            'payments.*.allowances' => 'numeric|min:0',
            'payments.*.commissions' => 'numeric|min:0',
            'payments.*.deductions' => 'numeric|min:0',
            'payments.*.net_salary' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            foreach ($request->payments as $payment) {
                SalaryPayment::updateOrCreate(
                    ['staff_id' => $payment['staff_id'], 'pay_month' => $request->month, 'pay_year' => $request->year],
                    [
                        'base_salary' => $payment['base_salary'],
                        'allowances' => $payment['allowances'] ?? 0,
                        'commissions' => $payment['commissions'] ?? 0,
                        'deductions' => $payment['deductions'] ?? 0,
                        'net_salary' => $payment['net_salary'],
                        'status' => 'pending',
                    ]
                );
            }
            DB::commit();
            return redirect()->route('payroll.index')->with('success', 'Payroll generated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function generatePayslip(SalaryPayment $salaryPayment)
    {
        $salaryPayment->load('staff.user', 'staff.zone');
        return Pdf::view('pdf.payslip', ['payment' => $salaryPayment])
            ->format('A4')
            ->download("payslip_{$salaryPayment->staff_id}_{$salaryPayment->pay_month}_{$salaryPayment->pay_year}.pdf");
    }

    public function emailPayslip(SalaryPayment $salaryPayment)
    {
        $staff = $salaryPayment->staff;
        if (!$staff->user || !$staff->user->email) {
            return back()->with('error', 'Staff email not found.');
        }

        $pdf = Pdf::view('pdf.payslip', ['payment' => $salaryPayment])->output();
        Mail::to($staff->user->email)->send(new PaySlipMail($pdf, $salaryPayment));

        return back()->with('success', 'Payslip emailed.');
    }

    public function export(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $payments = SalaryPayment::where('pay_month', $month)->where('pay_year', $year)
            ->with('staff.user')
            ->get();

        $csv = "Staff Name,Base Salary,Allowances,Commissions,Deductions,Net Salary,Status\n";
        foreach ($payments as $p) {
            $csv .= "{$p->staff->user->name},{$p->base_salary},{$p->allowances},{$p->commissions},{$p->deductions},{$p->net_salary},{$p->status}\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, "payroll_{$year}_{$month}.csv");
    }

    public function exportBankFile(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $payments = SalaryPayment::where('pay_month', $month)->where('pay_year', $year)
            ->where('status', 'pending')
            ->with('staff.user')
            ->get();

        // Generate NACHA or local bank format
        $content = "BANK_CODE,ACCOUNT_NUMBER,AMOUNT,ACCOUNT_NAME\n";
        foreach ($payments as $p) {
            $content .= "CRDB,{$p->staff->bank_account_number},{$p->net_salary},{$p->staff->user->name}\n";
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, "payroll_{$year}_{$month}.csv");
    }

    // Salary Advance methods
    public function requestAdvance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'amount' => 'required|numeric|min:1|max:'.(auth()->user()->staff->base_salary * 0.5),
            'reason' => 'required|string|max:500',
        ]);

        $advance = SalaryAdvance::create([
            'staff_id' => $request->staff_id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return back()->with('success', 'Advance request submitted.');
    }

    public function approveAdvance($id)
    {
        $advance = SalaryAdvance::findOrFail($id);
        $advance->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        return back()->with('success', 'Advance approved.');
    }

    public function processPayments(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $count = SalaryPayment::where('pay_month', $month)->where('pay_year', $year)
            ->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', "{$count} payments processed.");
    }
}