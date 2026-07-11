<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessedEvent extends Model
{
    use HasFactory;

    protected $table = 'processed_events';

    protected $fillable = [
        'idempotency_key',
        'event_type',
        'source',
        'source_id',
        'status',
        'payload_hash',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
