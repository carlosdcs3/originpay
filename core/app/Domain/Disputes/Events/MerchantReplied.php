<?php

namespace App\Domain\Disputes\Events;

use App\Domain\Core\DomainEvent;
use App\Domain\Core\ReplayableEvent;
use App\Models\Dispute;

class MerchantReplied extends DomainEvent implements ReplayableEvent
{
    public function __construct(
        public readonly Dispute $dispute,
        public readonly string $messageContent
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return (string) $this->dispute->id;
    }

    public function getAggregateType(): string
    {
        return 'dispute';
    }

    public function getPayloadVersion(): int
    {
        return 1;
    }

    public function getPayload(): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'merchant_id' => $this->dispute->merchant_id,
            'message_content' => $this->messageContent,
        ];
    }

    public function isReplayable(): bool
    {
        return true;
    }
}
