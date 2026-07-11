<?php

namespace App\DTOs\Auth;

class CreateApiCredentialDTO
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $status = 'active'
    ) {
    }
}
