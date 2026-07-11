<?php

namespace App\Domain\Core;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Carbon\Carbon;

abstract class DomainEvent
{
    use Dispatchable, SerializesModels;

    public readonly string $eventId;
    public readonly string $correlationId;
    public readonly ?string $causationId;
    public readonly Carbon $occurredAt;
    
    public readonly ?string $actorType;
    public readonly ?string $actorId;

    public function __construct()
    {
        $this->eventId = (string) Str::uuid();
        $this->correlationId = EventContext::getCorrelationId();
        $this->causationId = EventContext::getCausationId();
        $this->occurredAt = now();
        $this->actorType = EventContext::getActorType();
        $this->actorId = EventContext::getActorId();

        // When this event is constructed, it becomes the causation ID for any nested events triggered during the same tick.
        EventContext::setCausationId($this->eventId);
    }

    abstract public function getAggregateId(): string;
    
    abstract public function getAggregateType(): string;
    
    abstract public function getPayloadVersion(): int;
    
    abstract public function getPayload(): array;
}
