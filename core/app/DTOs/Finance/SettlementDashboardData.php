<?php

namespace App\DTOs\Finance;

use Illuminate\Contracts\Support\Arrayable;

class SettlementDashboardData implements Arrayable
{
    public function __construct(
        public readonly array $kpis,
        public readonly object $settlements,
        public readonly array $alerts
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => $this->kpis,
            'settlements' => $this->settlements,
            'alerts' => $this->alerts,
        ];
    }
}
