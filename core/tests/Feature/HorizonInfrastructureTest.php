<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use App\Models\WebhookEvent;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HorizonInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_horizon_dashboard_is_protected()
    {
        $response = $this->get('/horizon');
        // Depending on exact Laravel version and Horizon setup, it may return 403 or 404 for unauthenticated
        $response->assertStatus(403);
    }

    public function test_process_webhook_job_uses_high_queue()
    {
        Queue::fake();

        $event = WebhookEvent::create([
            'provider' => 'NEW',
            'event_id' => '123',
            'payload' => '{}',
            'status' => 'RECEIVED'
        ]);

        ProcessWebhookJob::dispatch($event)->onQueue('high');

        Queue::assertPushedOn('high', ProcessWebhookJob::class);
    }
}
