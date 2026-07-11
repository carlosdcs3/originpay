<?php

namespace App\Services\DisasterRecovery;

use Illuminate\Support\Facades\Cache;
use App\Models\WebhookAdminAudit;
use Illuminate\Support\Facades\Log;

class EmergencyCircuitBreaker
{
    /**
     * Define o estado de um Kill Switch
     */
    public function setSwitch(string $switchName, bool $active, int $adminId, string $reason = ''): void
    {
        // Remember forever
        if ($active) {
            Cache::forever($switchName, true);
        } else {
            Cache::forget($switchName);
        }

        $action = $active ? 'enabled_kill_switch' : 'disabled_kill_switch';

        WebhookAdminAudit::create([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => 'kill_switch',
            'target_id' => 0,
            'reason' => "Switch: {$switchName}. Reason: {$reason}",
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);

        Log::channel('gateway')->emergency("EMERGENCY KILL SWITCH TRIGGERED: {$action} on {$switchName} by Admin ID {$adminId}.");
    }

    /**
     * Retorna se um Kill Switch está ATIVO (bloqueando operações)
     */
    public function isSwitchActive(string $switchName): bool
    {
        return Cache::get($switchName, false) === true;
    }

    /**
     * Retorna o status de todos os switches suportados
     */
    public function getAllStatuses(): array
    {
        $switches = [
            'kill_switch:new_provider',
            'kill_switch:withdraw',
            'kill_switch:refund',
            'kill_switch:webhook_processing',
            'kill_switch:read_only_mode',
            'kill_switch:financial_maintenance',
        ];

        $status = [];
        foreach ($switches as $switch) {
            $status[$switch] = $this->isSwitchActive($switch);
        }

        return $status;
    }
}
