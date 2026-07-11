<?php

namespace App\Domain\Payments;

use App\Enums\ChargeStatus;

class Charge
{
    public function __construct(
        public readonly string $id,
        public readonly string $merchantId,
        public readonly ?string $sessionId,
        public readonly ?string $paymentMethodId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ChargeStatus $status,
        public readonly array $merchantMetadata,
        public readonly array $internalMetadata,
        public readonly string $environment,
        public readonly ?string $chargeNumber = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureMessage = null,
        public readonly ?string $createdAt = null
    ) {
    }

    public function isPending(): bool
    {
        return $this->status === ChargeStatus::PENDING;
    }

    public function isSucceeded(): bool
    {
        return $this->status === ChargeStatus::SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status === ChargeStatus::FAILED;
    }
}
