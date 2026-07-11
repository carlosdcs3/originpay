<?php

namespace App\DTOs\PaymentMethod;

class PaymentMethodResponseDTO
{
    private string $id;
    private string $type;
    private string $status;
    private string $fingerprint;
    private ?string $last4;
    private ?string $brand;
    private ?string $expiresAt;
    private string $createdAt;
    private array $metadata;

    public function __construct(
        string $id,
        string $type,
        string $status,
        string $fingerprint,
        ?string $last4,
        ?string $brand,
        ?string $expiresAt,
        string $createdAt,
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'fingerprint' => $this->fingerprint,
            'last4' => $this->last4,
            'brand' => $this->brand,
            'expires_at' => $this->expiresAt,
            'created_at' => $this->createdAt,
            'metadata' => $this->metadata,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }
}
