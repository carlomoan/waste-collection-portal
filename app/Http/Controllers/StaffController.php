<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\AttendanceRecord;
use App\Models\CollectionSession;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        return Inertia::render('Staff/Index', [
            'staff' => Staff::with('user', 'zone')
                ->where('is_active', true)
                ->orderBy('created_at')
                ->get(),
            'zones' => \App\Models\Zone::all(),
        ]);
    }

    public function show(Staff $staff)
    {
        return Inertia::render('Staff/Show', [
            'staff' => $staff->load('user', 'zone'),
            'attendance' => AttendanceRecord::where('staff_id', $staff->id)
                ->whereMonth('work_date', now()->month)
                ->whereYear('work_date', now()->year)
                ->orderBy('work_date')
                ->get(),
            'collectionSessions' => CollectionSession::where('staff_id', $staff->id)
                ->whereMonth('session_date', now()->month)
                ->whereYear('session_date', now()->year)
                ->with('payments')
                ->orderBy('session_date')
                ->get(),
            'performance' => app(ReportService::class)->collectorPerformance($staff->id, now()->month, now()->year),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'national_id' => 'nullable|string',
            'phone' => 'required|string',
            'zone_id' => 'nullable|exists:zones,id',
            'role' => 'required|in:collector,supervisor,accountant,manager,admin',
            'base_salary' => 'required|numeric',
            'hire_date' => 'required|date',
        ]);

        Staff::create($validated);

        return redirect()->back()->with('success', 'Staff member created successfully.');
    }

    public function update(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'national_id' => 'nullable|string',
            'phone' => 'required|string',
            'zone_id' => 'nullable|exists:zones,id',
            'role' => 'required|in:collector,supervisor,accountant,manager,admin',
            'base_salary' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $staff->update($validated);

        return redirect()->back()->with('success', 'Staff member updated successfully.');
    }
}
