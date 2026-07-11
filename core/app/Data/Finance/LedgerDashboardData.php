<?php

namespace App\Data\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LedgerDashboardData
{
    public function __construct(
        public array $kpis,
        public LengthAwarePaginator $transactions,
        public array $activeGateways,
        public array $filters
    ) {}
}
