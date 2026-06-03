<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Models\FuelLog;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with('driver.user', 'currentMaintenance');

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('is_hired')) {
            $query->where('is_hired', $request->boolean('is_hired'));
        }

        $vehicles = $query->orderBy('plate_number')->get()->map(fn($v) => [
            'id' => $v->id,
            'plate_number' => $v->plate_number,
            'type' => $v->type,
            'driver_name' => $v->driver?->user?->name ?? 'Unassigned',
            'status' => $v->status,
            'fuel_level' => $v->fuel_level,
            'last_service' => $v->last_service?->format('Y-m-d'),
            'is_hired' => $v->is_hired,
            'hire_end_date' => $v->hire_end_date?->format('Y-m-d'),
            'insurance_expiry' => $v->insurance_expiry?->format('Y-m-d'),
            'insurance_expiring_soon' => $v->insurance_expiry && $v->insurance_expiry->diffInDays(now()) <= 30,
        ]);

        $maintenanceSchedule = VehicleMaintenance::with('vehicle')
            ->where('status', 'pending')
            ->where('scheduled_date', '>=', now())
            ->orderBy('scheduled_date')
            ->limit(15)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'plate_number' => $m->vehicle->plate_number,
                'type' => $m->type,
                'scheduled_date' => $m->scheduled_date->format('Y-m-d'),
                'description' => $m->description,
                'estimated_cost' => (float) $m->estimated_cost,
                'status' => $m->status,
            ]);

        $drivers = Staff::where('role', 'driver')->with('user')->get()->map(fn($d) => [
            'id' => $d->id,
            'name' => $d->user->name ?? 'Unknown',
        ]);

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'maintenanceSchedule' => $maintenanceSchedule,
            'drivers' => $drivers,
            'filters' => $request->only(['status', 'type', 'is_hired']),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|string|max:20|unique:vehicles',
            'type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:staff,id',
            'fuel_level' => 'nullable|integer|min:0|max:100',
            'last_service' => 'nullable|date',
            'purchase_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date|after:today',
            'is_hired' => 'boolean',
            'payment_type' => 'nullable|required_if:is_hired,true|in:per_trip,per_day',
            'hire_fee' => 'nullable|required_if:is_hired,true|numeric|min:0',
            'hire_start_date' => 'nullable|required_if:is_hired,true|date',
            'hire_end_date' => 'nullable|required_if:is_hired,true|date|after:hire_start_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $vehicle = Vehicle::create([
                'plate_number' => $request->plate_number,
                'type' => $request->type,
                'driver_id' => $request->driver_id,
                'status' => 'active',
                'fuel_level' => $request->fuel_level ?? 0,
                'last_service' => $request->last_service,
                'purchase_date' => $request->purchase_date,
                'insurance_expiry' => $request->insurance_expiry,
                'is_hired' => $request->is_hired ?? false,
                'payment_type' => $request->payment_type,
                'hire_fee' => $request->hire_fee,
                'hire_start_date' => $request->hire_start_date,
                'hire_end_date' => $request->hire_end_date,
                'notes' => $request->notes,
            ]);

            AuditLog::log('vehicle.create', 'Vehicle', $vehicle->id, $request->all());

            DB::commit();
            return redirect()->route('vehicles.index')->with('success', 'Vehicle added.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load('driver.user', 'maintenanceSchedules', 'fuelLogs');

        // Fuel consumption trend (last 6 months)
        $fuelTrend = FuelLog::where('vehicle_id', $vehicle->id)
            ->where('refill_date', '>=', now()->subMonths(6))
            ->orderBy('refill_date')
            ->get()
            ->map(fn($log) => [
                'date' => $log->refill_date->format('Y-m-d'),
                'liters' => $log->liters,
                'cost' => (float) $log->cost,
                'odometer' => $log->odometer_km,
            ]);

        // Maintenance cost summary
        $maintenanceCost = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->where('status', 'completed')
            ->sum('actual_cost');

        return Inertia::render('Vehicles/Show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'type' => $vehicle->type,
                'driver_name' => $vehicle->driver?->user?->name ?? 'Unassigned',
                'status' => $vehicle->status,
                'fuel_level' => $vehicle->fuel_level,
                'last_service' => $vehicle->last_service?->format('Y-m-d'),
                'purchase_date' => $vehicle->purchase_date?->format('Y-m-d'),
                'insurance_expiry' => $vehicle->insurance_expiry?->format('Y-m-d'),
                'is_hired' => $vehicle->is_hired,
                'hire_start_date' => $vehicle->hire_start_date?->format('Y-m-d'),
                'hire_end_date' => $vehicle->hire_end_date?->format('Y-m-d'),
                'hire_fee' => (float) $vehicle->hire_fee,
                'notes' => $vehicle->notes,
            ],
            'maintenanceSchedule' => $vehicle->maintenanceSchedules->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'scheduled_date' => $m->scheduled_date->format('Y-m-d'),
                'completed_date' => $m->completed_date?->format('Y-m-d'),
                'estimated_cost' => (float) $m->estimated_cost,
                'actual_cost' => (float) $m->actual_cost,
                'status' => $m->status,
                'notes' => $m->notes,
            ]),
            'fuelLogs' => $fuelTrend,
            'maintenanceCost' => (float) $maintenanceCost,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,'.$vehicle->id,
            'type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:staff,id',
            'status' => 'required|in:active,inactive,maintenance',
            'fuel_level' => 'nullable|integer|min:0|max:100',
            'last_service' => 'nullable|date',
            'purchase_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'is_hired' => 'boolean',
            'payment_type' => 'nullable|required_if:is_hired,true|in:per_trip,per_day',
            'hire_fee' => 'nullable|required_if:is_hired,true|numeric|min:0',
            'hire_start_date' => 'nullable|required_if:is_hired,true|date',
            'hire_end_date' => 'nullable|required_if:is_hired,true|date|after:hire_start_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $old = $vehicle->toArray();
            $vehicle->update($request->all());
            AuditLog::log('vehicle.update', 'Vehicle', $vehicle->id, ['old' => $old, 'new' => $request->all()]);
            DB::commit();
            return redirect()->route('vehicles.index')->with('success', 'Vehicle updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Vehicle $vehicle)
    {
        if ($vehicle->maintenanceSchedules()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Cannot delete vehicle with pending maintenance.');
        }
        DB::beginTransaction();
        try {
            $vehicle->delete();
            AuditLog::log('vehicle.delete', 'Vehicle', $vehicle->id);
            DB::commit();
            return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // --- Maintenance Management ---
    public function scheduleMaintenance(Request $request, Vehicle $vehicle)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:oil_change,tire_rotation,engine_repair,general_service,other',
            'scheduled_date' => 'required|date|after:today',
            'description' => 'required|string|max:500',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $maintenance = $vehicle->maintenanceSchedules()->create([
                'type' => $request->type,
                'scheduled_date' => $request->scheduled_date,
                'description' => $request->description,
                'estimated_cost' => $request->estimated_cost,
                'status' => 'pending',
            ]);
            $vehicle->update(['status' => 'maintenance']);
            AuditLog::log('maintenance.schedule', 'VehicleMaintenance', $maintenance->id, $request->all());
            DB::commit();
            return back()->with('success', 'Maintenance scheduled.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function completeMaintenance(Request $request, VehicleMaintenance $maintenance)
    {
        $validator = Validator::make($request->all(), [
            'actual_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $maintenance->update([
                'status' => 'completed',
                'completed_date' => now(),
                'actual_cost' => $request->actual_cost,
                'notes' => $request->notes,
            ]);
            $maintenance->vehicle->update(['last_service' => now()]);
            if ($maintenance->vehicle->status === 'maintenance') {
                $maintenance->vehicle->update(['status' => 'active']);
            }
            AuditLog::log('maintenance.complete', 'VehicleMaintenance', $maintenance->id);
            DB::commit();
            return back()->with('success', 'Maintenance completed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // --- Fuel Log ---
    public function addFuelLog(Request $request, Vehicle $vehicle)
    {
        $validator = Validator::make($request->all(), [
            'refill_date' => 'required|date',
            'liters' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'odometer_km' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $fuelLog = $vehicle->fuelLogs()->create([
                'refill_date' => $request->refill_date,
                'liters' => $request->liters,
                'cost' => $request->cost,
                'odometer_km' => $request->odometer_km,
                'notes' => $request->notes,
            ]);
            // Update current fuel level (assume full tank after refill)
            $vehicle->update(['fuel_level' => min(100, $vehicle->fuel_level + 10)]);
            AuditLog::log('fuel.add', 'FuelLog', $fuelLog->id, $request->all());
            DB::commit();
            return back()->with('success', 'Fuel log added.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // --- Export ---
    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        $vehicles = Vehicle::with('driver.user')->get();

        if ($format === 'csv') {
            $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="vehicles.csv"'];
            $callback = function () use ($vehicles) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Plate', 'Type', 'Driver', 'Status', 'Fuel%', 'Last Service', 'Ins. Expiry', 'Hired']);
                foreach ($vehicles as $v) {
                    fputcsv($file, [
                        $v->id, $v->plate_number, $v->type, $v->driver?->user?->name,
                        $v->status, $v->fuel_level, $v->last_service?->format('Y-m-d'),
                        $v->insurance_expiry?->format('Y-m-d'), $v->is_hired ? 'Yes' : 'No',
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }
        return back()->with('error', 'Unsupported format');
    }
}