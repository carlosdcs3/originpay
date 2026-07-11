<?php

namespace App\Services;

use App\Models\Dispute;

class DisputeSlaService
{
    public function calculateRemainingHours(Dispute $dispute): int
    {
        if (!$dispute->due_at) {
            return 0;
        }
        
        $remaining = now()->diffInHours($dispute->due_at, false);
        return $remaining < 0 ? 0 : (int) $remaining;
    }

    public function checkAndEscalate(Dispute $dispute, DisputeEventService $eventService)
    {
        // Skip if already terminal
        if (in_array($dispute->status->value, ['won', 'lost', 'closed', 'canceled'])) {
            return;
        }

        $remainingHours = $this->calculateRemainingHours($dispute);

        if ($remainingHours <= 0) {
            $eventService->log($dispute, 'dispute.sla.expired', 'SLA Vencido', 'O prazo de resposta expirou.');
            return;
        }

        if ($remainingHours <= 12) {
            $eventService->log($dispute, 'dispute.sla.critical', 'SLA Crítico', "Faltam menos de 12 horas para o vencimento.");
            return;
        }

        if ($remainingHours <= 36) {
            $eventService->log($dispute, 'dispute.sla.warning', 'SLA em Alerta', "Prazo se esgotando.");
        }
    }
}
