<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\WebhookEvent;
use App\Models\WebhookDlq;
use App\Jobs\ProcessWebhookJob;
use App\Jobs\ReplayWebhookJob;
use App\Payment\Modern\Providers\NewProviderGateway;
use App\Payment\Modern\ModernPaymentGatewayFactory;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use App\Enums\ProviderType;
use App\Enums\WebhookEventStatus;

class WebhookAsyncProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.new_provider.webhook_secret', 'my_sandbox_secret');
        Config::set('services.new_provider.api_key', 'my_api_key');
        Config::set('queue.default', 'sync'); // We will mock Queue later if needed, but 'sync' allows job to run, so let's use 'database' or fake it

        $factory = new ModernPaymentGatewayFactory();
        $factory->registerGateway(ProviderType::MANUAL, NewProviderGateway::class);
        $this->app->instance(ModernPaymentGatewayFactory::class, $factory);
    }

    private function postSignedWebhook(string $payload, string $signature, int $timestamp)
    {
        return $this->call(
            'POST',
            '/api/webhook/modern/manual',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_NEWPROVIDER_SIGNATURE' => $signature,
                'HTTP_X_NEWPROVIDER_TIMESTAMP' => (string) $timestamp,
            ],
            $payload
        );
    }

    public function test_valid_webhook_creates_event_and_dispatches_job()
    {
        Queue::fake();

        $payload = json_encode(['id' => 'evt_001', 'reference' => 'ref_1']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        $response = $this->postSignedWebhook($payload, $signature, $timestamp);

        $response->assertStatus(200);

        $this->assertEquals(1, WebhookEvent::count());
        $event = WebhookEvent::first();
        $this->assertEquals(WebhookEventStatus::RECEIVED, $event->status);
        $this->assertEquals('evt_001', $event->event_id);

        Queue::assertPushed(ProcessWebhookJob::class, function ($job) use ($event) {
            return $job->event->id === $event->id && $job->queue === 'high';
        });
    }

    public function test_invalid_webhook_does_not_create_event()
    {
        Queue::fake();

        $payload = json_encode(['id' => 'evt_002']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'WRONG_SECRET');

        $response = $this->postSignedWebhook($payload, $signature, $timestamp);

        $response->assertStatus(401);
        $this->assertEquals(0, WebhookEvent::count());
        Queue::assertNothingPushed();
    }

    public function test_duplicate_webhook_is_ignored()
    {
        WebhookEvent::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_003',
            'payload' => '{}',
            'status' => WebhookEventStatus::PROCESSED,
        ]);

        $payload = json_encode(['id' => 'evt_003']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        $response = $this->postSignedWebhook($payload, $signature, $timestamp);

        $response->assertStatus(200);
        $this->assertEquals(1, WebhookEvent::count()); // Still 1
    }

    public function test_worker_processes_and_marks_processed()
    {
        $event = WebhookEvent::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_004',
            'payload' => json_encode(['id' => 'evt_004', 'status' => 'PAID', 'amount' => 100]),
            'status' => WebhookEventStatus::RECEIVED,
        ]);

        $factory = $this->app->make(ModernPaymentGatewayFactory::class);
        $txService = $this->mock(TransactionService::class, function($mock) {
            $mock->shouldReceive('processModernWebhook')->once()->andReturnTrue();
        });

        $job = new ProcessWebhookJob($event);
        $job->handle($factory, $txService);

        $event->refresh();
        $this->assertEquals(WebhookEventStatus::PROCESSED, $event->status);
        $this->assertNotNull($event->processed_at);
        $this->assertEquals(1, $event->attempts);
    }

    public function test_retry_exhaustion_moves_to_dlq()
    {
        $event = WebhookEvent::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_005',
            'payload' => json_encode(['id' => 'evt_005']),
            'status' => WebhookEventStatus::RECEIVED,
        ]);

        $factory = $this->app->make(ModernPaymentGatewayFactory::class);
        $txService = $this->mock(TransactionService::class, function($mock) {
            $mock->shouldReceive('processModernWebhook')->andThrow(new \Exception('Deadlock Example'));
        });

        $job = new ProcessWebhookJob($event);
        
        try {
            $job->handle($factory, $txService);
        } catch (\Exception $e) {}

        $event->refresh();
        $this->assertEquals(WebhookEventStatus::PROCESSING, $event->status); // After 1 try it is still processing

        // Simulate failing the job completely (Max tries reached in Laravel Queue)
        $job->failed(new \Exception('Deadlock Example Maxed'));

        $event->refresh();
        $this->assertEquals(WebhookEventStatus::FAILED, $event->status);

        $dlq = WebhookDlq::first();
        $this->assertNotNull($dlq);
        $this->assertEquals('evt_005', $dlq->event_id);
    }

    public function test_replay_dlq_creates_new_event()
    {
        Queue::fake();

        $dlq = WebhookDlq::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_006',
            'payload' => '{}',
            'error_message' => 'failed',
            'error_class' => 'Exception',
        ]);

        $job = new ReplayWebhookJob($dlq);
        $job->handle();

        $event = WebhookEvent::first();
        $this->assertNotNull($event);
        $this->assertEquals(WebhookEventStatus::RECEIVED, $event->status);
        $this->assertEquals($dlq->id, $event->metadata['original_dlq_id']);

        Queue::assertPushed(ProcessWebhookJob::class);
    }
}
