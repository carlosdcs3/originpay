<?php

namespace App\Services\Payments;

use App\Contracts\Payments\SessionRepositoryInterface;
use App\Domain\Payments\Session;
use App\DTOs\Payments\CreateSessionRequestDTO;
use App\DTOs\Payments\SessionResponseDTO;
use Illuminate\Support\Str;
use DateTimeImmutable;

class SessionService
{
    private SessionRepositoryInterface $repository;

    public function __construct(SessionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function createSession(CreateSessionRequestDTO $dto): SessionResponseDTO
    {
        $id = 'cs_' . Str::uuid()->toString();
        $expiresAt = (new DateTimeImmutable())->modify('+30 minutes');

        $session = new Session(
            $id,
            $dto->getAmount(),
            $dto->getCurrency(),
            $dto->getReferenceId(),
            $dto->getCustomer(),
            'AWAITING_PAYMENT_METHOD',
            $expiresAt
        );

        $this->repository->save($session);

        return new SessionResponseDTO(
            $session->getId(),
            $session->getStatus(),
            $session->getExpiresAt()->format('Y-m-d\TH:i:s\Z')
        );
    }

    public function getSession(string $id): ?SessionResponseDTO
    {
        $session = $this->repository->findById($id);

        if (!$session) {
            return null;
        }

        return new SessionResponseDTO(
            $session->getId(),
            $session->getStatus(),
            $session->getExpiresAt()->format('Y-m-d\TH:i:s\Z')
        );
    }
}
