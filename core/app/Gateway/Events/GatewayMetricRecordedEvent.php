<?php

namespace App\Gateway\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GatewayMetricRecordedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $gatewaySlug,
        public readonly string $operation,
        public readonly int $statusCode,
        public readonly int $latencyMs,
        public readonly bool $success,
        public readonly string $correlationId
    ) {}
}
