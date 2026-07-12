<?php

namespace App\Support\Observability;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;

class StructuredLogContext
{
    /**
     * @return array<string, mixed>
     */
    public function fromRequest(Request $request, ?int $statusCode = null, ?float $durationMs = null): array
    {
        return $this->withoutEmpty([
            'correlation_id' => Context::get('correlation_id'),
            'timestamp' => Carbon::now()->toIso8601String(),
            'tenant_id' => Context::get('tenant_id'),
            'merchant_id' => Context::get('merchant_id'),
            'user_id' => Context::get('user_id'),
            'api_key_id' => Context::get('api_key_id'),
            'gateway' => Context::get('gateway'),
            'payment_id' => Context::get('payment_id'),
            'request_method' => $request->method(),
            'request_path' => '/'.ltrim($request->path(), '/'),
            'ip' => $request->ip(),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function withoutEmpty(array $context): array
    {
        return array_filter($context, static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
