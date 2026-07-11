<?php

namespace App\Data\Finance;

use Illuminate\Support\Carbon;

class FinanceHealthReport
{
    public function __construct(
        public int $overallScore,
        public string $status, // Healthy, Warning, Critical
        /** @var FinanceHealthCheck[] */
        public array $checks = [],
        /** @var FinanceHealthCheck[] */
        public array $warnings = [],
        /** @var FinanceHealthCheck[] */
        public array $criticalIssues = [],
        public ?Carbon $generatedAt = null
    ) {
        $this->generatedAt = $generatedAt ?? now();
    }
}
