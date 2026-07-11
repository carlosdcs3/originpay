<?php

namespace App\Domain\Disputes\Events;

use App\Domain\Core\DomainEvent;
use App\Models\Dispute;
use App\Models\DisputeEvidenceItem;

class EvidenceUploaded extends DomainEvent
{
    public function __construct(
        public readonly Dispute $dispute,
        public readonly DisputeEvidenceItem $evidence
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
            'evidence_id' => $this->evidence->id,
        ];
    }
}
