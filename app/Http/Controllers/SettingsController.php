<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Index');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'nullable|string',
            'site_email' => 'nullable|email',
            'site_phone' => 'nullable|string',
        ]);

        // Store settings in database or config
        // For now, just return success
        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
