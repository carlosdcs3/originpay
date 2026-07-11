<?php

namespace App\Payment\Modern\DTO;

readonly class RefundDTO
{
    public function __construct(
        public string $providerTransactionId,
        public float $amount,
        public string $currency,
        public ?string $reason = null,
        public ?array $metadata = null,
    ) {}
}
