<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domain\Payments\Charge;

class ChargeStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Charge $charge,
        public readonly string $eventType
    ) {}
}
