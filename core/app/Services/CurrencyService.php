<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    public const string DEFAULT_CURRENCY_CACHE_KEY = 'default_currency';

    public const string ALL_CURRENCIES_CACHE_KEY = 'all_currencies';

    /**
     * Get the default currency code, cached for efficiency.
     */
    public function getDefaultCurrency()
    {
        return Cache::remember(self::DEFAULT_CURRENCY_CACHE_KEY, now()->addDay(), function () {
            try {
                // 1. Try marked as default
                $currency = Currency::where('default', true)->first(['code', 'symbol']);
                
                // 2. Try BRL
                if (!$currency) {
                    $currency = Currency::where('code', 'BRL')->first(['code', 'symbol']);
                }

                // 3. Try USD
                if (!$currency) {
                    $currency = Currency::where('code', 'USD')->first(['code', 'symbol']);
                }

                // 4. Safe visual fallback
                if (!$currency) {
                    \Illuminate\Support\Facades\Log::warning('CRITICAL: No default currency found in database. Using safe fallback array.');
                    return ['code' => 'BRL', 'symbol' => 'R$'];
                }

                return $currency->toArray();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error fetching default currency: ' . $e->getMessage());
                return ['code' => 'BRL', 'symbol' => 'R$'];
            }
        });
    }

    public function exists($currencyCode): bool
    {
        return Currency::where('code', $currencyCode)->exists();
    }

    /**
     * Get a list of all active currencies, cached.
     */
    public function getAllCurrencies()
    {
        return Cache::remember(self::ALL_CURRENCIES_CACHE_KEY, now()->addDay(), function () {
            return Currency::where('status', true)->get();
        });
    }

    public function getCurrencyByCode($code): ?Currency
    {
        $cacheKey = "currency_by_code_{$code}";
        
        $currency = Cache::get($cacheKey);
        
        if ($currency !== null) {
            return $currency;
        }

        $currency = Currency::where('code', $code)->first();

        if ($currency) {
            Cache::put($cacheKey, $currency, now()->addDay());
        } else {
            \Illuminate\Support\Facades\Log::warning("CRITICAL: Currency code '{$code}' requested but not found in database.");
            // Do not cache null for 24 hours, maybe short cache to avoid DB spam during a single request/burst
            Cache::put($cacheKey, null, now()->addSeconds(30));
        }

        return $currency;
    }

    /**
     * Clear cached currency data.
     */
    public function clearCurrencyCache(): void
    {
        Cache::forget(self::DEFAULT_CURRENCY_CACHE_KEY);
        Cache::forget(self::ALL_CURRENCIES_CACHE_KEY);
    }
}
