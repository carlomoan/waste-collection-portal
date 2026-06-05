<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\PerformanceRating;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\CollectionSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                    'id'           => $s->id,
                    'name'         => $s->user?->name,
                    'phone'        => $s->phone,
                    'role'         => $s->role,
                    'zone'         => $s->zone?->name,
                    'zone_id'      => $s->zone_id,
                    'base_salary'  => $s->base_salary,
                    'national_id'  => $s->national_id,
                    'hire_date'    => $s->hire_date,
                    'staff_number' => $s->staff_number,
                    'is_active'    => $s->is_active,
                    'avatar'       => $s->avatar,
                ]),
            'zones' => \App\Models\Zone::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'role'        => 'required|string|max:50',
            'zone_id'     => 'nullable|exists:zones,id',
            'base_salary' => 'nullable|numeric|min:0',
            'national_id' => 'nullable|string|max:50',
            'hire_date'   => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $slug  = Str::slug($request->name, '.');
            $email = $slug . '.' . Str::random(4) . '@staff.wcportal.local';

            $user = User::create([
                'name'     => $request->name,
                'email'    => $email,
                'password' => Hash::make(Str::random(12)),
                'role'     => 'staff',
            ]);

            $staff = Staff::create([
                'user_id'     => $user->id,
                'phone'       => $request->phone,
                'role'        => $request->role,
                'zone_id'     => $request->zone_id,
                'base_salary' => $request->base_salary ?? 0,
                'national_id' => $request->national_id,
                'hire_date'   => $request->hire_date ?? now()->toDateString(),
                'is_active'   => true,
            ]);

            AuditLog::log('staff.create', 'Staff', $staff->id, $request->all());
            DB::commit();
            return back()->with('success', 'Staff member added.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Staff $staff, Request $request)
    {
        $staff->load('user', 'zone');

        $data = [
            'id'          => $staff->id,
            'name'        => $staff->user?->name,
            'email'       => $staff->user?->email,
            'phone'       => $staff->phone,
            'role'        => $staff->role,
            'zone'        => $staff->zone?->name,
            'zone_id'     => $staff->zone_id,
            'base_salary' => $staff->base_salary,
            'national_id' => $staff->national_id,
            'hire_date'   => $staff->hire_date,
            'is_active'   => $staff->is_active,
            'staff_number'=> $staff->staff_number,
        ];

        if ($request->wantsJson()) {
            return response()->json(['staff' => $data]);
        }

        return back();
    }

    public function update(Request $request, Staff $staff)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'role'        => 'required|string|max:50',
            'zone_id'     => 'nullable|exists:zones,id',
            'base_salary' => 'nullable|numeric|min:0',
            'national_id' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $staff->user?->update(['name' => $request->name]);
            $old = $staff->toArray();
            $staff->update($request->only('phone', 'role', 'zone_id', 'base_salary', 'national_id'));
            AuditLog::log('staff.update', 'Staff', $staff->id, $request->all(), $old);
            DB::commit();
            return back()->with('success', 'Staff updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Staff $staff)
    {
        AuditLog::log('staff.delete', 'Staff', $staff->id, null, $staff->toArray());
        $staff->update(['is_active' => false]);
        $staff->delete();
        return back()->with('success', 'Staff member removed.');
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
        $staff->update(['is_active' => false]);
        return back()->with('success', 'Staff archived.');
    }

    public function restore($id)
    {
        $staff = Staff::withTrashed()->findOrFail($id);
        $staff->restore();
        $staff->update(['is_active' => true]);
        return back()->with('success', 'Staff restored.');
    }

    public function profile(Staff $staff)
    {
        $staff->load(['user', 'zone']);
        return Inertia::render('Staff/StaffProfile', ['staff' => $staff]);
    }
}