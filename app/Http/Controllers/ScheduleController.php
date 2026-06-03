<?php

namespace App\Http\Controllers;

use App\Models\CollectionSchedule;
use App\Models\Zone;
use App\Models\Staff;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = CollectionSchedule::with(['zone', 'staff.user'])
            ->orderBy('created_at')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'zone_name' => $s->zone?->name,
                'staff_name' => $s->staff?->user?->name,
                'days_of_week' => json_decode($s->days_of_week, true),
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'is_active' => $s->is_active,
                'route_order' => $s->route_order,
            ]);

        $zones = Zone::all(['id', 'name']);
        $staff = Staff::with('user')->where('role', 'collector')->where('is_active', true)->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->user?->name]);

        return Inertia::render('Schedule/Index', [
            'schedules' => $schedules,
            'zones' => $zones,
            'staff' => $staff,
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:zones,id',
            'staff_id' => 'required|exists:staff,id',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'route_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $schedule = CollectionSchedule::create([
                'zone_id' => $request->zone_id,
                'staff_id' => $request->staff_id,
                'days_of_week' => json_encode($request->days_of_week),
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'route_order' => $request->route_order ?? 0,
                'is_active' => true,
            ]);
            AuditLog::log('schedule.create', 'CollectionSchedule', $schedule->id, $request->all());
            DB::commit();
            return redirect()->route('schedules.index')->with('success', 'Schedule added.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, CollectionSchedule $schedule)
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:zones,id',
            'staff_id' => 'required|exists:staff,id',
            'days_of_week' => 'required|array',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
            'route_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $old = $schedule->toArray();
            $schedule->update([
                'zone_id' => $request->zone_id,
                'staff_id' => $request->staff_id,
                'days_of_week' => json_encode($request->days_of_week),
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'route_order' => $request->route_order ?? 0,
                'is_active' => $request->is_active ?? true,
            ]);
            AuditLog::log('schedule.update', 'CollectionSchedule', $schedule->id, ['old' => $old, 'new' => $request->all()]);
            DB::commit();
            return back()->with('success', 'Schedule updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(CollectionSchedule $schedule)
    {
        DB::beginTransaction();
        try {
            $schedule->delete();
            AuditLog::log('schedule.delete', 'CollectionSchedule', $schedule->id);
            DB::commit();
            return back()->with('success', 'Schedule deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // Generate weekly route plan
    public function generateWeeklyPlan(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $schedules = CollectionSchedule::with(['zone', 'staff.user'])
            ->where('is_active', true)
            ->get();

        $plan = [];
        foreach ($schedules as $schedule) {
            $days = json_decode($schedule->days_of_week, true);
            foreach ($days as $dayNum) {
                $date = Carbon::parse($startDate)->addDays($dayNum - 1);
                $plan[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->format('l'),
                    'zone' => $schedule->zone->name,
                    'staff' => $schedule->staff->user->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'route_order' => $schedule->route_order,
                ];
            }
        }
        usort($plan, fn($a, $b) => $a['date'] <=> $b['date']);

        return Inertia::render('Schedule/Plan', ['plan' => $plan, 'start_date' => $startDate]);
    }
}