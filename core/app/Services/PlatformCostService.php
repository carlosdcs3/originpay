<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Cache;

class PlatformCostService
{
    /**
     * Retorna a estimativa de custos operacionais da plataforma.
     * Estes valores podem ser baseados em constantes do arquivo de configuração
     * ou calculados com base no volume (ex: requisições API, Envios de E-mail, PIX Processados).
     */
    public function getEstimatedCosts30Days(): array
    {
        return Cache::remember('platform_costs_30d', 3600, function () {
            
            // Exemplo de custos unitários (podem vir de config/settings)
            $costPerPix = 0.05; // R$ 0,05 por emissão na Efí (ou conforme o PSP)
            $costPerEmail = 0.001; // R$ 0,001 via SES
            $costPerWebhook = 0.0005; // Custo de infraestrutura/AWS Lambda/Redis
            $fixedInfrastructureCost = 500.00; // VPS, DB, Redis, etc
            
            // Quantidades
            $pixCount = Charge::where('created_at', '>=', now()->subDays(30))->count();
            $webhookCount = WebhookDelivery::where('created_at', '>=', now()->subDays(30))->count();
            
            // Cálculos
            $pixCost = $pixCount * $costPerPix;
            $webhookCost = $webhookCount * $costPerWebhook;
            $emailCost = 5000 * $costPerEmail; // Estimativa estática para e-mails (substituir por contagem real no futuro)
            
            $variableCost = $pixCost + $webhookCost + $emailCost;
            $totalCost = $fixedInfrastructureCost + $variableCost;

            return [
                'currency' => 'BRL',
                'fixed_infrastructure' => round($fixedInfrastructureCost, 2),
                'variable_gateways' => round($pixCost, 2),
                'variable_webhooks' => round($webhookCost, 2),
                'variable_emails' => round($emailCost, 2),
                'total_estimated' => round($totalCost, 2),
                'period' => '30_days',
            ];
        });
    }
}
