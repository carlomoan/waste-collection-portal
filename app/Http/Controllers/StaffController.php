<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\AttendanceRecord;
use App\Models\CollectionSession;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        try {
            return Inertia::render('Staff/Index', [
                'staff' => Staff::with('user', 'zone')
                    ->where('is_active', true)
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($staffMember) {
                        return [
                            'id' => $staffMember->id ?? null,
                            'name' => $staffMember->user?->name ?? 'Unknown',
                            'phone' => $staffMember->phone ?? 'N/A',
                            'role' => $staffMember->role ?? 'Unknown',
                            'zone' => $staffMember->zone?->name ?? 'Unassigned',
                            'base_salary' => (float) ($staffMember->base_salary ?? 0),
                            'is_active' => $staffMember->is_active ?? false,
                        ];
                    }),
                'zones' => \App\Models\Zone::all()->map(function ($zone) {
                    return [
                        'id' => $zone->id ?? null,
                        'name' => $zone->name ?? 'Unknown',
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load staff data: ' . $e->getMessage());
        }
    }

    public function show(Staff $staff)
    {
        try {
            return Inertia::render('Staff/Show', [
                'staff' => [
                    'id' => $staff->id,
                    'name' => $staff->user?->name ?? 'Unknown',
                    'phone' => $staff->phone ?? 'N/A',
                    'role' => $staff->role ?? 'Unknown',
                    'zone' => $staff->zone?->name ?? 'Unassigned',
                    'base_salary' => (float) ($staff->base_salary ?? 0),
                    'hire_date' => $staff->hire_date?->format('Y-m-d'),
                ],
                'attendance' => AttendanceRecord::where('staff_id', $staff->id)
                    ->whereMonth('work_date', now()->month)
                    ->whereYear('work_date', now()->year)
                    ->orderBy('work_date')
                    ->get()
                    ->map(function ($record) {
                        return [
                            'id' => $record->id ?? null,
                            'work_date' => $record->work_date?->format('Y-m-d'),
                            'status' => $record->status ?? 'unknown',
                            'clock_in' => $record->clock_in?->format('H:i:s'),
                            'clock_out' => $record->clock_out?->format('H:i:s'),
                        ];
                    }),
                'collectionSessions' => CollectionSession::where('staff_id', $staff->id)
                    ->whereMonth('session_date', now()->month)
                    ->whereYear('session_date', now()->year)
                    ->with('payments')
                    ->orderBy('session_date')
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id ?? null,
                            'session_date' => $session->session_date?->format('Y-m-d'),
                            'planned_amount' => (float) ($session->planned_amount ?? 0),
                            'actual_amount' => (float) ($session->actual_amount ?? 0),
                            'status' => $session->status ?? 'pending',
                        ];
                    }),
                'performance' => app(ReportService::class)->collectorPerformance($staff->id, now()->month, now()->year),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load staff details: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'national_id' => 'nullable|string|max:50',
            'phone' => 'required|string|max:20',
            'zone_id' => 'nullable|exists:zones,id',
            'role' => 'required|in:collector,supervisor,accountant,manager,admin',
            'base_salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            Staff::create([
                'user_id' => $request->user_id,
                'national_id' => $request->national_id,
                'phone' => $request->phone,
                'zone_id' => $request->zone_id,
                'role' => $request->role,
                'base_salary' => $request->base_salary,
                'hire_date' => $request->hire_date,
                'is_active' => true,
            ]);

            return redirect()->back()->with('success', 'Staff member created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create staff member: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Staff $staff)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'nullable|string|max:50',
            'phone' => 'required|string|max:20',
            'zone_id' => 'nullable|exists:zones,id',
            'role' => 'required|in:collector,supervisor,accountant,manager,admin',
            'base_salary' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $staff->update([
                'national_id' => $request->national_id,
                'phone' => $request->phone,
                'zone_id' => $request->zone_id,
                'role' => $request->role,
                'base_salary' => $request->base_salary,
                'is_active' => $request->is_active ?? true,
            ]);

            return redirect()->back()->with('success', 'Staff member updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update staff member: ' . $e->getMessage());
        }
    }
}
