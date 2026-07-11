<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookDlq extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_id',
        'external_reference',
        'payload',
        'headers',
        'error_message',
        'error_class',
        'attempts',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
