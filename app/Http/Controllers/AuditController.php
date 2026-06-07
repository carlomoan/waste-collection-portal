<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = QueryBuilder::for(AuditLog::class)
            ->with('user')
            ->allowedFilters([
                AllowedFilter::exact('action'),
                AllowedFilter::exact('module'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('date_range', function ($query, $value) {
                    $dates = explode(',', $value);
                    $query->whereBetween('timestamp', [$dates[0], $dates[1]]);
                }),
                AllowedFilter::partial('description'),
            ])
            ->allowedSorts('timestamp', 'id')
            ->defaultSort('-timestamp')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'module', 'user_id', 'date_range']),
            'stats' => [
                'total_today' => AuditLog::whereDate('timestamp', today())->count(),
                'total_this_week' => AuditLog::whereBetween('timestamp', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'by_action' => AuditLog::selectRaw('action, count(*) as count')->groupBy('action')->get(),
            ],
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');
        // Optionally decode old/new values
        if ($auditLog->old_values) {
            $auditLog->old_values = json_decode($auditLog->old_values, true);
        }
        if ($auditLog->new_values) {
            $auditLog->new_values = json_decode($auditLog->new_values, true);
        }

        return Inertia::render('Audit/Show', ['log' => $auditLog]);
    }

    public function restore(AuditLog $auditLog)
    {
        // Only for 'updated' or 'deleted' actions
        if ($auditLog->action !== 'deleted' || ! $auditLog->old_values) {
            return back()->with('error', 'Cannot restore this log entry.');
        }

        $modelClass = $auditLog->module;
        if (! class_exists($modelClass)) {
            return back()->with('error', 'Model class not found.');
        }

        $oldData = json_decode($auditLog->old_values, true);
        $model = $modelClass::withTrashed()->find($auditLog->record_id);

        if ($model && $model->trashed()) {
            $model->restore();
            $model->update($oldData);
            AuditLog::log('restore', $modelClass, $auditLog->record_id, ['restored_from_log_id' => $auditLog->id]);

            return back()->with('success', 'Record restored successfully.');
        }

        return back()->with('error', 'Record not found or not deleted.');
    }

    public function cleanup(Request $request)
    {
        $days = $request->input('days', 90);
        $deleted = AuditLog::where('timestamp', '<', now()->subDays($days))->delete();

        return back()->with('success', "Deleted {$deleted} audit logs older than {$days} days.");
    }

    public function export(Request $request)
    {
        $logs = AuditLog::with('user')->latest('timestamp')->limit(5000)->get();

        return response()->streamDownload(function () use ($logs) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Timestamp', 'User', 'Action', 'Module', 'Record ID', 'Description', 'IP']);
            foreach ($logs as $l) {
                fputcsv($f, [$l->timestamp, $l->user?->name, $l->action, $l->module, $l->record_id, $l->description, $l->ip_address]);
            }
            fclose($f);
        }, 'audit-logs.csv', ['Content-Type' => 'text/csv']);
    }
}
