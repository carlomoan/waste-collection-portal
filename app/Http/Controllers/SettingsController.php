<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $defaults = [
            'company_name' => 'Waste Collection Portal', 'company_logo' => null,
            'email' => 'info@example.com', 'phone' => '+255 xxx xxx',
            'address' => 'Dar es Salaam, Tanzania', 'currency' => 'TZS',
            'tax_rate' => 18, 'invoice_prefix' => 'INV', 'email_notifications' => true,
            'sms_notifications' => false, 'payment_reminders' => true,
            'two_factor_auth' => false, 'session_timeout' => 30,
            'maintenance_mode' => false, 'backup_schedule' => 'daily',
        ];

        return Inertia::render('Settings/Index', [
            'settings' => array_merge($defaults, $settings),
            'mail_configs' => [
                'mail_host' => env('MAIL_HOST'), 'mail_port' => env('MAIL_PORT'),
                'mail_username' => env('MAIL_USERNAME'), 'mail_encryption' => env('MAIL_ENCRYPTION'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20', 'address' => 'nullable|string|max:500',
            'currency' => 'nullable|string|max:10', 'tax_rate' => 'nullable|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:10', 'email_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean', 'payment_reminders' => 'nullable|boolean',
            'two_factor_auth' => 'nullable|boolean', 'session_timeout' => 'nullable|integer|min:5|max:120',
            'maintenance_mode' => 'nullable|boolean', 'backup_schedule' => 'nullable|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        try {
            foreach ($request->except(['company_logo', '_token']) as $key => $value) {
                if ($value !== null) Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            if ($request->hasFile('company_logo')) {
                $logoPath = $request->file('company_logo')->store('settings', 'public');
                Setting::updateOrCreate(['key' => 'company_logo'], ['value' => $logoPath]);
            }
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
        Artisan::call('route:clear');
        return back()->with('success', 'Cache cleared.');
    }

    public function runBackup()
    {
        try { Artisan::call('backup:run'); } catch (\Exception $e) {}
        return back()->with('success', 'Backup initiated.');
    }

    /**
     * 🚀 NATIVE DATABASE EXPORT (Structure + Data + Indexes)
     */
    public function exportDatabase()
    {
        $driver = DB::connection()->getDriverName();
        $fileName = 'database_backup_' . now()->format('Y_m_d_His') . '.sql';
        $backupDir = storage_path('app/backups');

        if (!file_exists($backupDir)) mkdir($backupDir, 0755, true);
        $filePath = $backupDir . '/' . $fileName;
        $config = DB::connection()->getConfig();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s --single-transaction %s > %s',
                escapeshellarg($config['username']), escapeshellarg($config['password']),
                escapeshellarg($config['host']), escapeshellarg($config['port'] ?? 3306),
                escapeshellarg($config['database']), escapeshellarg($filePath)
            );
        } elseif ($driver === 'pgsql') {
            $command = sprintf(
                'PGPASSWORD=%s pg_dump --username=%s --host=%s --port=%s %s > %s',
                escapeshellarg($config['password']), escapeshellarg($config['username']),
                escapeshellarg($config['host']), escapeshellarg($config['port'] ?? 5432),
                escapeshellarg($config['database']), escapeshellarg($filePath)
            );
        } elseif ($driver === 'sqlite') {
            $command = sprintf('sqlite3 %s .dump > %s', escapeshellarg($config['database']), escapeshellarg($filePath));
        } else {
            return back()->with('error', 'Unsupported database driver for native backup.');
        }

        $result = Process::run($command);

        if ($result->successful()) {
            AuditLog::log('database.export', 'Database', null, ['file' => $fileName]);
            return response()->download($filePath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Database export failed: ' . $result->errorOutput());
    }

    /**
     * 🚀 NATIVE DATABASE RESTORE
     */
    public function restoreDatabase(Request $request)
    {
        $request->validate(['sql_file' => 'required|file|max:51200']); // 50MB max
        $file = $request->file('sql_file');
        $filePath = $file->getRealPath();

        $driver = DB::connection()->getDriverName();
        $config = DB::connection()->getConfig();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s --port=%s %s < %s',
                escapeshellarg($config['username']), escapeshellarg($config['password']),
                escapeshellarg($config['host']), escapeshellarg($config['port'] ?? 3306),
                escapeshellarg($config['database']), escapeshellarg($filePath)
            );
        } elseif ($driver === 'pgsql') {
            $command = sprintf(
                'PGPASSWORD=%s psql --username=%s --host=%s --port=%s %s < %s',
                escapeshellarg($config['password']), escapeshellarg($config['username']),
                escapeshellarg($config['host']), escapeshellarg($config['port'] ?? 5432),
                escapeshellarg($config['database']), escapeshellarg($filePath)
            );
        } elseif ($driver === 'sqlite') {
            $command = sprintf('sqlite3 %s < %s', escapeshellarg($config['database']), escapeshellarg($filePath));
        } else {
            return back()->with('error', 'Unsupported database driver.');
        }

        $result = Process::run($command);

        if ($result->successful()) {
            AuditLog::log('database.restore', 'Database', null, ['file' => $file->getClientOriginalName()]);
            return back()->with('success', 'Database restored successfully.');
        }

        return back()->with('error', 'Database restore failed: ' . $result->errorOutput());
    }
}
