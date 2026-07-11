<?php

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'provider',
        'gateway',
        'merchant_id',
        'event_id',
        'external_reference',
        'provider_reference',
        'event_type',
        'headers',
        'status',
        'attempts',
        'processed_at',
        'failed_at',
        'last_error',
        'error_message',
        'payload_hash',
        'payload_version',
        'raw_payload',
        'correlation_id',
        'metadata',
        'resolution_admin_id',
        'resolution_reason',
        'api_version',
        'environment',
        'payload',
        'created_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'raw_payload' => 'array',
        'metadata' => 'array',
        'status' => WebhookEventStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
