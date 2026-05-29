<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index()
    {
        try {
            // Get recent audit logs with user information
            $activities = AuditLog::with('user')
                ->orderBy('timestamp', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id ?? null,
                        'timestamp' => $log->timestamp?->toISOString() ?? now()->toISOString(),
                        'user' => $log->user?->name ?? 'System',
                        'action' => $log->action ?? 'Unknown',
                        'module' => $log->module ?? 'Unknown',
                        'description' => $log->description ?? 'N/A',
                        'ip_address' => $log->ip_address ?? 'N/A',
                        'status' => $log->status ?? 'success',
                    ];
                });

            return Inertia::render('Audit/Index', [
                'activities' => $activities,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load audit logs: ' . $e->getMessage());
        }
    }

    public function export()
    {
        try {
            $activities = AuditLog::with('user')
                ->orderBy('timestamp', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'Timestamp' => $log->timestamp?->format('Y-m-d H:i:s'),
                        'User' => $log->user?->name ?? 'System',
                        'Action' => $log->action,
                        'Module' => $log->module,
                        'Description' => $log->description,
                        'IP Address' => $log->ip_address,
                        'Status' => $log->status,
                    ];
                });

            $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($activities) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_keys($activities->first() ?? []));
                foreach ($activities as $activity) {
                    fputcsv($file, $activity);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export audit logs: ' . $e->getMessage());
        }
    }
}
