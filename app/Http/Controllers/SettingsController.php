<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $defaults = [
            'company_name' => 'Waste Collection Portal',
            'company_logo' => null,
            'email' => 'info@example.com',
            'phone' => '+255 xxx xxx',
            'address' => 'Dar es Salaam, Tanzania',
            'currency' => 'TZS',
            'tax_rate' => 18,
            'invoice_prefix' => 'INV',
            'email_notifications' => true,
            'sms_notifications' => false,
            'payment_reminders' => true,
            'two_factor_auth' => false,
            'session_timeout' => 30,
            'maintenance_mode' => false,
            'backup_schedule' => 'daily',
        ];
        $settings = array_merge($defaults, $settings);

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'mail_configs' => [
                'mail_host' => env('MAIL_HOST'),
                'mail_port' => env('MAIL_PORT'),
                'mail_username' => env('MAIL_USERNAME'),
                'mail_encryption' => env('MAIL_ENCRYPTION'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'company_logo' => 'nullable|image|max:2048',
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
            'maintenance_mode' => 'nullable|boolean',
            'backup_schedule' => 'nullable|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($request->except(['company_logo', '_token']) as $key => $value) {
                if ($value !== null) {
                    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            }

            if ($request->hasFile('company_logo')) {
                $logoPath = $request->file('company_logo')->store('settings', 'public');
                Setting::updateOrCreate(['key' => 'company_logo'], ['value' => $logoPath]);
            }

            // Clear cache
            Cache::forget('app_settings');

            AuditLog::log('settings.update', 'Settings', null, $request->except(['_token']));
            DB::commit();
            return back()->with('success', 'Settings updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function testEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);
        try {
            \Mail::raw('This is a test email from Waste Collection Portal.', function ($message) use ($request) {
                $message->to($request->test_email)->subject('Test Email');
            });
            return back()->with('success', 'Test email sent.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: '.$e->getMessage());
        }
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        return back()->with('success', 'Cache cleared.');
    }

    public function runBackup()
    {
        Artisan::call('backup:run');
        return back()->with('success', 'Backup initiated.');
    }
}