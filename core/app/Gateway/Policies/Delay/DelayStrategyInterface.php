<?php

namespace App\Gateway\Policies\Delay;

interface DelayStrategyInterface
{
    /**
     * Pausa a execucao pelo numero de milisegundos
     */
    public function delay(int $milliseconds): void;
}
