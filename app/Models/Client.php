<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;

    protected $fillable = [
        'client_number', 'name', 'phone', 'email', 'zone_id',
        'client_type_id', 'monthly_fee', 'address', 'status',
        'contract_start_date', 'credit_balance',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'contract_start_date' => 'date',
    ];

    // Relationships
    public function zone(): BelongsTo { return $this->belongsTo(Zone::class); }
    public function clientType(): BelongsTo { return $this->belongsTo(ClientType::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function debts(): HasMany { return $this->hasMany(Debt::class); }
    public function collectionSessions(): HasMany { return $this->hasManyThrough(CollectionSession::class, Payment::class); }

    // Accessors
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->invoices()->whereIn('status', ['unpaid','partial','overdue'])
                    ->sum('balance');
    }

    // Auto-generate client number
    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            $year   = now()->year;
            $prefix = "WCP-{$year}-";

            // Use max of existing numbers (including soft-deleted) to avoid gaps/collisions
            $maxNum = static::withTrashed()
                ->where('client_number', 'like', $prefix . '%')
                ->pluck('client_number')
                ->map(fn($n) => (int) substr($n, strlen($prefix)))
                ->max() ?? 0;

            $client->client_number = sprintf('%s%05d', $prefix, $maxNum + 1);
        });
    }
}
