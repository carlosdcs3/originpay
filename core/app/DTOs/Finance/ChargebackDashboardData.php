<?php

namespace App\DTOs\Finance;

use Illuminate\Contracts\Support\Arrayable;

class ChargebackDashboardData implements Arrayable
{
    public function __construct(
        public readonly array $kpis,
        public readonly object $chargebacks,
        public readonly array $alerts
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => $this->kpis,
            'chargebacks' => $this->chargebacks,
            'alerts' => $this->alerts,
        ];
    }
}
