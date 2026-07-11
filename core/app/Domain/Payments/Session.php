<?php

namespace App\Domain\Payments;

use DateTimeImmutable;

class Session
{
    private string $id;
    private float $amount;
    private string $currency;
    private string $referenceId;
    private array $customer;
    private string $status;
    private DateTimeImmutable $expiresAt;

    public function __construct(
        string $id,
        float $amount,
        string $currency,
        string $referenceId,
        array $customer,
        string $status,
        DateTimeImmutable $expiresAt
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->referenceId = $referenceId;
        $this->customer = $customer;
        $this->status = $status;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->id,
            'status' => $this->status,
            'expires_at' => $this->expiresAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
