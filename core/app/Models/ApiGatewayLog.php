<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiGatewayLog extends Model
{
    use HasFactory;

    protected $table = 'api_gateway_logs';

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'merchant_id',
        'charge_id',
        'gateway',
        'operation',
        'duration_ms',
        'status',
        'response_code',
        'error',
        'created_at'
    ];
}
