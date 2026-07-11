<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'merchant_id',
        'api_key_id',
        'api_version',
        'endpoint',
        'method',
        'status_code',
        'ip_address',
        'user_agent',
        'country',
        'duration_ms',
        'request_size',
        'response_size',
        'error_type',
        'error_code',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'duration_ms' => 'float',
        'status_code' => 'integer',
        'request_size' => 'integer',
        'response_size' => 'integer',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function credential()
    {
        return $this->belongsTo(ApiCredential::class, 'api_key_id');
    }
}
