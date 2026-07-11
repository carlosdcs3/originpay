<?php

namespace App\Payment\Modern\DTO;

readonly class GatewayTransactionDTO
{
    public function __construct(
        public string $providerTransactionId,
        public string $status, // PAID, FAILED, PENDING, REFUNDED
        public float $amount,
        public string $currency,
        public ?string $externalReference = null,
        public ?array $rawResponse = null,
    ) {}
}
