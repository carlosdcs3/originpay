<?php

namespace App\Payment\Modern\DTO;

readonly class GatewayResponseDTO
{
    public function __construct(
        public bool $isSuccess,
        public ?string $providerTransactionId = null,
        public ?string $redirectUrl = null,
        public ?string $qrCode = null,
        public ?string $errorMessage = null,
        public ?array $rawResponse = null,
    ) {}
}
