<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'user_id',
        'api_key_id',
        'method',
        'endpoint',
        'status_code',
        'response_time_ms',
        'ip_address',
        'environment',
        'request_headers',
        'response_headers',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }
}
