<?php

namespace App\DTOs\Finance;

use Illuminate\Contracts\Support\Arrayable;

class TransactionDashboardData implements Arrayable
{
    public function __construct(
        public readonly array $kpis,
        public readonly object $transactions,
        public readonly array $alerts
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => $this->kpis,
            'transactions' => $this->transactions,
            'alerts' => $this->alerts,
        ];
    }
}
