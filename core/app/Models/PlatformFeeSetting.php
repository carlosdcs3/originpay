<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformFeeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'small_transaction_limit',
        'small_transaction_fixed_fee',
        'standard_percentage_fee',
        'standard_fixed_fee',
        'is_active',
    ];

    protected $casts = [
        'small_transaction_limit' => 'decimal:8',
        'small_transaction_fixed_fee' => 'decimal:8',
        'standard_percentage_fee' => 'decimal:2',
        'standard_fixed_fee' => 'decimal:8',
        'is_active' => 'boolean',
    ];
}
