<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Factories\ApiResponse;
use App\Models\IdempotencyKey as IdempotencyModel;
use Closure;
use Illuminate\Http\Request;

class CheckIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        $idempotencyKeyHeader = $request->header('Idempotency-Key');

        if ($request->method() !== 'POST' || ! $idempotencyKeyHeader) {
            return $next($request);
        }

        $merchantId = $request->input('api_merchant_id');
        if (! $merchantId) {
            return $next($request);
        }

        $payload = $request->all();
        $requestHash = hash('sha256', json_encode($payload));

        $record = IdempotencyModel::where('merchant_id', $merchantId)
            ->where('idempotency_key', $idempotencyKeyHeader)
            ->first();

        $requestId = $request->attributes->get('request_id');

        if ($record) {
            if ($record->request_hash !== $requestHash) {
                return ApiResponse::error(
                    ApiErrorType::INVALID_REQUEST,
                    ApiErrorCode::INVALID_PARAMETERS,
                    'The request is invalid.',
                    409
                );
            }

            if ($record->response_status) {
                return response()->json($record->response_body, $record->response_status)->withHeaders([
                    'X-OriginPay-Request-Id' => $requestId,
                    'Idempotent-Replayed' => 'true',
                ]);
            }

            return ApiResponse::error(
                ApiErrorType::RATE_LIMIT_ERROR,
                ApiErrorCode::INVALID_PARAMETERS,
                'Too many requests.',
                429
            );
        }

        $lock = IdempotencyModel::create([
            'merchant_id' => $merchantId,
            'idempotency_key' => $idempotencyKeyHeader,
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'request_hash' => $requestHash,
            'locked_until' => now()->addMinutes(5),
            'expires_at' => now()->addHours(48),
        ]);

        $response = $next($request);

        $lock->update([
            'response_status' => $response->getStatusCode(),
            'response_body' => json_decode($response->getContent(), true),
        ]);

        return $response;
    }
}
