<?php

namespace App\Vault;

use App\Contracts\PaymentMethod\PaymentMethodVaultInterface;
use App\DTOs\PaymentMethod\CreatePaymentMethodRequestDTO;
use Illuminate\Support\Str;

class MockPaymentMethodVault implements PaymentMethodVaultInterface
{
    private static array $storage = [];

    public function storeMock(CreatePaymentMethodRequestDTO $requestDTO): string
    {
        $token = 'vault_tok_' . Str::uuid()->toString();

        // Note: We strip CVV explicitly as per PCI DSS simulated rules.
        // In a real vault, CVV may be kept temporarily and then purged, or passed straight to the gateway.
        // We do not store CVV in this mock vault to guarantee it never appears in responses or logs.
        self::$storage[$token] = [
            'type' => $requestDTO->getType(),
            'pan' => $requestDTO->getPan(),
            'exp_month' => $requestDTO->getExpMonth(),
            'exp_year' => $requestDTO->getExpYear(),
            'holder_name' => $requestDTO->getHolderName(),
        ];

        return $token;
    }

    public function retrieveMock(string $token): ?array
    {
        return self::$storage[$token] ?? null;
    }

    /**
     * For testing purposes only: clears the in-memory static storage.
     */
    public static function flushMockStorage(): void
    {
        self::$storage = [];
    }
}
