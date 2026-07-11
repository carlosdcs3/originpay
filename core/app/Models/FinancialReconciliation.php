<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'expected_balance',
        'actual_balance',
        'difference',
        'status',
        'metadata',
    ];

    protected $casts = [
        'expected_balance' => 'decimal:8',
        'actual_balance' => 'decimal:8',
        'difference' => 'decimal:8',
        'metadata' => 'array',
    ];
}
