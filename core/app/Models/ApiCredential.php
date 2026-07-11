<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiCredential extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'public_key',
        'secret_key_hash',
        'key_prefix',
        'environment',
        'status',
        'permissions',
        'api_version',
        'last_used_at',
        'expires_at',
        'grace_period_expires_at',
        'revoked_at'
    ];

    protected $casts = [
        'permissions' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'grace_period_expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
