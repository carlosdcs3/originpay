<?php

namespace App\Services\Fees;

class PlatformFeeResult
{
    public function __construct(
        public readonly float $grossAmount,
        public readonly float $platformFeeAmount,
        public readonly float $netAmount,
        public readonly ?int $ruleId,
        public readonly string $source,
        public readonly array $snapshot,
    ) {
    }

    public function toArray(): array
    {
        return [
            'gross_amount' => $this->grossAmount,
            'platform_fee_amount' => $this->platformFeeAmount,
            'net_amount' => $this->netAmount,
            'rule_id' => $this->ruleId,
            'source' => $this->source,
            'snapshot' => $this->snapshot,
        ];
    }
}
