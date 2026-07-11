<?php

namespace App\Payment\Modern\DTO;

readonly class DepositDTO
{
    public function __construct(
        public float $amount,
        public string $currency,
        public string $internalTrxId,
        public ?CustomerDTO $customer = null,
        public ?array $metadata = null,
    ) {}
}
