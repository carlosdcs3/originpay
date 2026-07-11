<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayLog extends Model
{
    protected $fillable = [
        'gateway_code',
        'request_payload',
        'response_payload',
        'http_status',
        'execution_time_ms',
        'correlation_id',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    /**
     * Helper to sanitize and log an event to the DB.
     */
    public static function logEvent(string $gatewayCode, array $request, array $response, ?int $httpStatus = null, ?int $execTime = null, ?string $correlationId = null)
    {
        self::create([
            'gateway_code' => $gatewayCode,
            'request_payload' => self::sanitize($request),
            'response_payload' => self::sanitize($response),
            'http_status' => $httpStatus,
            'execution_time_ms' => $execTime,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Sanitizes sensitive fields from payload.
     */
    public static function sanitize(array $payload): array
    {
        $sensitiveKeys = [
            'token', 'client_secret', 'authorization', 'cert', 'password', 'senha', 'cvv', 'card_number'
        ];

        array_walk_recursive($payload, function (&$value, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $value = '*** SANITIZED ***';
                    break;
                }
            }
        });

        return $payload;
    }
}
