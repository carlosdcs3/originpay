<?php

namespace App\Gateway\Exceptions\Mappers;

use App\DTOs\Gateway\GatewayResponse;
use App\Exceptions\Gateway\GatewayAuthenticationException;
use App\Exceptions\Gateway\GatewayCommunicationException;
use App\Exceptions\Gateway\GatewayRateLimitException;
use App\Exceptions\Gateway\GatewayTimeoutException;
use App\Exceptions\Gateway\GatewayValidationException;

class EfiExceptionMapper implements GatewayExceptionMapperInterface
{
    public function throwMappedException(GatewayResponse $response): void
    {
        if ($response->success) {
            return;
        }

        $statusCode = $response->status_code;
        
        // Efi as vezes retorna nome e mensagem no payload
        $errorType = $response->payload['nome'] ?? $response->payload['error'] ?? 'Unknown Error';
        $message = $response->payload['mensagem'] ?? $response->payload['error_description'] ?? 'Erro desconhecido na comunicação com Efi.';
        
        $fullMessage = "[Efi] {$errorType}: {$message}";

        if ($statusCode === 400 || $statusCode === 422) {
            throw new GatewayValidationException($fullMessage, $statusCode);
        }

        if ($statusCode === 401 || $statusCode === 403) {
            throw new GatewayAuthenticationException($fullMessage, $statusCode);
        }

        if ($statusCode === 408 || $statusCode === 504) {
            throw new GatewayTimeoutException($fullMessage, $statusCode);
        }

        if ($statusCode === 429) {
            throw new GatewayRateLimitException($fullMessage, $statusCode);
        }

        throw new GatewayCommunicationException($fullMessage, $statusCode);
    }
}
