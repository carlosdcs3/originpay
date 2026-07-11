<?php

namespace App\Domain\Payments;

class GatewayAuthorizeRequest
{
    public function __construct(
        public readonly string $chargeId,
        public readonly string $merchantId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $paymentMethodId,
        public readonly array $merchantMetadata,
        public readonly string $environment,
        public readonly ?GatewayRuntimeConfig $runtimeConfig = null
    ) {}

    public function withConfig(GatewayRuntimeConfig $config): self
    {
        return new self(
            $this->chargeId,
            $this->merchantId,
            $this->amount,
            $this->currency,
            $this->paymentMethodId,
            $this->merchantMetadata,
            $this->environment,
            $config
        );
    }
}
