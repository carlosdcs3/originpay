<?php

namespace App\Data\Finance;

class FinanceHealthCheck
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status, // PASS, WARNING, FAIL
        public string $severity, // LOW, MEDIUM, HIGH, CRITICAL
        public string $description,
        public string $recommendation,
        public array $metadata = []
    ) {}
}
