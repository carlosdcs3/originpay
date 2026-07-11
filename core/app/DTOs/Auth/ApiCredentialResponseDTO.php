<?php

namespace App\DTOs\Auth;

use App\Domain\Auth\ApiCredential;

class ApiCredentialResponseDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $publicKey,
        public readonly string $secretKey,
        public readonly string $merchantId,
        public readonly string $status,
        public readonly string $createdAt
    ) {
    }

    public static function fromDomain(ApiCredential $credential): self
    {
        return new self(
            $credential->id,
            $credential->publicKey,
            $credential->secretKey,
            $credential->merchantId,
            $credential->status,
            $credential->createdAt
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'public_key' => $this->publicKey,
            'secret_key' => $this->secretKey,
            'merchant_id' => $this->merchantId,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
