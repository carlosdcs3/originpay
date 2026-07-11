<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'gateway_id',
        'available',
        'pending',
        'blocked',
    ];

    protected $casts = [
        'wallet_id' => 'integer',
        'gateway_id' => 'integer',
        'available' => 'decimal:2',
        'pending' => 'decimal:2',
        'blocked' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }
}
