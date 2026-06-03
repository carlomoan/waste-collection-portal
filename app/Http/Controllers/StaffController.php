<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\PerformanceRating;
use App\Models\AttendanceRecord;
use App\Models\CollectionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        return Inertia::render('Staff/Index', [
            'staff' => Staff::with('user', 'zone')
                ->orderBy('name')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->user?->name,
                    'phone' => $s->phone,
                    'role' => $s->role,
                    'zone' => $s->zone?->name,
                    'base_salary' => $s->base_salary,
                    'is_active' => $s->is_active,
                    'avatar' => $s->avatar,
                ]),
            'zones' => \App\Models\Zone::all(),
        ]);
    }

    public function show(Staff $staff)
    {
        $staff->load('user', 'zone');

        // Performance trend last 6 months
        $performanceTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $collections = CollectionSession::where('staff_id', $staff->id)
                ->whereYear('session_date', $month->year)->whereMonth('session_date', $month->month)
                ->sum('actual_amount');
            $performanceTrend[] = ['month' => $month->format('M Y'), 'collections' => $collections];
        }

        return Inertia::render('Staff/Show', [
            'staff' => $staff,
            'attendance' => AttendanceRecord::where('staff_id', $staff->id)
                ->whereMonth('work_date', now()->month)->orderBy('work_date')->get(),
            'collectionSessions' => CollectionSession::where('staff_id', $staff->id)
                ->whereMonth('session_date', now()->month)->with('payments')->get(),
            'documents' => StaffDocument::where('staff_id', $staff->id)->get(),
            'performanceTrend' => $performanceTrend,
            'emergencyContacts' => $staff->emergencyContacts,
            'ratings' => PerformanceRating::where('staff_id', $staff->id)->latest()->take(5)->get(),
        ]);
    }

    public function uploadDocument(Request $request, Staff $staff)
    {
        $request->validate([
            'type' => 'required|in:contract,id_card,license,other',
            'file' => 'required|file|max:5120|mimes:pdf,jpg,png',
            'description' => 'nullable|string',
        ]);

        $path = $request->file('file')->store("staff/{$staff->id}/documents", 'public');

        StaffDocument::create([
            'staff_id' => $staff->id,
            'type' => $request->type,
            'file_path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'description' => $request->description,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function addEmergencyContact(Request $request, Staff $staff)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
        ]);

        $staff->emergencyContacts()->create($request->all());
        return back()->with('success', 'Emergency contact added.');
    }

    public function ratePerformance(Request $request, Staff $staff)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string',
            'period' => 'required|date_format:Y-m',
        ]);

        PerformanceRating::create([
            'staff_id' => $staff->id,
            'rating' => $request->rating,
            'comments' => $request->comments,
            'period' => $request->period,
            'rated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Performance rating added.');
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);

        $import = new \App\Imports\StaffImport();
        \Excel::import($import, $request->file('file'));

        return back()->with('success', "Imported {$import->getRowCount()} staff members.");
    }

    public function archive(Staff $staff)
    {
        $staff->update(['is_active' => false, 'archived_at' => now()]);
        return back()->with('success', 'Staff archived.');
    }

    public function restore($id)
    {
        $staff = Staff::withTrashed()->findOrFail($id);
        $staff->restore();
        $staff->update(['is_active' => true]);
        return back()->with('success', 'Staff restored.');
    }
}