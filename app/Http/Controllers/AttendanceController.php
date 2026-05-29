<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index()
    {
        try {
            return Inertia::render('Attendance/Index', [
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
                        ];
                    }),
                'todayAttendance' => AttendanceRecord::with('staff.user')
                    ->whereDate('work_date', today())
                    ->get()
                    ->map(function ($record) {
                        return [
                            'id' => $record->id ?? null,
                            'staff_id' => $record->staff_id ?? null,
                            'staff_name' => $record->staff?->user?->name ?? 'Unknown',
                            'status' => $record->status ?? 'unknown',
                            'clock_in' => $record->clock_in?->format('H:i:s'),
                            'clock_out' => $record->clock_out?->format('H:i:s'),
                        ];
                    }),
                'date' => today()->toDateString(),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load attendance data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'work_date' => 'required|date',
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date',
            'status' => 'required|in:present,absent,leave,half_day',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            AttendanceRecord::updateOrCreate(
                [
                    'staff_id' => $request->staff_id,
                    'work_date' => $request->work_date,
                ],
                [
                    'clock_in' => $request->clock_in,
                    'clock_out' => $request->clock_out,
                    'status' => $request->status,
                    'notes' => $request->notes,
                ]
            );

            return redirect()->back()->with('success', 'Attendance recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record attendance: ' . $e->getMessage());
        }
    }

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.staff_id' => 'required|exists:staff,id',
            'attendance.*.status' => 'required|in:present,absent,leave,half_day',
            'attendance.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            foreach ($request->attendance as $record) {
                AttendanceRecord::updateOrCreate(
                    [
                        'staff_id' => $record['staff_id'],
                        'work_date' => $request->date,
                    ],
                    [
                        'status' => $record['status'],
                        'notes' => $record['notes'] ?? null,
                    ]
                );
            }

            return redirect()->back()->with('success', 'Bulk attendance recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record bulk attendance: ' . $e->getMessage());
        }
    }

    public function monthly(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            return Inertia::render('Attendance/Monthly', [
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
                        ];
                    }),
                'attendance' => AttendanceRecord::with('staff.user')
                    ->whereMonth('work_date', $month)
                    ->whereYear('work_date', $year)
                    ->get()
                    ->map(function ($record) {
                        return [
                            'id' => $record->id ?? null,
                            'staff_id' => $record->staff_id ?? null,
                            'staff_name' => $record->staff?->user?->name ?? 'Unknown',
                            'work_date' => $record->work_date?->format('Y-m-d'),
                            'status' => $record->status ?? 'unknown',
                        ];
                    }),
                'month' => $month,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load monthly attendance: ' . $e->getMessage());
        }
    }
}
