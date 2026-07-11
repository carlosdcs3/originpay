<?php

namespace Tests\Feature;

use App\Events\ChargePaidEvent;
use App\Listeners\DispatchWebhooksListener;
use App\Listeners\SendChargePaidEmailListener;
use App\Models\Charge;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChargePaidEventListenerTest extends TestCase
{
    public function test_ChargePaidEvent_dispatch_queues_each_listener_once(): void
    {
        Queue::fake();

        $charge = new Charge([
            'uuid' => 'charge-paid-event-test',
            'user_id' => 1,
            'amount' => 10,
        ]);

        Event::dispatch(new ChargePaidEvent($charge, 10.0));

        $queuedListeners = Queue::pushed(CallQueuedListener::class);
        $listenerCounts = [];

        foreach ($queuedListeners as $entry) {
            $job = is_array($entry) ? $entry[0] : $entry;
            $listenerCounts[$job->class] = ($listenerCounts[$job->class] ?? 0) + 1;
        }

        $this->assertSame(2, $queuedListeners->count());
        $this->assertSame(1, $listenerCounts[DispatchWebhooksListener::class] ?? 0);
        $this->assertSame(1, $listenerCounts[SendChargePaidEmailListener::class] ?? 0);
    }
}
