<?php

namespace App\Contracts\PaymentMethod;

use App\Domain\PaymentMethod\PaymentMethod;

interface PaymentMethodRepositoryInterface
{
    public function save(PaymentMethod $paymentMethod): void;

    public function findById(string $id): ?PaymentMethod;
}
