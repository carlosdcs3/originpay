<?php

namespace App\Modules\Fees\Application\Actions;

use App\Models\PlatformFeeRule;
use App\Modules\Fees\Domain\Contracts\PlatformFeeSimulator;
use App\Services\Fees\PlatformFeeCalculator;
use App\Services\Fees\PlatformFeeResolver;
use App\Services\Fees\PlatformFeeResult;

class SimulatePlatformFeeAction implements PlatformFeeSimulator
{
    public function __construct(
        private readonly PlatformFeeResolver $resolver,
        private readonly PlatformFeeCalculator $calculator
    ) {
    }

    public function simulate(array $data): PlatformFeeResult
    {
        if (! empty($data['rule_id'])) {
            $rule = PlatformFeeRule::findOrFail($data['rule_id']);

            return $this->calculator->calculate((float) $data['amount'], $rule, $rule->scope);
        }

        return $this->resolver->resolve(
            $data['user_id'] ?? null,
            $data['payment_method'],
            (float) $data['amount'],
            $data['currency'] ?? 'BRL'
        );
    }
}
