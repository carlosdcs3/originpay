<?php

namespace App\Factories\PaymentMethod;

use App\Domain\PaymentMethod\PaymentMethod;
use App\DTOs\PaymentMethod\CreatePaymentMethodRequestDTO;
use Illuminate\Support\Str;

class PaymentMethodFactory
{
    public function createFromRequest(CreatePaymentMethodRequestDTO $dto): PaymentMethod
    {
        $id = 'pm_' . str_replace('-', '', Str::uuid()->toString());
        
        $fingerprint = $this->generateFakeFingerprint($dto);
        
        $last4 = null;
        if ($dto->getPan()) {
            $last4 = substr($dto->getPan(), -4);
        }

        $brand = $this->guessBrandFake($dto->getPan());
        
        $expiresAt = null;
        if ($dto->getExpMonth() && $dto->getExpYear()) {
            $year = strlen($dto->getExpYear()) === 2 ? '20' . $dto->getExpYear() : $dto->getExpYear();
            $expiresAt = new \DateTimeImmutable(sprintf('%s-%s-01 23:59:59', $year, $dto->getExpMonth()));
            $expiresAt = $expiresAt->modify('last day of this month');
            
            if ($expiresAt < new \DateTimeImmutable()) {
                throw new \InvalidArgumentException('The provided card is expired.');
            }
        }

        return new PaymentMethod(
            $id,
            $dto->getType(),
            'ACTIVE', // Initial status
            $fingerprint,
            $last4,
            $brand,
            $expiresAt,
            new \DateTimeImmutable(),
            $dto->getMetadata() ? $dto->getMetadata()->toArray() : []
        );
    }

    private function generateFakeFingerprint(CreatePaymentMethodRequestDTO $dto): string
    {
        // In Sprint 4 this is purely fake/deterministic based on PAN if available.
        $pan = $dto->getPan() ?? Str::random(16);
        return 'fp_' . hash('sha256', $pan);
    }

    private function guessBrandFake(?string $pan): ?string
    {
        if (!$pan) return null;
        if (str_starts_with($pan, '4')) return 'visa';
        if (str_starts_with($pan, '5')) return 'mastercard';
        if (str_starts_with($pan, '3')) return 'amex';
        return 'unknown';
    }
}
