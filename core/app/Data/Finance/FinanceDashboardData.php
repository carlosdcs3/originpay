<?php

namespace App\Data\Finance;

class FinanceDashboardData
{
    public function __construct(
        public KpiCollection $kpis,
        public ChartCollection $charts,
        public AlertCollection $alerts,
        public HealthCollection $health,
        public GatewayCollection $gateways,
        public WalletCollection $wallets
    ) {}
}

class KpiCollection
{
    public function __construct(
        public array $items = []
    ) {}
}

class ChartCollection
{
    public function __construct(
        public array $items = []
    ) {}
}

class AlertCollection
{
    public function __construct(
        public array $items = []
    ) {}
}

class HealthCollection
{
    public function __construct(
        public array $items = []
    ) {}
}

class GatewayCollection
{
    public function __construct(
        public array $items = []
    ) {}
}

class WalletCollection
{
    public function __construct(
        public array $items = []
    ) {}
}
