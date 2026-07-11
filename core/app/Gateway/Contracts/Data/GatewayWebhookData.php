<?php

namespace App\Gateway\Contracts\Data;

class GatewayWebhookData
{
    public function __construct(
        public readonly bool $is_valid,
        public readonly ?string $gateway_reference = null,
        public readonly ?string $status = null,
        public readonly ?float $amount = null,
        public readonly ?string $operation = null,
        public readonly array $raw_payload = [],
        public readonly ?string $error_message = null
    ) {}

    public static function valid(
        string $gatewayReference,
        string $status,
        ?float $amount,
        ?string $operation,
        array $rawPayload
    ): self {
        return new self(
            is_valid: true,
            gateway_reference: $gatewayReference,
            status: $status,
            amount: $amount,
            operation: $operation,
            raw_payload: $rawPayload
        );
    }

    public static function invalid(string $errorMessage, array $rawPayload = []): self
    {
        return new self(
            is_valid: false,
            raw_payload: $rawPayload,
            error_message: $errorMessage
        );
    }
}
