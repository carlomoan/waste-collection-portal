<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\InvoiceService;
use App\Services\ClientService;

class Payment extends Model
{
    use SoftDeletes, LogsActivity;

    /** TZS 200 payments are Ushuru wa Mnada Soko la Kikundi (market levy), NOT household waste fees. */
    public const MARKET_LEVY_AMOUNT = 200.00;

    public const REVENUE_TYPES = [
        'household_waste' => 'Household Waste Fee',
        'market_levy' => 'Ushuru wa Mnada Soko la Kikundi',
        'other' => 'Other Revenue',
    ];

    protected $fillable = [
        'control_number',
        'receipt_number',
        'pos_number',
        'bill_reference',
        'invoice_id',
        'client_id',
        'collection_session_id',
        'staff_id',
        'amount',
        'revenue_type',
        'payer_name',
        'payment_method',
        'status',
        'paid_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function collectionSession(): BelongsTo { return $this->belongsTo(CollectionSession::class); }
    public function staff(): BelongsTo { return $this->belongsTo(Staff::class); }

    // After payment saved, update invoice balance
    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            // Auto-classify: TZS 200 = Ushuru wa Mnada, not household waste fee
            if (empty($payment->revenue_type)) {
                $payment->revenue_type = self::classifyRevenueType((float) $payment->amount);
            }
        });

        static::created(function (Payment $payment) {
            if ($payment->invoice) {
                app(InvoiceService::class)->recalculate($payment->invoice);
            }
            if ($payment->client) {
                app(ClientService::class)->applyCreditBalance($payment->client);
            }
        });

        static::updated(function (Payment $payment) {
            if ($payment->invoice && $payment->isDirty('amount')) {
                app(InvoiceService::class)->recalculate($payment->invoice);
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Household waste collection monthly fees only (excludes market levies).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeHouseholdWaste($query)
    {
        return $query->where('revenue_type', 'household_waste');
    }

    /**
     * Ushuru wa Mnada Soko la Kikundi (market levy) payments only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeMarketLevy($query)
    {
        return $query->where('revenue_type', 'market_levy');
    }

    /**
     * Classify a payment amount into its revenue type.
     * TZS 200 = market levy; anything else = household waste fee.
     */
    public static function classifyRevenueType(float $amount): string
    {
        return abs($amount - self::MARKET_LEVY_AMOUNT) < 0.001
            ? 'market_levy'
            : 'household_waste';
    }
}
