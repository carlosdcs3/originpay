<?php

namespace App\Services\Webhook\Handlers;

use App\DTOs\Gateway\GatewayWebhookData;
use App\Models\WebhookEvent;

interface WebhookHandlerInterface
{
    /**
     * Retorna se este handler suporta o evento.
     */
    public function supports(string $entityType): bool;

    /**
     * Processa a logica de negócio.
     */
    public function handle(GatewayWebhookData $data, WebhookEvent $event): void;
}
