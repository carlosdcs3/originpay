<?php

namespace App\Services\PaymentMethod;

use App\Contracts\PaymentMethod\PaymentMethodRepositoryInterface;
use App\Contracts\PaymentMethod\PaymentMethodVaultInterface;
use App\DTOs\PaymentMethod\CreatePaymentMethodRequestDTO;
use App\DTOs\PaymentMethod\PaymentMethodResponseDTO;
use App\Factories\PaymentMethod\PaymentMethodFactory;
use App\Domain\PaymentMethod\PaymentMethod;

class PaymentMethodService
{
    private PaymentMethodVaultInterface $vault;
    private PaymentMethodRepositoryInterface $repository;
    private PaymentMethodFactory $factory;

    public function __construct(
        PaymentMethodVaultInterface $vault,
        PaymentMethodRepositoryInterface $repository,
        PaymentMethodFactory $factory
    ) {
        $this->vault = $vault;
        $this->repository = $repository;
        $this->factory = $factory;
    }

    public function createPaymentMethod(CreatePaymentMethodRequestDTO $dto): PaymentMethodResponseDTO
    {
        // 1. Store sensitive data in Vault (Mock for Sprint 4)
        $vaultToken = $this->vault->storeMock($dto);

        // 2. Build domain entity (masking PAN, resolving expiration, generating ID and Fingerprint)
        $paymentMethod = $this->factory->createFromRequest($dto);

        // 3. Optional logic: Link vault token to the entity metadata if necessary
        // but for now, we just ensure it is created safely without exposing PAN/CVV.

        // 4. Save entity to repository
        $this->repository->save($paymentMethod);

        // 5. Return safe response DTO
        return $this->toResponseDTO($paymentMethod);
    }

    public function getPaymentMethod(string $id): ?PaymentMethodResponseDTO
    {
        $paymentMethod = $this->repository->findById($id);

        if (!$paymentMethod) {
            return null;
        }

        return $this->toResponseDTO($paymentMethod);
    }

    private function toResponseDTO(PaymentMethod $paymentMethod): PaymentMethodResponseDTO
    {
        return new PaymentMethodResponseDTO(
            $paymentMethod->getId(),
            $paymentMethod->getType(),
            $paymentMethod->getStatus(),
            $paymentMethod->getFingerprint(),
            $paymentMethod->getLast4(),
            $paymentMethod->getBrand(),
            $paymentMethod->getExpiresAt() ? $paymentMethod->getExpiresAt()->format('Y-m-d\TH:i:s\Z') : null,
            $paymentMethod->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            $paymentMethod->getMetadata()
        );
    }
}
