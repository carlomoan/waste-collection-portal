<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDeposit extends Model
{
    protected $fillable = [
        'deposit_reference',
        'staff_id',
        'deposit_date',
        'amount',
        'bank_name',
        'account_number',
        'slip_number',
        'slip_file',
        'status',
    ];

    protected $casts = [
        'deposit_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    // Auto-generate deposit reference
    protected static function booted(): void
    {
        static::creating(function (BankDeposit $deposit) {
            if (empty($deposit->deposit_reference)) {
                $prefix = 'DEP';
                $date = now()->format('Ymd');
                $count = static::whereDate('created_at', now()->toDateString())->count() + 1;
                $deposit->deposit_reference = sprintf('%s-%s-%04d', $prefix, $date, $count);
            }
        });
    }
}
