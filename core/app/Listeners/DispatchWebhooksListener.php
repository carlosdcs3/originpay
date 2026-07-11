<?php

namespace App\Listeners;

use App\Events\ChargePaidEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Log;

class DispatchWebhooksListener implements ShouldQueue
{
    use InteractsWithQueue;

    // Horizon/Queue configuration
    public $tries = 3;
    public $backoff = [30, 60, 120];
    
    public function handle(ChargePaidEvent $event): void
    {
        try {
            $charge = $event->charge;
            
            if ($charge->user_id) {
                // Instancia o despachante e envia o evento
                $dispatcher = app(WebhookDispatcher::class);
                $dispatcher->dispatch($charge->user_id, 'charge.paid', $charge->toArray());
            }
        } catch (\Exception $e) {
            Log::error("Erro ao despachar webhook para a cobrança {$event->charge->uuid}: " . $e->getMessage());
            $this->release(30); // Tenta novamente em 30s
        }
    }
}
