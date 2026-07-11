<?php

namespace App\DTOs\PaymentMethod;

class CreatePaymentMethodRequestDTO
{
    private string $type;
    private ?string $pan;
    private ?string $expMonth;
    private ?string $expYear;
    private ?string $cvv;
    private ?string $holderName;
    private ?PaymentMethodMetadataDTO $metadata;

    public function __construct(
        string $type,
        ?string $pan = null,
        ?string $expMonth = null,
        ?string $expYear = null,
        ?string $cvv = null,
        ?string $holderName = null,
        ?PaymentMethodMetadataDTO $metadata = null
    ) {
        $this->type = $type;
        $this->pan = $pan;
        $this->expMonth = $expMonth;
        $this->expYear = $expYear;
        $this->cvv = $cvv;
        $this->holderName = $holderName;
        $this->metadata = $metadata;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPan(): ?string
    {
        return $this->pan;
    }

    public function getExpMonth(): ?string
    {
        return $this->expMonth;
    }

    public function getExpYear(): ?string
    {
        return $this->expYear;
    }

    public function getCvv(): ?string
    {
        return $this->cvv;
    }

    public function getHolderName(): ?string
    {
        return $this->holderName;
    }

    public function getMetadata(): ?PaymentMethodMetadataDTO
    {
        return $this->metadata;
    }
}
