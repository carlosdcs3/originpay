<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'charge_id',
        'gateway_event_id',
        'event',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }
}
