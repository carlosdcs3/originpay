<?php

namespace App\Services\Subscriptions;

use App\Enums\SubscriptionInterval;
use Carbon\CarbonImmutable;

class SubscriptionPeriodCalculator
{
    public function firstPeriod(
        CarbonImmutable $startAt,
        SubscriptionInterval $interval,
        int $intervalCount
    ): SubscriptionPeriod {
        return $this->periodStartingAt($startAt, $interval, $intervalCount);
    }

    public function periodStartingAt(
        CarbonImmutable $startAt,
        SubscriptionInterval $interval,
        int $intervalCount
    ): SubscriptionPeriod {
        $count = max(1, $intervalCount);

        $end = match ($interval) {
            SubscriptionInterval::DAY => $startAt->addDays($count),
            SubscriptionInterval::WEEK => $startAt->addWeeks($count),
            SubscriptionInterval::MONTH => $startAt->addMonthsNoOverflow($count),
            SubscriptionInterval::YEAR => $startAt->addYearsNoOverflow($count),
        };

        return new SubscriptionPeriod($startAt, $end, $end);
    }
}
