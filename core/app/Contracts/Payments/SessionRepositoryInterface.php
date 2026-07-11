<?php

namespace App\Contracts\Payments;

use App\Domain\Payments\Session;

interface SessionRepositoryInterface
{
    public function save(Session $session): void;
    public function findById(string $id): ?Session;
}
