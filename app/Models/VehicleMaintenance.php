<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenance extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'scheduled_date',
        'completed_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
