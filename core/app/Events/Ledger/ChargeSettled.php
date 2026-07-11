<?php

namespace App\Events\Ledger;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ChargeSettled implements FinancialDomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $correlationId;
    public Carbon $occurredAt;

    public function __construct(
        public int $walletId,
        public int $gatewayId,
        public float $amount,
        public string $transactionId
    ) {
        $this->correlationId = Str::uuid()->toString();
        $this->occurredAt = now();
    }

    public function getGatewayId(): ?int { return $this->gatewayId; }
    public function getWalletId(): ?int { return $this->walletId; }
    public function getTransactionId(): ?string { return $this->transactionId; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function getOccurredAt(): Carbon { return $this->occurredAt; }
}
