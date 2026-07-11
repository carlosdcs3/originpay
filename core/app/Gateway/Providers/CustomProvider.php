<?php

namespace App\Gateway\Providers;

class CustomProvider implements GatewayProviderInterface
{
    public function definition(): GatewayDefinition
    {
        return new GatewayDefinition([
            'name' => 'Gateway Personalizado',
            'code' => 'custom',
            'adapter' => 'CustomGatewayAdapter',
            'logo' => 'assets/providers/custom.svg',
            'description' => 'Crie um gateway do zero. Você precisará configurar métodos e credenciais manualmente.',
            'operations' => [],
            'credentials' => [],
            'withdraw_fields' => []
        ]);
    }
}
