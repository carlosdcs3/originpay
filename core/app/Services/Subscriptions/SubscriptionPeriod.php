<?php

namespace App\Services\Subscriptions;

use Carbon\CarbonImmutable;

class SubscriptionPeriod
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly CarbonImmutable $nextBillingAt,
    ) {
    }
}
