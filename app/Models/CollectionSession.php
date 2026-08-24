<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionSession extends Model
{
    protected $fillable = [
        'session_reference',
        'staff_id',
        'session_date',
        'expected_amount',
        'actual_amount',
        'banked_amount',
        'status',
        'submitted_at',
        'reconciled_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'expected_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'banked_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Staff, CollectionSession>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return HasMany<Payment, CollectionSession>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
