<?php

namespace App\Services;

use App\Models\Dispute;
use App\Enums\DisputeStatus;
use Illuminate\Support\Facades\DB;
use Exception;

class DisputeWorkflowService
{
    public function __construct(
        protected DisputeStateMachine $stateMachine,
        protected DisputeEventService $eventService
    ) {}

    public function close(Dispute $dispute, DisputeStatus $result, string $reason = null): void
    {
        DB::transaction(function () use ($dispute, $result, $reason) {
            $lockedDispute = Dispute::where('id', $dispute->id)->lockForUpdate()->first();
            
            $this->stateMachine->assertCanTransition($lockedDispute, $result);

            $oldStatus = $lockedDispute->status->value;
            
            $lockedDispute->update([
                'status' => $result,
                'resolved_at' => now(),
                'reason' => $reason,
            ]);

            $this->eventService->log($lockedDispute, 'dispute.closed', 'Disputa Encerrada', "Resultado: {$result->label()}. Motivo: " . ($reason ?? 'Não informado'), [
                'old_status' => $oldStatus,
                'new_status' => $result->value,
                'is_terminal' => true
            ]);
        });
    }

    public function transitionTo(Dispute $dispute, DisputeStatus $newStatus, string $eventType, string $title, string $description = null): void
    {
        DB::transaction(function () use ($dispute, $newStatus, $eventType, $title, $description) {
            $lockedDispute = Dispute::where('id', $dispute->id)->lockForUpdate()->first();
            
            $this->stateMachine->assertCanTransition($lockedDispute, $newStatus);

            $oldStatus = $lockedDispute->status->value;

            $lockedDispute->update(['status' => $newStatus]);

            $this->eventService->log($lockedDispute, $eventType, $title, $description, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus->value
            ]);
        });
    }
}
