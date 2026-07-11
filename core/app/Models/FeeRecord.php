<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway_id',
        'operation_type', // charge, withdrawal, settlement
        'reference_id',
        'gross_amount',
        'gateway_cost',
        'merchant_fee',
        'net_amount',
        'margin',
        'status', // expected, confirmed, divergent
        'metadata'
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'gateway_cost' => 'decimal:2',
        'merchant_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'margin' => 'decimal:2',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }
}
