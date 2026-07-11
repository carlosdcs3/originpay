<?php

namespace App\Data\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeeDashboardData
{
    public function __construct(
        public array $kpis,
        public LengthAwarePaginator $fees,
        public array $filters
    ) {}
}
