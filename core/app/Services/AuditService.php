<?php

namespace App\Services;

use App\Models\FinancialComplianceAudit;
use App\Models\GatewayLog;
use App\Models\KycAuditLog;
use App\Models\PlatformFeeAudit;
use App\Models\WebhookAdminAudit;
use App\Models\WithdrawalAudit;
use App\Models\DxAuditLog;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Registra uma ação de auditoria direcionando para a tabela específica apropriada.
     */
    public function log(string $category, string $action, string $description, array $metadata = [], ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        $ip = request()->ip();

        try {
            switch (strtolower($category)) {
                case 'financeiro':
                case 'compliance':
                    FinancialComplianceAudit::create([
                        'user_id' => $userId,
                        'action' => $action,
                        'description' => $description,
                        'metadata' => $metadata,
                        'ip_address' => $ip,
                    ]);
                    break;

                case 'gateway':
                    GatewayLog::create([
                        'action' => $action,
                        'description' => $description,
                        'payload' => $metadata, // payload in gateway_logs
                        'ip_address' => $ip,
                    ]);
                    break;

                case 'kyc':
                    KycAuditLog::create([
                        'user_id' => $userId,
                        'action' => $action,
                        'notes' => $description,
                        'metadata' => $metadata,
                    ]);
                    break;

                case 'webhook':
                    WebhookAdminAudit::create([
                        'admin_id' => $userId,
                        'action' => $action,
                        'details' => $description,
                        'context' => $metadata,
                        'ip_address' => $ip,
                    ]);
                    break;
                    
                case 'fee':
                case 'taxas':
                    PlatformFeeAudit::create([
                        'admin_id' => $userId,
                        'action' => $action,
                        'description' => $description,
                        'changes' => $metadata,
                    ]);
                    break;
                    
                case 'saque':
                case 'withdrawal':
                    WithdrawalAudit::create([
                        'admin_id' => $userId,
                        'action' => $action,
                        'reason' => $description,
                        'metadata' => $metadata,
                    ]);
                    break;

                default:
                    // Fallback to DX/System audit or standard Laravel log
                    if (class_exists(DxAuditLog::class)) {
                        DxAuditLog::create([
                            'user_id' => $userId,
                            'action' => $action,
                            'description' => $description,
                            'metadata' => $metadata,
                            'ip_address' => $ip,
                        ]);
                    } else {
                        Log::channel('audit')->info("AUDIT [{$category}] {$action}: {$description}", $metadata);
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Failsafe: se a tabela específica não tiver as colunas esperadas ou não existir, loga em arquivo
            Log::channel('audit')->error("Falha ao salvar auditoria de {$category}. Fallback para arquivo.", [
                'action' => $action,
                'description' => $description,
                'metadata' => $metadata,
                'error' => $e->getMessage()
            ]);
        }
    }
}
