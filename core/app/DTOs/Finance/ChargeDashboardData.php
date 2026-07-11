<?php

namespace App\DTOs\Finance;

use Illuminate\Contracts\Support\Arrayable;

class ChargeDashboardData implements Arrayable
{
    public function __construct(
        public readonly array $kpis,
        public readonly object $charges,
        public readonly array $alerts
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => $this->kpis,
            'charges' => $this->charges,
            'alerts' => $this->alerts,
        ];
    }
}
