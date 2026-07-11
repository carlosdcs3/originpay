<?php

namespace App\Events\Ledger;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GatewayAuditFailed implements FinancialDomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $correlationId;
    public Carbon $occurredAt;

    public function __construct(
        public array $auditResults
    ) {
        $this->correlationId = Str::uuid()->toString();
        $this->occurredAt = now();
    }

    public function getGatewayId(): ?int { return null; }
    public function getWalletId(): ?int { return null; }
    public function getTransactionId(): ?string { return null; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function getOccurredAt(): Carbon { return $this->occurredAt; }
}
