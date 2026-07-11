<?php

namespace App\Gateway\Http\Response;

use App\DTOs\Gateway\GatewayResponse;

class DefaultResponseMapper implements GatewayResponseMapperInterface
{
    public function map(array $transportResult, array $requestData): GatewayResponse
    {
        return new GatewayResponse(
            success: $transportResult['successful'] ?? false,
            transaction_id: null,
            status: null,
            error_message: !empty($transportResult['serverError']) ? 'Server Error' : null,
            payload: json_decode($transportResult['body'] ?? '{}', true) ?? [],
            request_id: null,
            correlation_id: $requestData['correlation_id'] ?? null,
            provider_reference: null,
            status_code: $transportResult['status'] ?? 0,
            headers: $transportResult['headers'] ?? [],
            raw_body: $transportResult['body'] ?? '',
            latency: null, // Será preenchido pelo MetricsMiddleware depois
            retry_count: $requestData['retry_count'] ?? 0
        );
    }
}
