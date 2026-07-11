<?php

namespace App\DTOs\Finance;

use Illuminate\Contracts\Support\Arrayable;

class WithdrawalDashboardData implements Arrayable
{
    public function __construct(
        public readonly array $kpis,
        public readonly object $withdrawals,
        public readonly array $alerts
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => $this->kpis,
            'withdrawals' => $this->withdrawals,
            'alerts' => $this->alerts,
        ];
    }
}
