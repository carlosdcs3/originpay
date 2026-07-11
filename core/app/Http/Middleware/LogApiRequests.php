<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Illuminate\Support\Str;

class LogApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // Sanitize Payload (remove passwords, CVV, full CC numbers)
        $payload = $request->all();
        $payload = $this->sanitizePayload($payload);

        $responsePayload = $this->sanitizePayload(json_decode($response->getContent(), true) ?? []);

        // Determine user and api key from request (set by AuthenticateApiKey)
        $userId = $request->input('api_user_id') ?: $request->attributes->get('api_user_id');
        $apiKeyId = $request->input('api_key_id') ?: $request->attributes->get('api_key_id');
        $environment = $request->input('api_environment', $request->attributes->get('api_environment', 'live'));

        try {
            ApiLog::create([
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => $userId,
                'api_key_id' => $apiKeyId,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $duration,
                'ip_address' => $request->ip(),
                'environment' => $environment,
                'request_payload' => $payload,
                'response_payload' => $responsePayload,
            ]);
        } catch (\Exception $e) {
            // Do not break API if log fails
        }

        return $response;
    }

    private function sanitizePayload($payload)
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'cvv', 'cvc', 'card_number', 'pan',
            'api_key', 'api-key', 'x_api_key', 'x-api-key', 'api_secret', 'test_api_key', 'test_api_secret',
            'secret', 'secret_key', 'secret_key_hash', 'token', 'authorization', 'merchant_key',
            'x_merchant_key', 'x-merchant-key', 'test_merchant_key', 'signature', 'x_signature', 'x-signature',
            'client_secret', 'certificate', 'certificate_password', 'pix_copy_paste',
            'qr_code', 'qr_code_url', 'rawresponse', 'raw_response', 'internal_metadata'
        ];
        
        foreach ($payload as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $payload[$key] = '***';
            } elseif (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            }
        }
        return $payload;
    }
}
