<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewayFeeConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'transaction_fee_type',
        'transaction_fixed_fee',
        'transaction_percent_fee',
        'withdraw_fee_type',
        'withdraw_fixed_fee',
        'withdraw_percent_fee',
        'refund_fee_type',
        'refund_fixed_fee',
        'refund_percent_fee',
        'provider_fee_mode',
        'provider_fixed_fee',
        'provider_percent_fee',
        'currency',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'transaction_fixed_fee' => 'decimal:8',
        'transaction_percent_fee' => 'decimal:2',
        'withdraw_fixed_fee' => 'decimal:8',
        'withdraw_percent_fee' => 'decimal:2',
        'refund_fixed_fee' => 'decimal:8',
        'refund_percent_fee' => 'decimal:2',
        'provider_fixed_fee' => 'decimal:8',
        'provider_percent_fee' => 'decimal:2',
    ];
}
