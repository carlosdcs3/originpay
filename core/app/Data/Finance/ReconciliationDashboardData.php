<?php

namespace App\Data\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReconciliationDashboardData
{
    public function __construct(
        public array $kpis,
        public LengthAwarePaginator $reconciliations,
        public array $activeGateways,
        public array $filters
    ) {}
}
