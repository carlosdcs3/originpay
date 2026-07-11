<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookEndpoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'url',
        'secret',
        'secret_encrypted',
        'secret_preview',
        'old_secret',
        'old_secret_expires_at',
        'environment',
        'status',
        'events',
        'description',
        'last_used_at'
    ];

    protected $casts = [
        'events' => 'array',
        'status' => 'boolean',
        'last_used_at' => 'datetime',
        'old_secret_expires_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class, 'webhook_endpoint_id');
    }
}
