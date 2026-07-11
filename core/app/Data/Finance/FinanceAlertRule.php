<?php

namespace App\Data\Finance;

class FinanceAlertRule
{
    public function __construct(
        public string $condition,
        public string $severity,
        public int $cooldownMinutes,
        public bool $enabled,
        public string $message,
        public string $category
    ) {}
}
