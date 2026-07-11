<?php
namespace App\Services\Connect\Campaign;

use App\Models\Connect\Campaign;
use Exception;

class CampaignStateMachine
{
    public function transitionTo(Campaign $campaign, string $newStatus)
    {
        $current = $campaign->status;

        if ($current === $newStatus) return;

        $valid = match ($current) {
            Campaign::STATUS_DRAFT => in_array($newStatus, [Campaign::STATUS_SCHEDULED, Campaign::STATUS_PREPARING]),
            Campaign::STATUS_SCHEDULED => in_array($newStatus, [Campaign::STATUS_PREPARING, Campaign::STATUS_CANCELLED, Campaign::STATUS_DRAFT]),
            Campaign::STATUS_PREPARING => in_array($newStatus, [Campaign::STATUS_RESOLVING, Campaign::STATUS_FAILED, Campaign::STATUS_CANCELLED]),
            Campaign::STATUS_RESOLVING => in_array($newStatus, [Campaign::STATUS_QUEUEING, Campaign::STATUS_FAILED, Campaign::STATUS_CANCELLED]),
            Campaign::STATUS_QUEUEING => in_array($newStatus, [Campaign::STATUS_RUNNING, Campaign::STATUS_FAILED, Campaign::STATUS_CANCELLED]),
            Campaign::STATUS_RUNNING => in_array($newStatus, [Campaign::STATUS_COMPLETED, Campaign::STATUS_CANCELLED, Campaign::STATUS_FAILED]),
            default => false,
        };

        if (!$valid) {
            throw new Exception("Transição de status inválida: {$current} -> {$newStatus}");
        }

        $campaign->status = $newStatus;
    }
}
