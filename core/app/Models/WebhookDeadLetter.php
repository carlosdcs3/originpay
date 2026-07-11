<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDeadLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_event_id',
        'gateway_code',
        'payload',
        'headers',
        'signature',
        'provider_timestamp',
        'received_at',
        'error_message',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'received_at' => 'datetime',
    ];

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_code', 'code');
    }
}
