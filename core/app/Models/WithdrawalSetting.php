<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalSetting extends Model
{
    protected $fillable = [
        'withdraw_enabled',
        'auto_approve_enabled',
        'minimum_amount',
        'maximum_amount',
        'daily_amount_limit',
        'daily_count_limit'
    ];

    protected $casts = [
        'withdraw_enabled' => 'boolean',
        'auto_approve_enabled' => 'boolean',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'daily_amount_limit' => 'decimal:2',
    ];
}
