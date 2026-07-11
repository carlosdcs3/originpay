<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = [
            [
                'name' => 'Real Brasileiro',
                'code' => 'BRL',
                'symbol' => 'R$',
                'exchange_rate' => 1.00000000,
                'type' => 'fiat',
                'status' => '1',
                'default' => '1',
            ],
            [
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 0.20000000,
                'type' => 'fiat',
                'status' => '1',
                'default' => '0',
            ]
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
