<?php

namespace Database\Factories;

use App\Constants\CurrencyType;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'flag' => null,
            'name' => 'Brazilian Real',
            'code' => 'BRL',
            'symbol' => 'R$',
            'type' => CurrencyType::FIAT,
            'exchange_rate' => 1,
            'rate_live' => false,
            'auto_wallet' => false,
            'default' => 1,
            'status' => 1,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Currency $currency) {
            if ($currency->getAttribute('currency_code')) {
                $currency->code = $currency->getAttribute('currency_code');
                unset($currency->currency_code);
            }
        });
    }
}
