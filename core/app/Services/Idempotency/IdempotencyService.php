<?php

namespace App\Services\Idempotency;

use App\Repositories\Idempotency\EloquentIdempotencyRepository;
use App\Models\IdempotencyKey;

class IdempotencyService
{
    public function __construct(
        private readonly EloquentIdempotencyRepository $repository
    ) {
    }

    public function checkAndStore(string $merchantId, string $idempotencyKey, string $method, string $path, string $body): ?IdempotencyKey
    {
        $hash = hash('sha256', $body);
        
        return $this->repository->findOrLock($merchantId, $idempotencyKey, $method, $path, $hash);
    }

    public function updateResponse(IdempotencyKey $key, int $status, ?array $responseBody): void
    {
        $this->repository->updateResponse($key, $status, $responseBody);
    }
}
