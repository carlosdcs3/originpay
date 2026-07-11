<?php

namespace App\Data\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChargebackDashboardData
{
    public function __construct(
        public array $kpis,
        public LengthAwarePaginator $chargebacks,
        public array $filters
    ) {}
}
