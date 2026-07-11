<?php

namespace App\Contracts\Payments;

use App\Domain\Payments\Charge;

interface ChargeRepositoryInterface
{
    public function save(Charge $charge): void;
    
    public function findByIdAndMerchant(string $chargeId, string $merchantId): ?Charge;
}
