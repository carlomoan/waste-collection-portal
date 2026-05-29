<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        try {
            // Get settings from database or config
            // For now, return default settings
            $settings = [
                'general' => [
                    'company_name' => setting('company_name', 'Waste Collection Portal'),
                    'email' => setting('email', 'info@wastecollection.co.tz'),
                    'phone' => setting('phone', '+255 123 456 789'),
                    'address' => setting('address', 'Dar es Salaam, Tanzania'),
                ],
                'billing' => [
                    'currency' => setting('currency', 'TZS'),
                    'tax_rate' => setting('tax_rate', 18),
                    'invoice_prefix' => setting('invoice_prefix', 'INV'),
                ],
                'notifications' => [
                    'email_notifications' => setting('email_notifications', true),
                    'sms_notifications' => setting('sms_notifications', false),
                    'payment_reminders' => setting('payment_reminders', true),
                ],
                'security' => [
                    'two_factor_auth' => setting('two_factor_auth', false),
                    'session_timeout' => setting('session_timeout', 30),
                ],
            ];

            return Inertia::render('Settings/Index', [
                'settings' => $settings,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load settings: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'currency' => 'nullable|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:10',
            'email_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'payment_reminders' => 'nullable|boolean',
            'two_factor_auth' => 'nullable|boolean',
            'session_timeout' => 'nullable|integer|min:5|max:120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Store settings in database
            // This would typically use a settings package or custom settings table
            foreach ($request->all() as $key => $value) {
                if ($value !== null) {
                    setting([$key => $value]);
                }
            }

            return back()->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }
}

// Helper function for settings (if not using a package)
if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        // This is a placeholder - implement actual settings storage
        // You could use Laravel's config, a settings package, or a custom settings table
        return config('settings.' . $key, $default);
    }
}
