<?php

namespace App\Gateway\Contracts\Data;

class GatewayResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $gateway_reference = null,
        public readonly ?string $status = null,
        public readonly ?float $amount = null,
        public readonly mixed $raw_response = null,
        public readonly ?string $error_code = null,
        public readonly ?string $error_message = null,
        public array $providerMetadata = [],
        
        // Atributos Específicos Extraídos (Tipagem Forte)
        public readonly ?string $txid = null,
        public readonly ?string $locationId = null,
        public readonly ?string $pixCopyPaste = null,
        public readonly ?string $qrCodeImage = null,
        
        // Observabilidade Injetada pela Infraestrutura
        public ?string $correlationId = null,
        public ?string $requestId = null,
        public ?int $statusCode = null,
        public ?float $latency = null,
        public ?int $retryCount = null,
        public ?string $gatewayVersion = null
    ) {}

    public static function success(
        string $gatewayReference,
        ?string $status,
        ?float $amount,
        mixed $rawResponse,
        array $providerMetadata = [],
        ?string $txid = null,
        ?string $locationId = null,
        ?string $pixCopyPaste = null,
        ?string $qrCodeImage = null
    ): self {
        return new self(
            success: true,
            gateway_reference: $gatewayReference,
            status: $status,
            amount: $amount,
            raw_response: $rawResponse,
            providerMetadata: $providerMetadata,
            txid: $txid,
            locationId: $locationId,
            pixCopyPaste: $pixCopyPaste,
            qrCodeImage: $qrCodeImage
        );
    }

    public static function error(
        string $errorCode,
        string $errorMessage,
        mixed $rawResponse,
        array $providerMetadata = []
    ): self {
        return new self(
            success: false,
            raw_response: $rawResponse,
            error_code: $errorCode,
            error_message: $errorMessage,
            providerMetadata: $providerMetadata
        );
    }
}
