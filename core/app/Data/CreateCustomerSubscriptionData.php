<?php

namespace App\Data;

use App\Enums\SubscriptionInterval;
use Carbon\CarbonImmutable;

class CreateCustomerSubscriptionData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly ?string $customerDocument,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $paymentMethod,
        public readonly SubscriptionInterval $interval,
        public readonly int $intervalCount,
        public readonly ?string $description,
        public readonly CarbonImmutable $startAt,
        public readonly array $metadata,
        public readonly ?string $idempotencyKey,
    ) {
    }

    public static function fromArray(int $userId, array $data, ?string $idempotencyKey): self
    {
        return new self(
            userId: $userId,
            customerName: $data['customer']['name'],
            customerEmail: $data['customer']['email'],
            customerDocument: $data['customer']['document'] ?? null,
            amount: number_format((float) $data['amount'], 2, '.', ''),
            currency: strtoupper($data['currency'] ?? 'BRL'),
            paymentMethod: strtolower($data['payment_method']),
            interval: SubscriptionInterval::from($data['interval']),
            intervalCount: (int) ($data['interval_count'] ?? 1),
            description: $data['description'] ?? null,
            startAt: CarbonImmutable::parse($data['start_at'] ?? now()),
            metadata: $data['metadata'] ?? [],
            idempotencyKey: $idempotencyKey,
        );
    }
}
