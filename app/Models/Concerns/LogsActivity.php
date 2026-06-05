<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Automatically records create / update / delete events to audit_logs.
 * Apply this trait to any Eloquent model that should be audited.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            self::writeAuditLog('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = array_intersect_key($model->getOriginal(), $model->getDirty());
            self::writeAuditLog('updated', $model, $old, $model->getDirty());
        });

        static::deleted(function ($model) {
            self::writeAuditLog('deleted', $model, $model->getAttributes(), null);
        });
    }

    private static function writeAuditLog(string $action, $model, ?array $old, ?array $new): void
    {
        try {
            AuditLog::log(
                action:    strtolower(class_basename($model)) . '.' . $action,
                module:    class_basename($model),
                recordId:  $model->getKey(),
                newValues: $new,
                oldValues: $old,
            );
        } catch (\Throwable $e) {
            // Never break the main request due to audit failures
            \Illuminate\Support\Facades\Log::warning('Audit log write failed: ' . $e->getMessage());
        }
    }
}
