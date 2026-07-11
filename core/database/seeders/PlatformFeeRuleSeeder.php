<?php

namespace Database\Seeders;

use App\Models\PlatformFeeRule;
use Illuminate\Database\Seeder;

class PlatformFeeRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            'pix' => [
                'fixed_fee' => 0.30,
                'percentage_fee' => 1.99,
                'minimum_fee' => 0,
                'maximum_fee' => null,
                'settlement_delay_days' => 1,
                'reserve_percentage' => 0,
            ],
            'card' => [
                'fixed_fee' => 0.49,
                'percentage_fee' => 3.49,
                'minimum_fee' => 0,
                'maximum_fee' => null,
                'settlement_delay_days' => 14,
                'reserve_percentage' => 0,
            ],
            'boleto' => [
                'fixed_fee' => 1.99,
                'percentage_fee' => 0,
                'minimum_fee' => 0,
                'maximum_fee' => null,
                'settlement_delay_days' => 2,
                'reserve_percentage' => 0,
            ],
            'crypto' => [
                'fixed_fee' => 0,
                'percentage_fee' => 1.50,
                'minimum_fee' => 0,
                'maximum_fee' => null,
                'settlement_delay_days' => 1,
                'reserve_percentage' => 0,
            ],
        ];

        foreach ($rules as $method => $values) {
            PlatformFeeRule::firstOrCreate(
                [
                    'scope' => PlatformFeeRule::SCOPE_GLOBAL,
                    'user_id' => null,
                    'payment_method' => $method,
                    'currency' => 'BRL',
                    'status' => PlatformFeeRule::STATUS_ACTIVE,
                ],
                $values + [
                    'starts_at' => now(),
                    'metadata' => ['seeded_by' => self::class],
                ]
            );
        }
    }
}
