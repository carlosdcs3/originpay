<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'idempotency_key',
        'request_method',
        'request_path',
        'request_hash',
        'response_status',
        'response_body',
        'locked_until',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'response_status' => 'integer',
        'locked_until' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
