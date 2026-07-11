<?php

namespace App\Gateway\Policies\Delay;

class BlockingDelayStrategy implements DelayStrategyInterface
{
    public function delay(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000); // converte para microsegundos
        }
    }
}
