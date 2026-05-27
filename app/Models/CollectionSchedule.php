<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionSchedule extends Model
{
    protected $fillable = [
        'zone_id',
        'staff_id',
        'frequency',
        'days_of_week',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function isActive(): bool
    {
        return $this->is_active 
            && (is_null($this->effective_to) || now()->lte($this->effective_to))
            && now()->gte($this->effective_from);
    }

    public function isScheduledForDay(int $dayOfWeek): bool
    {
        return in_array($dayOfWeek, $this->days_of_week ?? []);
    }

    public function scopeThisWeek($query)
    {
        return $query->where('effective_from', '<=', now()->endOfWeek())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->startOfWeek());
            });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
