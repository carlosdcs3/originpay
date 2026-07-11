<?php

namespace App\Repositories\Payments;

use App\Contracts\Payments\SessionRepositoryInterface;
use App\Domain\Payments\Session;

class MockSessionRepository implements SessionRepositoryInterface
{
    /** @var Session[] */
    private static array $storage = [];

    public function save(Session $session): void
    {
        self::$storage[$session->getId()] = $session;
    }

    public function findById(string $id): ?Session
    {
        return self::$storage[$id] ?? null;
    }

    public static function flushMockStorage(): void
    {
        self::$storage = [];
    }
}
