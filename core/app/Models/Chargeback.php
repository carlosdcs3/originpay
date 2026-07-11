<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chargeback extends Model
{
    use HasFactory;

    protected $fillable = [
        'charge_id',
        'user_id',
        'gateway_id',
        'provider_reference',
        'amount',
        'reason',
        'status',
        'deadline',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deadline' => 'datetime',
        'metadata' => 'array',
    ];

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }
}
