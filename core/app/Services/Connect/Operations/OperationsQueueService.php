<?php
namespace App\Services\Connect\Operations;

use Illuminate\Support\Facades\Queue;

class OperationsQueueService
{
    public function getQueueStats(string $queueName)
    {
        return [
            'queue' => $queueName,
            'size' => Queue::size($queueName),
            // Extensible: Fetching delayed/failed jobs from Redis directly or DB depending on connection
            // 'failed' => app('queue.failer')->count(),
        ];
    }
}
