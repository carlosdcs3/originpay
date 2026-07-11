<?php

namespace App\Gateway\Providers;

interface GatewayProviderInterface
{
    public function definition(): GatewayDefinition;
}
