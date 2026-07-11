<?php

namespace App\DTOs\Finance;

use Illuminate\Contracts\Support\Arrayable;

class FeeDashboardData implements Arrayable
{
    public function __construct(
        public readonly array $kpis,
        public readonly object $fees,
        public readonly array $alerts
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => $this->kpis,
            'fees' => $this->fees,
            'alerts' => $this->alerts,
        ];
    }
}
