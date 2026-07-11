<?php

namespace App\Services;

use App\Models\PaymentGateway;
use App\Models\Charge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Enums\ChargeStatus;

class PlatformIntelligenceService
{
    /**
     * Extrai insights operacionais agregados para a Dashboard.
     */
    public function getInsights(): array
    {
        return Cache::remember('platform_intelligence_insights', 300, function () {
            $insights = [];

            // 1. Gateway mais utilizado
            $mostUsedGateway = Charge::select('gateway_id', DB::raw('count(*) as total'))
                ->whereNotNull('gateway_id')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('gateway_id')
                ->orderByDesc('total')
                ->first();
                
            if ($mostUsedGateway && $mostUsedGateway->gateway_id) {
                $gw = PaymentGateway::find($mostUsedGateway->gateway_id);
                if ($gw) {
                    $insights['most_used_gateway'] = [
                        'name' => $gw->name,
                        'code' => $gw->code,
                        'volume_7d' => $mostUsedGateway->total
                    ];
                }
            }

            // 2. Anomalia de falhas (Taxa de Failed/Cancelled alta)
            $totalCharges24h = Charge::where('created_at', '>=', now()->subHours(24))->count();
            $failedCharges24h = Charge::where('created_at', '>=', now()->subHours(24))
                ->whereIn('status', [ChargeStatus::FAILED, ChargeStatus::CANCELLED, ChargeStatus::REJECTED])
                ->count();
                
            $failRate = $totalCharges24h > 0 ? round(($failedCharges24h / $totalCharges24h) * 100, 2) : 0;
            
            if ($failRate > 15) { // Arbitrary threshold for anomaly
                $insights['anomaly_detected'] = true;
                $insights['fail_rate_anomaly'] = $failRate;
            } else {
                $insights['anomaly_detected'] = false;
            }

            // 3. Pico de acessos API
            $apiRpm = app(ApiMetricsService::class)->getRpmSummary();
            $insights['api_rpm_24h'] = $apiRpm['rpm_avg_24h'];

            // 4. DLQ Status
            $dlqSummary = app(DlqMonitorService::class)->getStatusSummary();
            $pendingDlq = $dlqSummary['pending'] ?? 0;
            
            if ($pendingDlq > 100) {
                $insights['dlq_warning'] = true;
                $insights['dlq_pending_count'] = $pendingDlq;
            }

            return $insights;
        });
    }
}
