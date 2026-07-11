<?php

namespace App\Gateway\Events;

interface GatewayEventDispatcherInterface
{
    public function dispatch(object $event): void;
}
