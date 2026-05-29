<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImport extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'records_imported',
        'status',
        'imported_at',
        'imported_by',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'records_imported' => 'integer',
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
