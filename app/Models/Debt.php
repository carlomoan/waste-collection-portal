<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    protected $fillable = [
        'client_id',
        'invoice_id',
        'original_amount',
        'paid_amount',
        'outstanding',
        'penalty_rate',
        'penalty_amount',
        'penalty_applied',
        'penalty_applied_at',
        'status',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'penalty_rate' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'penalty_applied' => 'boolean',
        'penalty_applied_at' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isSettled(): bool
    {
        return $this->status === 'settled' || $this->outstanding <= 0;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid' || ($this->paid_amount > 0 && $this->outstanding > 0);
    }
}
