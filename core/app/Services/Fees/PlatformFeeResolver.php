<?php

namespace App\Services\Fees;

use App\Models\PlatformFeeRule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PlatformFeeResolver
{
    public function __construct(
        private readonly PlatformFeeCalculator $calculator
    ) {
    }

    public function resolve(
        User|int|null $user,
        string $paymentMethod,
        float $amount,
        string $currency = 'BRL',
        Carbon|string|null $at = null
    ): PlatformFeeResult {
        $userId = $user instanceof User ? $user->id : $user;
        $method = strtolower($paymentMethod);
        $currency = strtoupper($currency);
        $baseQuery = $this->baseQuery($method, $currency, $at);

        if ($userId && $baseQuery) {
            $merchantRule = (clone $baseQuery)
                ->merchant($userId)
                ->latestEffective()
                ->first();

            if ($merchantRule) {
                return $this->calculator->calculate($amount, $merchantRule, PlatformFeeRule::SCOPE_MERCHANT, $at);
            }
        }

        $globalRule = $baseQuery
            ? (clone $baseQuery)->global()->latestEffective()->first()
            : null;

        if ($globalRule) {
            return $this->calculator->calculate($amount, $globalRule, PlatformFeeRule::SCOPE_GLOBAL, $at);
        }

        return $this->calculator->calculate($amount, $this->fallbackRule($method, $currency), 'fallback', $at);
    }

    private function baseQuery(string $paymentMethod, string $currency, Carbon|string|null $at)
    {
        if (! Schema::hasTable('platform_fee_rules')) {
            Log::warning('Platform fee rules table is missing; using fallback fee rule.', [
                'payment_method' => $paymentMethod,
                'currency' => $currency,
            ]);

            return null;
        }

        return PlatformFeeRule::query()
            ->active()
            ->forMethod($paymentMethod, $currency)
            ->currentlyEffective($at);
    }

    private function fallbackRule(string $paymentMethod, string $currency): PlatformFeeRule
    {
        return new PlatformFeeRule([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'payment_method' => $paymentMethod,
            'currency' => $currency,
            'fixed_fee' => 0.30,
            'percentage_fee' => 2.00,
            'minimum_fee' => 0,
            'maximum_fee' => null,
            'settlement_delay_days' => 0,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
        ]);
    }
}
