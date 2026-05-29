<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'plate_number',
        'type',
        'driver_id',
        'status',
        'fuel_level',
        'last_service',
        'purchase_date',
        'insurance_expiry',
        'is_hired',
        'hire_start_date',
        'hire_end_date',
        'hire_cost',
    ];

    protected $casts = [
        'fuel_level' => 'integer',
        'last_service' => 'date',
        'purchase_date' => 'date',
        'insurance_expiry' => 'date',
        'is_hired' => 'boolean',
        'hire_start_date' => 'date',
        'hire_end_date' => 'date',
        'hire_cost' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'driver_id');
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    public function isHired(): bool
    {
        return $this->is_hired === true;
    }

    public function requiresMaintenance(): bool
    {
        // Hired vehicles may not require maintenance as it's covered by hire agreement
        if ($this->is_hired) {
            return false;
        }
        
        // Owned vehicles require maintenance
        return true;
    }

    public function scopeOwned($query)
    {
        return $query->where('is_hired', false);
    }

    public function scopeHired($query)
    {
        return $query->where('is_hired', true);
    }
}
