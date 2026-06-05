<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'status',
    ];

    protected $casts = [
        'timestamp'  => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Create an audit log entry.
     *
     * @param string       $action      e.g. 'payment.create', 'client.update'
     * @param string       $module      e.g. 'Payment', 'Client'
     * @param int|null     $recordId
     * @param array|null   $newValues   New / changed data
     * @param array|null   $oldValues   Previous data (for updates/deletes)
     * @param string       $status
     */
    public static function log(
        string  $action,
        string  $module,
        ?int    $recordId  = null,
        ?array  $newValues = null,
        ?array  $oldValues = null,
        string  $status    = 'success'
    ): self {
        $shortModule = class_exists($module) ? class_basename($module) : $module;

        return static::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'module'     => $shortModule,
            'record_id'  => $recordId,
            'description'=> "{$action} on {$shortModule}" . ($recordId ? " #{$recordId}" : ''),
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => Request::ip(),
            'status'     => $status,
        ]);
    }
}
