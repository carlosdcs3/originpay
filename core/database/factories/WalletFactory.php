<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        $balance = 0;

        return [
            'currency_id' => Currency::factory(),
            'user_id' => User::factory(),
            'uuid' => (string) Str::uuid(),
            'balance' => $balance,
            'available_balance' => $balance,
            'pending_balance' => 0,
            'reserved_balance' => 0,
            'withdrawn_balance' => 0,
            'rolling_reserve_balance' => 0,
            'status' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Wallet $wallet) {
            if ((float) $wallet->balance > 0 && (float) $wallet->available_balance === 0.0) {
                $wallet->available_balance = $wallet->balance;
            }
        });
    }
}
