<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Gateway\GatewayManager;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicStatusController extends Controller
{
    /**
     * Retorna a saúde da plataforma para a página pública status.originpay.com
     * Cache de 60s para evitar DDoS ou sobrecarga de DB/APIs de terceiros.
     */
    public function getStatus(): JsonResponse
    {
        $statusData = Cache::remember('public_status_page', 60, function () {
            
            $services = [
                'api' => ['status' => 'operational', 'name' => 'API REST'],
                'webhooks' => ['status' => 'operational', 'name' => 'Webhooks Delivery'],
                'dashboard' => ['status' => 'operational', 'name' => 'Dashboard'],
            ];

            // Avaliar saúde dos Gateways Ativos
            $activeGateways = PaymentGateway::where('status', 1)->get();
            $gatewayStatus = 'operational';
            
            foreach ($activeGateways as $gw) {
                try {
                    $adapter = GatewayManager::adapter($gw);
                    $health = $adapter->healthCheck();
                    
                    if (!$health->isOnline) {
                        $gatewayStatus = 'degraded_performance';
                        // Apenas para efeito de status público, registramos degradação, mas não falha total
                    }
                } catch (\Exception $e) {
                    $gatewayStatus = 'partial_outage';
                }
            }
            
            $services['pix_processing'] = [
                'status' => $gatewayStatus,
                'name' => 'Processamento PIX'
            ];

            // Analisar se há atraso na DLQ que indique falha de webhooks
            $dlqCount = \App\Models\WebhookDeadLetter::where('status', 'pending')
                            ->where('created_at', '>=', now()->subHours(1))
                            ->count();
                            
            if ($dlqCount > 50) {
                $services['webhooks']['status'] = 'degraded_performance';
            }

            // Verifica janela de manutenção ativa
            $activeMaintenance = \App\Models\MaintenanceWindow::where('status', 'in_progress')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->first();

            if ($activeMaintenance) {
                $affected = $activeMaintenance->affected_services ?? [];
                foreach ($affected as $svc) {
                    if (isset($services[$svc])) {
                        $services[$svc]['status'] = 'under_maintenance';
                    }
                }
            }

            // Calcula status global baseado nos componentes
            $globalStatus = 'operational';
            if ($activeMaintenance) {
                $globalStatus = 'under_maintenance';
            } else {
                foreach ($services as $service) {
                    if ($service['status'] === 'partial_outage') $globalStatus = 'partial_outage';
                    elseif ($service['status'] === 'degraded_performance' && $globalStatus !== 'partial_outage') $globalStatus = 'degraded_performance';
                }
            }

            return [
                'global_status' => $globalStatus,
                'maintenance_window' => $activeMaintenance ? [
                    'title' => $activeMaintenance->title,
                    'description' => $activeMaintenance->description,
                    'ends_at' => $activeMaintenance->ends_at->toIso8601String(),
                ] : null,
                'services' => array_values($services),
                'last_updated' => now()->toIso8601String(),
            ];
        });

        return response()->json($statusData);
    }
}
