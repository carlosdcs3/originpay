<?php

namespace App\Payment\Modern\DTO;

readonly class WebhookDTO
{
    public function __construct(
        public string $providerTransactionId,
        public ?string $externalReference,
        public string $status, // PAID, FAILED, REFUNDED, CHARGEBACKED, etc
        public float $amount,
        public string $currency,
        public ?array $metadata = null,
        public ?array $rawPayload = null,
    ) {}
}
