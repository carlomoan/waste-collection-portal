<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Invoice extends Model
{
    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'due_date' => 'date',
        'grace_period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function debt(): HasOne { return $this->hasOne(Debt::class); }

    public function isPastGrace(): bool
    {
        return now()->isAfter($this->grace_period_end) && $this->balance > 0;
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('grace_period_end', '<', now())
                     ->whereIn('status', ['unpaid', 'partial']);
    }

    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->where('billing_month', $month)
                     ->where('billing_year', $year);
    }

    // Formatted invoice number
    protected static function booted(): void
    {
        static::creating(function (Invoice $inv) {
            $count = static::where('billing_year', $inv->billing_year)
                           ->where('billing_month', $inv->billing_month)
                           ->count() + 1;
            $inv->invoice_number = sprintf('INV-%d-%02d-%05d',
                $inv->billing_year, $inv->billing_month, $count);
        });
    }
}
