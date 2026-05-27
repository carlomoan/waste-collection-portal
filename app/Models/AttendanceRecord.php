<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'staff_id',
        'work_date',
        'clock_in',
        'clock_out',
        'status',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function isPresent(): bool
    {
        return $this->status === 'present';
    }

    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    public function isOnLeave(): bool
    {
        return $this->status === 'leave';
    }

    public function isHalfDay(): bool
    {
        return $this->status === 'half_day';
    }

    public function getWorkHoursAttribute(): float
    {
        if ($this->clock_in && $this->clock_out) {
            return $this->clock_in->diffInHours($this->clock_out);
        }
        return 0;
    }
}
