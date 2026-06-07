<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Notifications\LeaveStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            // Real leave requests
            $leaveRequests = LeaveRequest::with('staff.user')
                ->where('status', 'pending')
                ->whereMonth('start_date', $month)
                ->whereYear('start_date', $year)
                ->get()
                ->map(fn ($lr) => [
                    'id' => $lr->id,
                    'staff_name' => $lr->staff->user->name ?? 'Unknown',
                    'leave_type' => $lr->leave_type,
                    'start_date' => $lr->start_date->format('Y-m-d'),
                    'end_date' => $lr->end_date->format('Y-m-d'),
                    'days' => $lr->days,
                    'reason' => $lr->reason,
                ]);

            return Inertia::render('Attendance/Index', [
                'staff' => Staff::with('user', 'zone')
                    ->where('is_active', true)
                    ->where('role', 'collector')
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->user?->name,
                        'phone' => $s->phone,
                        'zone' => $s->zone?->name,
                    ]),
                'todayAttendance' => AttendanceRecord::with('staff.user')
                    ->whereDate('work_date', today())
                    ->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'staff_id' => $r->staff_id,
                        'staff_name' => $r->staff->user?->name,
                        'status' => $r->status,
                        'clock_in' => $r->clock_in?->format('H:i:s'),
                        'clock_out' => $r->clock_out?->format('H:i:s'),
                        'overtime_hours' => $r->overtime_hours,
                    ]),
                'pendingLeaveRequests' => $leaveRequests,
                'month' => $month,
                'year' => $year,
                'summary' => $this->getMonthlySummary($month, $year),
            ]);
        } catch (\Exception $e) {
            // Render the page with safe defaults rather than redirecting back,
            // which can cause a redirect loop when the referrer is this page.
            return Inertia::render('Attendance/Index', [
                'staff' => [],
                'todayAttendance' => [],
                'pendingLeaveRequests' => [],
                'month' => (int) $request->input('month', now()->month),
                'year' => (int) $request->input('year', now()->year),
                'summary' => [
                    'total_present' => 0,
                    'total_absent' => 0,
                    'total_half_days' => 0,
                    'total_leave' => 0,
                    'total_overtime' => 0,
                ],
                'error' => 'Failed to load attendance: '.$e->getMessage(),
            ]);
        }
    }

    private function getMonthlySummary($month, $year)
    {
        return [
            'total_present' => AttendanceRecord::whereMonth('work_date', $month)
                ->whereYear('work_date', $year)
                ->where('status', 'present')->count(),
            'total_absent' => AttendanceRecord::whereMonth('work_date', $month)
                ->whereYear('work_date', $year)
                ->where('status', 'absent')->count(),
            'total_half_days' => AttendanceRecord::whereMonth('work_date', $month)
                ->whereYear('work_date', $year)
                ->where('status', 'half_day')->count(),
            'total_leave' => AttendanceRecord::whereMonth('work_date', $month)
                ->whereYear('work_date', $year)
                ->where('status', 'leave')->count(),
            'total_overtime' => AttendanceRecord::whereMonth('work_date', $month)
                ->whereYear('work_date', $year)
                ->sum('overtime_hours'),
        ];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'work_date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s|after:clock_in',
            'status' => 'required|in:present,absent,leave,half_day',
            'notes' => 'nullable|string|max:500',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $attendance = AttendanceRecord::updateOrCreate(
                ['staff_id' => $request->staff_id, 'work_date' => $request->work_date],
                [
                    'clock_in' => $request->clock_in,
                    'clock_out' => $request->clock_out,
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'overtime_hours' => $request->overtime_hours,
                ]
            );

            AuditLog::log('attendance.create', 'Attendance', $attendance->id, ['data' => $request->all()]);

            DB::commit();

            return back()->with('success', 'Attendance recorded.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed: '.$e->getMessage());
        }
    }

    public function bulkClock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_ids' => 'required|array',
            'staff_ids.*' => 'exists:staff,id',
            'action' => 'required|in:clock_in,clock_out',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $now = now()->format('H:i:s');
        $count = 0;

        foreach ($request->staff_ids as $staffId) {
            $record = AttendanceRecord::firstOrCreate(
                ['staff_id' => $staffId, 'work_date' => $request->date],
                ['status' => 'present']
            );

            if ($request->action === 'clock_in' && ! $record->clock_in) {
                $record->clock_in = $now;
                $record->save();
                $count++;
            } elseif ($request->action === 'clock_out' && ! $record->clock_out) {
                $record->clock_out = $now;
                // Auto-calc overtime if clock_out > 17:00
                if ($record->clock_in && strtotime($now) > strtotime('17:00:00')) {
                    $record->overtime_hours = (strtotime($now) - strtotime($record->clock_in)) / 3600 - 8;
                }
                $record->save();
                $count++;
            }
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function monthlyReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $attendance = AttendanceRecord::with('staff.user')
            ->whereMonth('work_date', $month)
            ->whereYear('work_date', $year)
            ->get();

        // Calculate late arrivals (clock_in > 08:30)
        $lateCount = $attendance->filter(fn ($a) => $a->clock_in && strtotime($a->clock_in) > strtotime('08:30:00'))->count();

        $data = [
            'month' => $month,
            'year' => $year,
            'summary' => $this->getMonthlySummary($month, $year),
            'late_arrivals' => $lateCount,
            'details' => $attendance->map(fn ($a) => [
                'staff' => $a->staff->user?->name,
                'date' => $a->work_date->format('Y-m-d'),
                'status' => $a->status,
                'clock_in' => $a->clock_in,
                'clock_out' => $a->clock_out,
                'overtime' => $a->overtime_hours,
            ]),
        ];

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($data['details'], "attendance_{$year}_{$month}.csv");
        }

        return Inertia::render('Attendance/Report', $data);
    }

    public function storeLeaveRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'leave_type' => 'required|in:vacation,sick,emergency,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $days = now()->parse($request->start_date)->diffInDays(now()->parse($request->end_date)) + 1;

        $leave = LeaveRequest::create([
            'staff_id' => $request->staff_id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $days,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // Notify supervisors (in real app, get role-based users)
        // Notification::route('mail', 'supervisor@example.com')->notify(new LeaveRequestSubmitted($leave));

        return back()->with('success', 'Leave request submitted.');
    }

    public function approveLeave($id)
    {
        DB::beginTransaction();
        try {
            $leave = LeaveRequest::findOrFail($id);
            $leave->update(['status' => 'approved']);

            // Auto-mark attendance as leave for those days
            $start = now()->parse($leave->start_date);
            $end = now()->parse($leave->end_date);
            for ($date = $start; $date->lte($end); $date->addDay()) {
                AttendanceRecord::updateOrCreate(
                    ['staff_id' => $leave->staff_id, 'work_date' => $date],
                    ['status' => 'leave', 'notes' => 'Approved leave: '.$leave->leave_type]
                );
            }

            if ($leave->staff->user) {
                Notification::send($leave->staff->user, new LeaveStatusChanged($leave, 'approved'));
            }

            AuditLog::log('leave.approve', 'LeaveRequest', $id);
            DB::commit();

            return back()->with('success', 'Leave approved.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectLeave($id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $leave->update(['status' => 'rejected']);

        if ($leave->staff->user) {
            Notification::send($leave->staff->user, new LeaveStatusChanged($leave, 'rejected'));
        }

        AuditLog::log('leave.reject', 'LeaveRequest', $id);

        return back()->with('success', 'Leave rejected.');
    }

    private function exportCsv($data, $filename)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys((array) $data[0] ?? []));
            foreach ($data as $row) {
                fputcsv($file, (array) $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
