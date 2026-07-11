<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WebhookDlq;
use App\Models\WebhookEvent;
use App\Enums\WebhookEventStatus;

class ReplayWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public WebhookDlq $dlq;

    public function __construct(WebhookDlq $dlq)
    {
        $this->dlq = $dlq;
    }

    public function handle()
    {
        // Se já foi resolvido, ignora
        if ($this->dlq->resolved_at !== null) {
            return;
        }

        // Recupera o payload original, se possível (Masking dificulta o replay perfeito se houver dados essenciais mascarados. 
        // Num cenário real de DLQ, ou se guarda os dados sensíveis criptografados, ou o MaskHelper não mascara dados essenciais pro parse).
        // Criar novo evento. Usamos um event_id diferente ou suffix para evitar unique index collison se o original ainda estiver lá falho.
        
        $fallbackEventId = $this->dlq->event_id ?? ('dlq_' . $this->dlq->id);
        $payload = is_array($this->dlq->payload) ? json_encode($this->dlq->payload) : (string) $this->dlq->payload;
        $headers = is_array($this->dlq->headers) ? json_encode($this->dlq->headers) : (string) ($this->dlq->headers ?? '{}');

        $event = WebhookEvent::firstOrCreate(
            [
                'provider' => $this->dlq->provider,
                'event_id' => $fallbackEventId,
            ],
            [
                'external_reference' => $this->dlq->external_reference,
                'payload' => $payload,
                'headers' => $headers,
                'status' => WebhookEventStatus::RECEIVED,
                'metadata' => [
                    'original_dlq_id' => $this->dlq->id,
                    'is_replay' => true
                ]
            ]
        );

        // Se já foi criado antes, apenas forçamos para RECEIVED novamente
        if (!$event->wasRecentlyCreated && $event->status === WebhookEventStatus::FAILED) {
            $event->status = WebhookEventStatus::RECEIVED;
            $event->attempts = 0;
            $event->metadata = array_merge($event->metadata ?? [], ['original_dlq_id' => $this->dlq->id]);
            $event->save();
        }

        // Despacha o Job na fila de alta prioridade
        ProcessWebhookJob::dispatch($event)->onQueue('high');
    }
}
