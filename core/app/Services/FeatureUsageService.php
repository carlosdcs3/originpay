<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class FeatureUsageService
{
    /**
     * Incrementa o uso de uma feature na plataforma.
     * Utiliza o Redis para contabilização atômica sem sobrecarregar o DB.
     * 
     * Exemplos de features: 'checkout_api', 'checkout_link', 'dashboard_login', 'api_key_created'
     */
    public function logUsage(string $featureKey, int $value = 1): void
    {
        $windows = [
            '1h' => now()->addHour(),
            '24h' => now()->addHours(24),
            '7d' => now()->addDays(7),
        ];

        foreach ($windows as $suffix => $ttl) {
            $cacheKey = "feature_usage:{$featureKey}:{$suffix}";
            
            if (Cache::has($cacheKey)) {
                Cache::increment($cacheKey, $value);
            } else {
                Cache::put($cacheKey, $value, $ttl);
            }
        }
    }

    /**
     * Retorna as estatísticas de uso das principais features nos últimos 7 dias.
     */
    public function getSummary(): array
    {
        $featuresToTrack = [
            'checkout_api',
            'checkout_link',
            'split_payment',
            'withdrawal_request',
            'webhook_dispatched',
            'sandbox_mode_used'
        ];
        
        $summary = [];
        
        foreach ($featuresToTrack as $feature) {
            $summary[$feature] = [
                '1h' => Cache::get("feature_usage:{$feature}:1h", 0),
                '24h' => Cache::get("feature_usage:{$feature}:24h", 0),
                '7d' => Cache::get("feature_usage:{$feature}:7d", 0),
            ];
        }
        
        return $summary;
    }
}
