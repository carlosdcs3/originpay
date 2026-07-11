<?php

namespace App\Console\Commands;

use App\Models\Charge;
use App\Enums\ChargeStatus;
use App\Services\ChargeService;
use App\Gateway\GatewayManager;
use App\Models\PaymentGateway;
use App\Models\ReconciliationHistory;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Facades\Log;

class GatewayReconcileCommand extends Command
{
    protected $signature = 'gateway:reconcile {--hours=24 : Horas para retroceder na busca de pendentes}';
    protected $description = 'Reconcilia cobranças pendentes com os adquirentes (PSP)';

    public function handle(ChargeService $chargeService)
    {
        $hours = (int) $this->option('hours');
        $startTime = microtime(true);
        
        $this->info("Iniciando reconciliação de cobranças pendentes (últimas {$hours}h)...");

        $charges = Charge::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereIn('status', [ChargeStatus::PENDING, ChargeStatus::WAITING_PAYMENT])
            ->where('created_at', '>=', now()->subHours($hours))
            ->where('created_at', '<=', now()->subMinutes(5))
            ->whereNotNull('gateway_charge_id')
            ->get();

        $this->info("Encontradas {$charges->count()} cobranças PENDING elegíveis.");

        if ($charges->isEmpty()) {
            $this->info("Nenhuma cobrança pendente elegível encontrada.");
            return 0;
        }

        // Agrupar por gateway para registrar no ReconciliationHistory
        $chargesByGateway = $charges->groupBy('gateway_id');

        foreach ($chargesByGateway as $gatewayId => $gatewayCharges) {
            $gatewayModel = PaymentGateway::find($gatewayId);
            if (!$gatewayModel) continue;

            $processed = 0;
            $divergences = 0;
            $divergencesDetails = [];
            $gatewayCode = $gatewayModel->code;
            $hasError = false;
            $errorMessage = null;

            $gatewayStartTime = microtime(true);
            $adapter = GatewayManager::adapter($gatewayModel);

            $this->withProgressBar($gatewayCharges, function (Charge $charge) use ($chargeService, $adapter, &$processed, &$divergences, &$divergencesDetails, &$hasError, &$errorMessage) {
                try {
                    $statusData = $adapter->getCharge($charge->gateway_charge_id);
                    $processed++;

                    if ($statusData['status'] === 'paid') {
                        $this->newLine();
                        $this->warn("Divergência: Cobrança {$charge->uuid} está PAGA no PSP. Corrigindo...");
                        
                        $eventId = 'reconcile_' . now()->timestamp . '_' . $charge->id;
                        $chargeService->markAsPaid($charge, $eventId);
                        
                        $divergences++;
                        $divergencesDetails[] = $charge->uuid;
                        Log::channel('gateway')->info("Reconciliação: Cobrança {$charge->uuid} marcada como paga.");
                    }
                } catch (Exception $e) {
                    $this->newLine();
                    $this->error("Erro ao reconciliar cobrança {$charge->uuid}: " . $e->getMessage());
                    Log::channel('gateway')->error("Reconciliação Falha [{$charge->uuid}]: " . $e->getMessage());
                    $hasError = true;
                    $errorMessage = substr($e->getMessage(), 0, 500);
                }
            });

            $gatewayDurationMs = (int) round((microtime(true) - $gatewayStartTime) * 1000);

            ReconciliationHistory::create([
                'gateway_code' => $gatewayCode,
                'processed_count' => $processed,
                'divergences_count' => $divergences,
                'duration_ms' => $gatewayDurationMs,
                'status' => $hasError ? 'failed' : 'success',
                'error_message' => $errorMessage,
                'divergences_details' => $divergencesDetails,
            ]);
        }

        $totalDuration = round((microtime(true) - $startTime) * 1000);
        $this->newLine();
        $this->info("Reconciliação finalizada em {$totalDuration}ms.");
        return 0;
    }
}
