<?php

namespace Database\Factories;

use App\Models\LedgerEntry;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    public function definition(): array
    {
        $wallet = Wallet::factory()->create();

        return [
            'transaction_id' => null,
            'wallet_id' => $wallet->id,
            'direction' => 'credit',
            'amount' => $this->faker->randomFloat(2, 1, 500),
            'currency' => 'BRL',
            'balance_after' => 0,
            'description' => $this->faker->sentence(),
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
