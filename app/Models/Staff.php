<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'staff_number',
        'national_id',
        'phone',
        'zone_id',
        'role',
        'base_salary',
        'hire_date',
        'is_active',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function collectionSessions(): HasMany
    {
        return $this->hasMany(CollectionSession::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function bankDeposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class);
    }

    public function collectionSchedules(): HasMany
    {
        return $this->hasMany(CollectionSchedule::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function scopeCollectors($query)
    {
        return $query->where('role', 'collector');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Auto-generate staff number
    protected static function booted(): void
    {
        static::creating(function (Staff $staff) {
            $prefix = config('wcp.staff_prefix', 'WCP-STF');
            $year = now()->year;
            $count = static::whereYear('created_at', $year)->count() + 1;
            $staff->staff_number = sprintf('%s-%d-%03d', $prefix, $year, $count);
        });
    }
}
