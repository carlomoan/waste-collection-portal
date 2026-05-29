<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class VehicleController extends Controller
{
    public function index()
    {
        try {
            // Get vehicles with driver information
            $vehicles = Vehicle::with('driver')
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'id' => $vehicle->id ?? null,
                        'plate_number' => $vehicle->plate_number ?? 'N/A',
                        'type' => $vehicle->type ?? 'Unknown',
                        'driver' => $vehicle->driver?->name ?? 'Unassigned',
                        'status' => $vehicle->status ?? 'inactive',
                        'fuel_level' => $vehicle->fuel_level ?? 0,
                        'last_service' => $vehicle->last_service?->format('Y-m-d'),
                        'is_hired' => $vehicle->is_hired ?? false,
                        'hire_end_date' => $vehicle->hire_end_date?->format('Y-m-d'),
                    ];
                });

            // Get maintenance schedule (only for owned vehicles)
            $maintenanceSchedule = VehicleMaintenance::with('vehicle')
                ->whereHas('vehicle', function ($query) {
                    $query->where('is_hired', false);
                })
                ->where('status', '!=', 'completed')
                ->orderBy('scheduled_date')
                ->limit(10)
                ->get()
                ->map(function ($maintenance) {
                    return [
                        'id' => $maintenance->id ?? null,
                        'vehicle' => $maintenance->vehicle?->plate_number ?? 'Unknown',
                        'type' => $maintenance->type ?? 'Unknown',
                        'scheduled_date' => $maintenance->scheduled_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                        'status' => $maintenance->status ?? 'upcoming',
                    ];
                });

            return Inertia::render('Vehicles/Index', [
                'vehicles' => $vehicles,
                'maintenanceSchedule' => $maintenanceSchedule,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load vehicle data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $drivers = Staff::where('status', 'active')->get()->map(function ($staff) {
                return [
                    'id' => $staff->id ?? null,
                    'name' => $staff->name ?? 'Unknown',
                ];
            });

            return Inertia::render('Vehicles/Create', [
                'drivers' => $drivers,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load vehicle form: ' . $e->getMessage());
        }
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
            'is_hired' => 'nullable|boolean',
            'hire_start_date' => 'nullable|required_if:is_hired,true|date',
            'hire_end_date' => 'nullable|required_if:is_hired,true|date|after:hire_start_date',
            'hire_cost' => 'nullable|required_if:is_hired,true|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            Vehicle::create([
                'plate_number' => $request->plate_number,
                'type' => $request->type,
                'driver_id' => $request->driver_id,
                'status' => 'active',
                'fuel_level' => $request->fuel_level ?? 0,
                'last_service' => $request->last_service,
                'purchase_date' => $request->purchase_date,
                'insurance_expiry' => $request->insurance_expiry,
                'is_hired' => $request->is_hired ?? false,
                'hire_start_date' => $request->hire_start_date,
                'hire_end_date' => $request->hire_end_date,
                'hire_cost' => $request->hire_cost,
            ]);

            return redirect()->route('vehicles.index')->with('success', 'Vehicle added successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add vehicle: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $vehicle = Vehicle::with(['driver', 'maintenanceSchedules'])->findOrFail($id);
            
            return Inertia::render('Vehicles/Show', [
                'vehicle' => [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'type' => $vehicle->type,
                    'driver' => $vehicle->driver?->name ?? 'Unassigned',
                    'status' => $vehicle->status,
                    'fuel_level' => $vehicle->fuel_level,
                    'last_service' => $vehicle->last_service?->format('Y-m-d'),
                    'purchase_date' => $vehicle->purchase_date?->format('Y-m-d'),
                    'insurance_expiry' => $vehicle->insurance_expiry?->format('Y-m-d'),
                    'is_hired' => $vehicle->is_hired ?? false,
                    'hire_start_date' => $vehicle->hire_start_date?->format('Y-m-d'),
                    'hire_end_date' => $vehicle->hire_end_date?->format('Y-m-d'),
                    'hire_cost' => (float) ($vehicle->hire_cost ?? 0),
                ],
                'maintenanceSchedule' => $vehicle->maintenanceSchedules->map(function ($maintenance) {
                    return [
                        'id' => $maintenance->id,
                        'type' => $maintenance->type,
                        'scheduled_date' => $maintenance->scheduled_date?->format('Y-m-d'),
                        'completed_date' => $maintenance->completed_date?->format('Y-m-d'),
                        'status' => $maintenance->status,
                        'notes' => $maintenance->notes,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load vehicle: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            $drivers = Staff::where('status', 'active')->get()->map(function ($staff) {
                return [
                    'id' => $staff->id ?? null,
                    'name' => $staff->name ?? 'Unknown',
                ];
            });

            return Inertia::render('Vehicles/Edit', [
                'vehicle' => [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'type' => $vehicle->type,
                    'driver_id' => $vehicle->driver_id,
                    'status' => $vehicle->status,
                    'fuel_level' => $vehicle->fuel_level,
                    'last_service' => $vehicle->last_service?->format('Y-m-d'),
                    'purchase_date' => $vehicle->purchase_date?->format('Y-m-d'),
                    'insurance_expiry' => $vehicle->insurance_expiry?->format('Y-m-d'),
                    'is_hired' => $vehicle->is_hired ?? false,
                    'hire_start_date' => $vehicle->hire_start_date?->format('Y-m-d'),
                    'hire_end_date' => $vehicle->hire_end_date?->format('Y-m-d'),
                    'hire_cost' => (float) ($vehicle->hire_cost ?? 0),
                ],
                'drivers' => $drivers,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load vehicle: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $id,
            'type' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:staff,id',
            'status' => 'required|in:active,inactive,maintenance',
            'fuel_level' => 'nullable|integer|min:0|max:100',
            'last_service' => 'nullable|date',
            'purchase_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date|after:today',
            'is_hired' => 'nullable|boolean',
            'hire_start_date' => 'nullable|required_if:is_hired,true|date',
            'hire_end_date' => 'nullable|required_if:is_hired,true|date|after:hire_start_date',
            'hire_cost' => 'nullable|required_if:is_hired,true|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $vehicle = Vehicle::findOrFail($id);
            $vehicle->update([
                'plate_number' => $request->plate_number,
                'type' => $request->type,
                'driver_id' => $request->driver_id,
                'status' => $request->status,
                'fuel_level' => $request->fuel_level ?? 0,
                'last_service' => $request->last_service,
                'purchase_date' => $request->purchase_date,
                'insurance_expiry' => $request->insurance_expiry,
                'is_hired' => $request->is_hired ?? false,
                'hire_start_date' => $request->hire_start_date,
                'hire_end_date' => $request->hire_end_date,
                'hire_cost' => $request->hire_cost,
            ]);

            return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update vehicle: ' . $e->getMessage());
        }
    }
}
