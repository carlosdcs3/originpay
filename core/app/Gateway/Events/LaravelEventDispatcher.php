<?php

namespace App\Gateway\Events;

use Illuminate\Contracts\Events\Dispatcher;

class LaravelEventDispatcher implements GatewayEventDispatcherInterface
{
    public function __construct(protected Dispatcher $dispatcher) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
