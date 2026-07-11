<?php

namespace App\DTOs\Finance;

use Illuminate\Contracts\Support\Arrayable;

class WalletDashboardData implements Arrayable
{
    public function __construct(
        public readonly array $kpis,
        public readonly object $wallets,
        public readonly array $alerts,
        public readonly array $charts,
        public readonly array $distributionData = []
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => $this->kpis,
            'wallets' => $this->wallets,
            'alerts' => $this->alerts,
            'charts' => $this->charts,
            'distributionData' => $this->distributionData,
        ];
    }
}
