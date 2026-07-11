<?php

namespace Database\Factories;

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Models\Charge;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Charge>
 */
class ChargeFactory extends Factory
{
    protected $model = Charge::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10, 1000);
        $platformFee = 0;
        $gatewayFee = 0;

        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => 'charge_' . Str::uuid()->toString(),
            'charge_id' => 'ch_' . Str::lower(Str::random(24)),
            'merchant_id' => Merchant::factory(),
            'user_id' => User::factory(),
            'gateway_id' => 'mock',
            'gateway_charge_id' => 'gw_' . Str::random(16),
            'gateway_reference' => null,
            'payment_method' => PaymentMethod::PIX,
            'amount' => $amount,
            'currency' => 'BRL',
            'platform_fee' => $platformFee,
            'gateway_fee' => $gatewayFee,
            'net_amount' => $amount - $platformFee - $gatewayFee,
            'description' => 'Test charge',
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_document' => '12345678909',
            'expires_at' => now()->addMinutes(30),
            'paid_at' => null,
            'boleto_url' => null,
            'boleto_pdf_url' => null,
            'barcode' => null,
            'digitable_line' => null,
            'status' => ChargeStatus::WAITING_PAYMENT,
            'metadata' => [],
            'environment' => 'sandbox',
        ];
    }
}
