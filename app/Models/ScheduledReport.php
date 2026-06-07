<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'frequency',
        'recipients',
        'is_active',
        'last_sent_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
    ];
}
