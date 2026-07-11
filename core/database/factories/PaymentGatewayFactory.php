<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    public function definition(): array
    {
        $code = 'gateway_' . Str::lower(Str::random(8));

        return [
            'provider' => 'custom',
            'adapter' => 'CustomGatewayAdapter',
            'logo' => null,
            'name' => 'Test Gateway',
            'code' => $code,
            'currencies' => ['BRL'],
            'credentials' => [],
            'is_withdraw' => 'enabled',
            'status' => 1,
            'is_maintenance' => false,
            'priority' => 999,
            'is_sandbox' => true,
            'supports_pix' => true,
            'supports_card' => false,
            'supports_boleto' => false,
            'supports_crypto' => false,
            'supports_refund' => false,
            'supports_withdrawal' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PaymentGateway $gateway) {
            $operations = $gateway->getAttribute('operations');

            if (is_array($operations)) {
                $gateway->supports_pix = in_array('PIX_CHARGE', $operations, true);
                $gateway->supports_withdrawal = in_array('PIX_WITHDRAW', $operations, true);
                unset($gateway->operations);
            }
        });
    }
}
