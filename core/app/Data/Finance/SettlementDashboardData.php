<?php

namespace App\Data\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SettlementDashboardData
{
    public function __construct(
        public array $kpis,
        public LengthAwarePaginator $settlements,
        public array $filters
    ) {}
}
