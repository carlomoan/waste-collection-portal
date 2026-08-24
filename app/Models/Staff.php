<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use LogsActivity, SoftDeletes;

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

    /**
     * @return BelongsTo<User, Staff>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Zone, Staff>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * @return HasMany<CollectionSession, Staff>
     */
    public function collectionSessions(): HasMany
    {
        return $this->hasMany(CollectionSession::class);
    }

    /**
     * @return HasMany<Payment, Staff>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Expense, Staff>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<SalaryPayment, Staff>
     */
    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    /**
     * @return HasMany<BankDeposit, Staff>
     */
    public function bankDeposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class);
    }

    /**
     * @return HasMany<CollectionSchedule, Staff>
     */
    public function collectionSchedules(): HasMany
    {
        return $this->hasMany(CollectionSchedule::class);
    }

    /**
     * @return HasMany<AttendanceRecord, Staff>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * @param  Builder<Staff>  $query
     * @return Builder<Staff>
     */
    public function scopeCollectors($query)
    {
        return $query->where('role', 'collector');
    }

    /**
     * @param  Builder<Staff>  $query
     * @return Builder<Staff>
     */
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
            $full = "{$prefix}-{$year}-";

            $maxNum = static::withTrashed()
                ->where('staff_number', 'like', $full.'%')
                ->pluck('staff_number')
                ->map(fn ($n) => (int) substr($n, strlen($full)))
                ->max() ?? 0;

            $staff->staff_number = sprintf('%s%03d', $full, $maxNum + 1);
        });
    }
}
