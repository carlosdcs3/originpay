<?php

namespace App\Services;

use App\Models\Dispute;
use App\Enums\DisputeStatus;
use Exception;

class DisputeStateMachine
{
    /**
     * Allowed transitions from key => array of allowed statuses
     */
    protected const TRANSITIONS = [
        'received' => ['waiting_merchant_docs', 'under_review', 'canceled'],
        'waiting_merchant_docs' => ['docs_received', 'lost', 'won', 'canceled'],
        'docs_received' => ['under_review', 'canceled'],
        'under_review' => ['evidence_sent', 'gateway_review', 'won', 'lost', 'canceled'],
        'evidence_sent' => ['gateway_review', 'won', 'lost', 'canceled'],
        'gateway_review' => ['bank_review', 'pending_decision', 'won', 'lost', 'canceled'],
        'bank_review' => ['pending_decision', 'won', 'lost', 'canceled'],
        'pending_decision' => ['won', 'lost', 'canceled'],
    ];

    /**
     * Assert if a transition is allowed.
     */
    public function assertCanTransition(Dispute $dispute, DisputeStatus $newStatus): void
    {
        $current = $dispute->status->value;
        $next = $newStatus->value;

        // If it's already closed/won/lost/canceled, no further transitions allowed (terminal state)
        if (in_array($current, ['won', 'lost', 'closed', 'canceled'])) {
            throw new Exception("Cannot transition from terminal state: {$current}");
        }

        $allowed = self::TRANSITIONS[$current] ?? [];

        // Operational bypass rule: allow direct close if explicitly requested by admin
        // We will log this in the event service.
        if (in_array($next, ['won', 'lost', 'canceled'])) {
            return;
        }

        if (!in_array($next, $allowed)) {
            throw new Exception("Invalid transition from {$current} to {$next}");
        }
    }
}
