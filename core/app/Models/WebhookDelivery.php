<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'webhook_endpoint_id',
        'webhook_event_id',
        'event_type',
        'idempotency_key',
        'payload',
        'attempt',
        'successful',
        'status_code',
        'next_retry_at',
        'latency_ms',
        'status',
        'attempt_count',
        'next_attempt_at',
        'last_attempt_at',
        'response_status',
        'response_body',
        'error_message'
    ];

    protected $casts = [
        'payload' => 'array',
        'successful' => 'boolean',
        'attempt' => 'integer',
        'status_code' => 'integer',
        'latency_ms' => 'float',
        'next_retry_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'response_status' => 'integer'
    ];

    public function endpoint()
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function event()
    {
        return $this->belongsTo(WebhookEvent::class, 'webhook_event_id');
    }
}
