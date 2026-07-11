<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Idempotency\IdempotencyService;
use App\Factories\ApiResponse;
use App\Enums\ApiErrorType;
use App\Enums\ApiErrorCode;

class IdempotencyMiddleware
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return $next($request);
        }

        $merchantContext = $request->attributes->get('merchant_context');
        
        if (!$merchantContext) {
            return $next($request);
        }

        $record = $this->idempotencyService->checkAndStore(
            merchantId: $merchantContext->merchantId,
            idempotencyKey: $idempotencyKey,
            method: $request->method(),
            path: $request->path(),
            body: $request->getContent()
        );

        if (!$record->wasRecentlyCreated) {
            $requestHash = hash('sha256', $request->getContent());

            if (
                $record->request_method !== $request->method()
                || $record->request_path !== $request->path()
                || $record->request_hash !== $requestHash
            ) {
                return ApiResponse::error(
                    ApiErrorType::INVALID_REQUEST,
                    ApiErrorCode::INVALID_PARAMETERS,
                    'Idempotency key already used with a different request.',
                    409
                );
            }

            if ($record->locked_until && $record->locked_until->isFuture()) {
                return ApiResponse::error(
                    ApiErrorType::INVALID_REQUEST,
                    ApiErrorCode::INVALID_PARAMETERS,
                    'Concurrent request with the same idempotency key.',
                    409
                );
            }

            if ($record->response_status) {
                return response()->json($record->response_body, $record->response_status)
                    ->header('Idempotent-Replayed', 'true');
            }
        }

        $response = $next($request);

        $this->idempotencyService->updateResponse(
            $record,
            $response->status(),
            json_decode($response->getContent(), true)
        );

        return $response;
    }
}
