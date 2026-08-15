<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'bank_name',
        'account_number',
        'account_holder',
        'opening_balance',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function deposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class);
    }

    public function getCurrentBalanceAttribute()
    {
        $deposits = BankDeposit::where('bank_account_id', $this->id)->where('status', 'confirmed')->sum('amount');
        return $this->opening_balance + $deposits;
    }
}
