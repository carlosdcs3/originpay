<?php

namespace App\Domain\Core;

interface EventSerializer
{
    /**
     * Serializes a DomainEvent into a standard array format suitable for Outbox storage or Message Brokers.
     */
    public function serialize(DomainEvent $event): array;

    /**
     * Deserializes a payload array back into a specific DomainEvent instance.
     */
    public function deserialize(array $payload): DomainEvent;
}
