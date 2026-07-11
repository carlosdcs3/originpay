<?php

namespace App\Data\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionDashboardData
{
    public function __construct(
        public array $kpis,
        public LengthAwarePaginator $transactions,
        public array $filters
    ) {}
}
