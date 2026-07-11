<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway_id',
        'destination',
        'gross_amount',
        'fees',
        'net_amount',
        'status',
        'scheduled_date',
        'settled_date',
        'split_rule_id',
        'metadata'
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'scheduled_date' => 'datetime',
        'settled_date' => 'datetime',
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
