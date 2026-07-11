<?php

namespace App\Domain\Auth;

class ApiCredential
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
}
