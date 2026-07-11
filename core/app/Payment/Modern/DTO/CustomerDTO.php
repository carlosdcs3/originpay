<?php

namespace App\Payment\Modern\DTO;

readonly class CustomerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $documentNumber = null,
        public ?string $phone = null,
        public ?array $address = null,
    ) {}
}
