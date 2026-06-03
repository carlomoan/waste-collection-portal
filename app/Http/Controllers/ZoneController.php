<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::withCount('staff', 'clients')->get();
        return Inertia::render('Zones/Index', ['zones' => $zones]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:zones',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $zone = Zone::create($request->all());
        AuditLog::log('zone.create', 'Zone', $zone->id, $request->all());
        return redirect()->route('zones.index')->with('success', 'Zone created.');
    }

    public function update(Request $request, Zone $zone)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:zones,name,' . $zone->id,
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $zone->update($request->all());
        AuditLog::log('zone.update', 'Zone', $zone->id, $request->all());
        return back()->with('success', 'Zone updated.');
    }

    public function destroy(Zone $zone)
    {
        if ($zone->staff()->exists() || $zone->clients()->exists()) {
            return back()->with('error', 'Cannot delete zone with assigned staff or clients.');
        }
        $zone->delete();
        return redirect()->route('zones.index')->with('success', 'Zone deleted.');
    }
}