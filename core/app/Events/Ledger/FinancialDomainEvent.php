<?php

namespace App\Events\Ledger;

use Illuminate\Support\Carbon;

interface FinancialDomainEvent
{
    public function getGatewayId(): ?int;
    public function getWalletId(): ?int;
    public function getTransactionId(): ?string;
    public function getCorrelationId(): string;
    public function getOccurredAt(): Carbon;
}
