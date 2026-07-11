<?php

namespace Database\Factories;

use App\Enums\MethodType;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10, 1000);

        return [
            'user_id' => User::factory(),
            'trx_id' => 'TXN' . Str::upper(Str::random(12)),
            'trx_type' => TrxType::DEPOSIT,
            'provider' => 'system',
            'processing_type' => MethodType::SYSTEM,
            'amount' => $amount,
            'fee' => 0,
            'currency' => 'BRL',
            'net_amount' => $amount,
            'status' => TrxStatus::PENDING,
        ];
    }
}
