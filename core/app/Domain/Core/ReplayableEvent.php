<?php

namespace App\Domain\Core;

interface ReplayableEvent
{
    /**
     * Defines whether this event is safe to be replayed (idempotent).
     */
    public function isReplayable(): bool;
}
