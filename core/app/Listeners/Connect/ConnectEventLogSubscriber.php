<?php
namespace App\Listeners\Connect;

use App\Models\Connect\ConnectEventLog;
use Illuminate\Support\Str;

class ConnectEventLogSubscriber
{
    public function handleCampaignEvent($event)
    {
        $campaign = $event->campaign ?? ($event->execution->campaign ?? null);
        if (!$campaign) return;

        $eventName = class_basename($event);
        
        ConnectEventLog::create([
            'uuid' => Str::uuid()->toString(),
            'merchant_id' => $campaign->merchant_id,
            'aggregate_type' => 'Campaign',
            'aggregate_id' => $campaign->id,
            'event_type' => $eventName,
            'payload' => ['metadata' => $event->execution->metadata ?? []],
            'occurred_at' => now(),
        ]);
    }

    public function subscribe($events)
    {
        $events->listen('App\Events\Connect\Campaign\*', [ConnectEventLogSubscriber::class, 'handleCampaignEvent']);
    }
}
