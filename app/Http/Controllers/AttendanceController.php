<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index()
    {
        return Inertia::render('Attendance/Index', [
            'staff' => Staff::with('user', 'zone')
                ->where('is_active', true)
                ->where('role', 'collector')
                ->orderBy('created_at')
                ->get(),
            'todayAttendance' => AttendanceRecord::with('staff.user')
                ->whereDate('work_date', today())
                ->get(),
            'date' => today()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'work_date' => 'required|date',
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date',
            'status' => 'required|in:present,absent,leave,half_day',
            'notes' => 'nullable|string',
        ]);

        AttendanceRecord::updateOrCreate(
            [
                'staff_id' => $validated['staff_id'],
                'work_date' => $validated['work_date'],
            ],
            $validated
        );

        return redirect()->back()->with('success', 'Attendance recorded successfully.');
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.staff_id' => 'required|exists:staff,id',
            'attendance.*.status' => 'required|in:present,absent,leave,half_day',
            'attendance.*.notes' => 'nullable|string',
        ]);

        foreach ($validated['attendance'] as $record) {
            AttendanceRecord::updateOrCreate(
                [
                    'staff_id' => $record['staff_id'],
                    'work_date' => $validated['date'],
                ],
                [
                    'status' => $record['status'],
                    'notes' => $record['notes'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Bulk attendance recorded successfully.');
    }

    public function monthly(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        return Inertia::render('Attendance/Monthly', [
            'staff' => Staff::with('user', 'zone')
                ->where('is_active', true)
                ->where('role', 'collector')
                ->orderBy('created_at')
                ->get(),
            'attendance' => AttendanceRecord::with('staff.user')
                ->whereMonth('work_date', $month)
                ->whereYear('work_date', $year)
                ->get(),
            'month' => $month,
            'year' => $year,
        ]);
    }
}
