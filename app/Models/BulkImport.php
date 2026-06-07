<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImport extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'entity_type',
        'records_imported',
        'total_rows',
        'success_count',
        'failed_count',
        'imported_ids',
        'error_log',
        'error_message',
        'status',
        'imported_at',
        'imported_by',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'records_imported' => 'integer',
        'total_rows' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'imported_ids' => 'array',
        'error_log' => 'array',
    ];

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
