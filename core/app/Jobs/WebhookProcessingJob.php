<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WebhookEvent;
use App\Enums\WebhookEventStatus;
use App\Services\WebhookProcessingService;
use Exception;
use Illuminate\Support\Facades\Log;

class WebhookProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $webhookEvent;

    /**
     * Número de segundos que o job pode rodar antes do timeout
     */
    public $timeout = 30; // Override pela Policy se preciso
    
    /**
     * Quantidade máxima de retries (fallback)
     */
    public $tries = 5;

    public function __construct(WebhookEvent $webhookEvent)
    {
        $this->webhookEvent = $webhookEvent;
    }

    public function handle(WebhookProcessingService $processingService)
    {
        try {
            $this->webhookEvent->status = WebhookEventStatus::PROCESSING;
            $this->webhookEvent->attempts += 1;
            $this->webhookEvent->save();

            // O Serviço converte raw_payload para DTO e acha o Handler via Resolver
            $processingService->process($this->webhookEvent);

            $this->webhookEvent->status = WebhookEventStatus::PROCESSED;
            $this->webhookEvent->processed_at = now();
            $this->webhookEvent->save();

        } catch (Exception $e) {
            Log::error("WebhookProcessingJob Failed", [
                'event_id' => $this->webhookEvent->id,
                'correlation_id' => $this->webhookEvent->correlation_id,
                'error' => $e->getMessage()
            ]);

            $this->webhookEvent->status = WebhookEventStatus::FAILED;
            $this->webhookEvent->error_message = $e->getMessage();
            $this->webhookEvent->failed_at = now();
            $this->webhookEvent->save();

            throw $e; // Re-joga para o Laravel Queue Manager lidar com retries
        }
    }

    /**
     * Se o job falhar permanentemente (após X retries), vira DLQ
     */
    public function failed(Exception $exception)
    {
        $this->webhookEvent->status = WebhookEventStatus::DEAD_LETTER;
        $this->webhookEvent->save();
        
        // Aqui acionariamos o FinanceAlertService
    }
}
