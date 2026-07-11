<?php

namespace App\Services;

use App\Models\PaymentGateway;
use App\Models\WalletBalance;
use Illuminate\Support\Facades\Cache;

class GatewayHealthScoreService
{
    /**
     * Calculates the health score for a given gateway based on weighted penalties.
     * The score is always bounded between 0 and 100.
     */
    public function calculateScore(PaymentGateway $gateway): int
    {
        $score = 100;

        // 1. Gateway Offline (-40 points)
        if (!$gateway->status) {
            $score -= 40;
        }

        // 2. Sem operações (-5 points)
        if (empty($gateway->operations)) {
            $score -= 5;
        }

        // 3. Saldo Negativo (-50 points)
        $hasNegativeBalance = WalletBalance::where('gateway_id', $gateway->id)
            ->where(function ($query) {
                $query->where('available', '<', 0)
                      ->orWhere('pending', '<', 0)
                      ->orWhere('blocked', '<', 0);
            })->exists();

        if ($hasNegativeBalance) {
            $score -= 50;
        }

        // 4. Ledger Divergente (-30 points)
        // Here we could cross-check if there are divergences. We can use an event or cache for this.
        // For now, if there is a known divergence flagged in Cache:
        if (Cache::has("gateway:{$gateway->id}:divergence")) {
            $score -= 30;
        }

        // 5. Webhook Parado (-20 points)
        if (Cache::has("gateway:{$gateway->id}:webhook_stalled")) {
            $score -= 20;
        }

        // 6. Fila Congestionada (-10 points)
        if (Cache::has("gateway:{$gateway->id}:queue_congested")) {
            $score -= 10;
        }

        // Boundaries
        return max(0, min(100, $score));
    }
}

