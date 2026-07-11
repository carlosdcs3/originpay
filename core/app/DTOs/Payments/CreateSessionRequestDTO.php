<?php

namespace App\DTOs\Payments;

class CreateSessionRequestDTO
{
    private float $amount;
    private string $currency;
    private string $referenceId;
    private array $customer;

    public function __construct(float $amount, string $currency, string $referenceId, array $customer)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->referenceId = $referenceId;
        $this->customer = $customer;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getCustomer(): array
    {
        return $this->customer;
    }
}
