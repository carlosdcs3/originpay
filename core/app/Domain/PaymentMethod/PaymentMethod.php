<?php

namespace App\Domain\PaymentMethod;

use DateTimeInterface;

class PaymentMethod
{
    private string $id;
    private string $type;
    private string $status;
    private string $fingerprint;
    private ?string $last4;
    private ?string $brand;
    private ?DateTimeInterface $expiresAt;
    private DateTimeInterface $createdAt;
    private array $metadata;

    public function __construct(
        string $id,
        string $type,
        string $status,
        string $fingerprint,
        ?string $last4,
        ?string $brand,
        ?DateTimeInterface $expiresAt,
        DateTimeInterface $createdAt,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->status = $status;
        $this->fingerprint = $fingerprint;
        $this->last4 = $last4;
        $this->brand = $brand;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt;
        $this->metadata = $metadata;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isExpired(DateTimeInterface $now = null): bool
    {
        if (!$this->expiresAt) {
            return false;
        }
        
        $now = $now ?? new \DateTimeImmutable();
        return $this->expiresAt < $now;
    }
}
