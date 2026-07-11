<?php

namespace Tests\Feature;

use App\Enums\ProviderType;
use App\Enums\WebhookEventStatus;
use App\Http\Controllers\Backend\WebhookAdminController;
use App\Jobs\ProcessWebhookJob;
use App\Jobs\ReplayWebhookJob;
use App\Models\WebhookDlq;
use App\Models\WebhookEvent;
use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\GatewayResponseDTO;
use App\Payment\Modern\DTO\GatewayTransactionDTO;
use App\Payment\Modern\DTO\RefundDTO;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Payment\Modern\DTO\WithdrawDTO;
use App\Payment\Modern\ModernPaymentGatewayFactory;
use App\Payment\Modern\ModernPaymentGatewayInterface;
use App\Services\TransactionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebhookEventContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureWebhookTables();
    }

    public function test_webhook_event_initial_status_is_saved_without_exception(): void
    {
        $event = WebhookEvent::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_initial_status',
            'payload' => '{}',
            'headers' => '{}',
            'status' => WebhookEventStatus::RECEIVED,
        ]);

        $this->assertTrue($event->exists);
        $this->assertSame(WebhookEventStatus::RECEIVED, $event->status);
    }

    public function test_ReplayWebhook_job_creates_event_and_dispatches_processing_job(): void
    {
        Queue::fake();

        $dlq = WebhookDlq::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_replay_contract',
            'external_reference' => 'ref_replay_contract',
            'payload' => json_encode(['id' => 'evt_replay_contract']),
            'headers' => '{}',
            'error_message' => 'controlled failure',
            'error_class' => \RuntimeException::class,
        ]);

        (new ReplayWebhookJob($dlq))->handle();

        $event = WebhookEvent::where('provider', ProviderType::MANUAL->value)
            ->where('event_id', 'evt_replay_contract')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame(WebhookEventStatus::RECEIVED, $event->status);
        $this->assertSame($dlq->id, $event->metadata['original_dlq_id']);
        Queue::assertPushed(ProcessWebhookJob::class);
    }

    public function test_ReplayWebhook_admin_redirects_success_without_value_error(): void
    {
        Queue::fake();

        $dlq = WebhookDlq::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_admin_replay_contract',
            'payload' => '{}',
            'headers' => '{}',
            'error_message' => 'controlled failure',
            'error_class' => \RuntimeException::class,
        ]);

        $response = $this
            ->withoutMiddleware()
            ->from('/admin/webhooks?tab=dlq')
            ->post(route('admin.webhooks.reprocessSingle', $dlq->id));

        $response->assertRedirect('/admin/webhooks?tab=dlq');
        Queue::assertPushed(ReplayWebhookJob::class);
    }

    public function test_process_webhook_job_handles_replayed_event_without_column_or_enum_errors(): void
    {
        $event = WebhookEvent::create([
            'provider' => ProviderType::MANUAL->value,
            'event_id' => 'evt_process_contract',
            'payload' => json_encode(['id' => 'evt_process_contract']),
            'headers' => '{}',
            'status' => WebhookEventStatus::RECEIVED,
        ]);

        $factory = $this->mock(ModernPaymentGatewayFactory::class, function ($mock) {
            $mock->shouldReceive('getGateway')
                ->once()
                ->with(ProviderType::MANUAL)
                ->andReturn(new ContractTestModernGateway());
        });

        $transactionService = $this->mock(TransactionService::class, function ($mock) {
            $mock->shouldReceive('processModernWebhook')
                ->once()
                ->with(\Mockery::type(WebhookDTO::class), ProviderType::MANUAL);
        });

        (new ProcessWebhookJob($event))->handle($factory, $transactionService);

        $event->refresh();
        $this->assertSame(WebhookEventStatus::PROCESSED, $event->status);
        $this->assertNull($event->last_error);
    }

    public function test_modern_webhook_for_unimplemented_provider_returns_controlled_error(): void
    {
        $response = $this->postJson('/api/webhook/modern/stripe', [
            'id' => 'evt_unimplemented_provider',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.type', 'invalid_request');
    }

    private function ensureWebhookTables(): void
    {
        if (!Schema::hasTable('webhook_events')) {
            Schema::create('webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('provider');
                $table->string('event_id')->nullable();
                $table->string('external_reference')->nullable();
                $table->string('event_type')->nullable();
                $table->longText('payload');
                $table->longText('headers')->nullable();
                $table->string('status')->default('RECEIVED');
                $table->integer('attempts')->default(0);
                $table->timestamp('processed_at')->nullable();
                $table->text('last_error')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('resolution_admin_id')->nullable();
                $table->text('resolution_reason')->nullable();
                $table->timestamps();
                $table->unique(['provider', 'event_id']);
            });
        }

        if (!Schema::hasTable('webhook_dlqs')) {
            Schema::create('webhook_dlqs', function (Blueprint $table) {
                $table->id();
                $table->string('provider');
                $table->string('event_id')->nullable();
                $table->string('external_reference')->nullable();
                $table->longText('payload');
                $table->longText('headers')->nullable();
                $table->text('error_message')->nullable();
                $table->string('error_class')->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolution_admin_id')->nullable();
                $table->text('resolution_reason')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('webhook_admin_audits')) {
            Schema::create('webhook_admin_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('action');
                $table->string('target_type');
                $table->unsignedBigInteger('target_id');
                $table->string('batch_id')->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }
}

class ContractTestModernGateway implements ModernPaymentGatewayInterface
{
    public function createDeposit(DepositDTO $dto): GatewayResponseDTO { return new GatewayResponseDTO(true); }
    public function createPix(DepositDTO $dto): GatewayResponseDTO { return new GatewayResponseDTO(true); }
    public function createCheckout(DepositDTO $dto): GatewayResponseDTO { return new GatewayResponseDTO(true); }
    public function verifyWebhook(Request $request): bool { return true; }
    public function parseWebhook(Request $request): WebhookDTO
    {
        return new WebhookDTO(
            providerTransactionId: 'evt_process_contract',
            externalReference: null,
            status: 'PAID',
            amount: 1.0,
            currency: 'BRL',
            rawPayload: json_decode($request->getContent(), true)
        );
    }
    public function refund(RefundDTO $dto): GatewayResponseDTO { return new GatewayResponseDTO(true); }
    public function withdraw(WithdrawDTO $dto): GatewayResponseDTO { return new GatewayResponseDTO(true); }
    public function getTransaction(string $providerTrxId): GatewayTransactionDTO
    {
        return new GatewayTransactionDTO($providerTrxId, 'PAID', 1.0, 'BRL');
    }
    public function healthCheck(): string { return 'CONNECTED'; }
}
