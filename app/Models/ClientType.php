<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientType extends Model
{
    protected $fillable = [
        'name',
        'category',
        'default_monthly_fee',
        'description',
    ];

    protected $casts = [
        'default_monthly_fee' => 'decimal:2',
    ];

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}
