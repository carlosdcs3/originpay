<?php

namespace App\Payment\Modern\DTO;

readonly class WithdrawDTO
{
    public function __construct(
        public float $amount,
        public string $currency,
        public string $internalTrxId,
        public string $destinationAccount,
        public ?CustomerDTO $customer = null,
        public ?array $metadata = null,
    ) {}
}
