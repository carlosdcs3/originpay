<?php

namespace App\Contracts\PaymentMethod;

use App\DTOs\PaymentMethod\CreatePaymentMethodRequestDTO;

interface PaymentMethodVaultInterface
{
    /**
     * Stores the sensitive data securely and returns a reference token.
     * Note: In Sprint 4, this is named `storeMock` to clarify it is not production-ready.
     */
    public function storeMock(CreatePaymentMethodRequestDTO $requestDTO): string;

    /**
     * Retrieves the sensitive data using the reference token.
     * Note: In Sprint 4, this is named `retrieveMock` to clarify it is not production-ready.
     */
    public function retrieveMock(string $token): ?array;
}
