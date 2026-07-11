<?php

namespace App\Repositories\PaymentMethod;

use App\Contracts\PaymentMethod\PaymentMethodRepositoryInterface;
use App\Domain\PaymentMethod\PaymentMethod;

class MockPaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    private static array $storage = [];

    public function save(PaymentMethod $paymentMethod): void
    {
        // Save the pure entity to memory
        // No DB persistence. No PAN exposed, since Entity only has last4.
        self::$storage[$paymentMethod->getId()] = $paymentMethod;
    }

    public function findById(string $id): ?PaymentMethod
    {
        return self::$storage[$id] ?? null;
    }

    /**
     * For testing purposes only
     */
    public static function flushMockStorage(): void
    {
        self::$storage = [];
    }
}
