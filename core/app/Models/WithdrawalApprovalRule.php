<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalApprovalRule extends Model
{
    protected $fillable = [
        'min_amount',
        'max_amount',
        'approval_mode',
        'required_role',
        'is_active'
    ];

    protected $casts = [
        'min_amount' => 'decimal:8',
        'max_amount' => 'decimal:8',
        'is_active' => 'boolean',
    ];
}
