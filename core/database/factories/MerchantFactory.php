<?php

namespace Database\Factories;

use App\Enums\EnvironmentMode;
use App\Enums\MerchantStatus;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'site_url' => 'https://' . fake()->unique()->domainName(),
            'currency_id' => Currency::factory(),
            'business_logo' => null,
            'business_description' => fake()->sentence(),
            'business_email' => fake()->unique()->companyEmail(),
            'fee' => 0,
            'status' => MerchantStatus::APPROVED,
            'merchant_key' => 'mrc_' . Str::lower(Str::random(24)),
            'api_key' => 'live_pk_' . Str::random(32),
            'api_secret' => 'live_sk_' . Str::random(32),
            'test_api_key' => 'test_pk_' . Str::random(32),
            'test_api_secret' => 'test_sk_' . Str::random(32),
            'test_merchant_key' => 'test_mrc_' . Str::lower(Str::random(16)),
            'current_mode' => EnvironmentMode::SANDBOX,
            'sandbox_enabled' => true,
            'webhook_url' => fake()->url(),
        ];
    }
}
