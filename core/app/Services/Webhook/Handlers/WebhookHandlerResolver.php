<?php

namespace App\Services\Webhook\Handlers;

use Exception;

class WebhookHandlerResolver
{
    /**
     * @var WebhookHandlerInterface[]
     */
    protected array $handlers;

    public function __construct(
        ChargeWebhookHandler $chargeHandler
        // Injetar os outros aqui depois...
    ) {
        $this->handlers = [
            $chargeHandler
        ];
    }

    public function resolve(string $entityType): WebhookHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($entityType)) {
                return $handler;
            }
        }

        throw new Exception("No handler found for entity type: {$entityType}");
    }
}
