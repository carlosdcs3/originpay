<?php

namespace App\Jobs\Connect;

use App\Events\Connect\Campaign\CampaignRecipientProcessed;
use App\Models\Connect\ConnectCampaignDlq;
use App\Models\Connect\ConnectCampaignRecipient;
use App\Services\Connect\CampaignQuotaService;
use App\Services\Connect\DeliveryService;
use App\Services\Connect\Template\TemplateEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ProcessCampaignRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recipientId;

    protected int $merchantId;

    protected string $correlationId;

    public function __construct($recipientId, ?int $merchantId = null, ?string $correlationId = null)
    {
        $this->recipientId = $recipientId;
        $this->merchantId = $merchantId ?? 0;
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    public function handle(TemplateEngine $engine, DeliveryService $deliveryService, CampaignQuotaService $quotas)
    {
        $executionId = null;

        $recipientMeta = ConnectCampaignRecipient::where('id', $this->recipientId)
            ->when($this->merchantId > 0, fn ($query) => $query->where('merchant_id', $this->merchantId))
            ->select('id', 'merchant_id', 'channel', 'campaign_id', 'attempts', 'status')->first();

        if (! $recipientMeta || in_array($recipientMeta->status, [ConnectCampaignRecipient::STATUS_PROCESSED, ConnectCampaignRecipient::STATUS_SKIPPED])) {
            return;
        }

        if (! $quotas->allowsRecipients((int) $recipientMeta->merchant_id, 1)) {
            return;
        }

        // Rate Limiter Strategy
        $rateLimitKey = "connect_campaign_{$recipientMeta->channel}_merchant_{$recipientMeta->merchant_id}";
        $maxPerMinute = match ($recipientMeta->channel) {
            'whatsapp' => 1200,
            'email' => 6000,
            default => 600
        };

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxPerMinute)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->release($seconds);

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        DB::transaction(function () use ($engine, $deliveryService, &$executionId) {
            $recipient = ConnectCampaignRecipient::where('id', $this->recipientId)
                ->when($this->merchantId > 0, fn ($query) => $query->where('merchant_id', $this->merchantId))
                ->lockForUpdate()->first();

            if (! $recipient || in_array($recipient->status, [ConnectCampaignRecipient::STATUS_PROCESSED, ConnectCampaignRecipient::STATUS_SKIPPED])) {
                return;
            }

            $executionId = $recipient->execution_id;
            $campaign = $recipient->campaign;

            $recipient->update([
                'status' => ConnectCampaignRecipient::STATUS_PROCESSING,
                'attempts' => $recipient->attempts + 1,
                'last_attempt_at' => now(),
            ]);

            $contact = $recipient->contact;
            $ast = $campaign->metadata['snapshot']['template_ast'] ?? [];

            $context = [
                'contact.name' => $contact->name ?? '',
                'contact.email' => $contact->email ?? '',
            ];

            $compiledPayload = $engine->compile($ast, $recipient->channel, $context);

            // MAGIC HAPPENS HERE: DeliveryService replaces the switch cases!
            $result = $deliveryService->dispatch($recipient, $compiledPayload);

            $status = $result->success ? ConnectCampaignRecipient::STATUS_PROCESSED : ConnectCampaignRecipient::STATUS_FAILED;

            if ($status === ConnectCampaignRecipient::STATUS_FAILED) {
                if ($result->isTransient && $recipient->attempts < $campaign->max_attempts) {
                    // Retry
                    $status = ConnectCampaignRecipient::STATUS_QUEUED;
                    $retryDelay = $result->retryAfter ?? (30 * $recipient->attempts);
                    $this->release($retryDelay);
                } else {
                    // DLQ
                    ConnectCampaignDlq::create([
                        'uuid' => Str::uuid()->toString(),
                        'recipient_id' => $recipient->id,
                        'execution_id' => $executionId,
                        'merchant_id' => $recipient->merchant_id,
                        'channel' => $recipient->channel,
                        'last_error' => $result->errorMessage ?? 'Max attempts reached',
                        'payload_snapshot' => $compiledPayload,
                    ]);
                }
            }

            $recipient->update([
                'status' => $status,
                'processed_at' => now(),
                'payload_snapshot' => $compiledPayload,
                'failed_reason' => $result->errorMessage,
            ]);

            event(new CampaignRecipientProcessed($recipient->execution));
        });

        if ($executionId) {
            FinalizeCampaignExecutionJob::dispatch($executionId)
                ->onQueue('connect_system')
                ->delay(now()->addSeconds(2));
        }
    }
}
