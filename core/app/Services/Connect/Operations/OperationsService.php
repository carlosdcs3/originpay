<?php
namespace App\Services\Connect\Operations;

class OperationsService
{
    public OperationsCampaignService $campaigns;
    public OperationsQueueService $queues;
    public OperationsDlqService $dlq;

    public function __construct(
        OperationsCampaignService $campaigns,
        OperationsQueueService $queues,
        OperationsDlqService $dlq
    ) {
        $this->campaigns = $campaigns;
        $this->queues = $queues;
        $this->dlq = $dlq;
    }
    
    // Facade methods
    public function getDashboardMetrics(int $merchantId)
    {
        return [
            'active_campaigns' => $this->campaigns->getActiveCampaigns($merchantId),
            // ... aggregates from Snapshots
        ];
    }
}
