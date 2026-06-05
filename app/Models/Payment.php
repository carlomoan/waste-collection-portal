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

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
