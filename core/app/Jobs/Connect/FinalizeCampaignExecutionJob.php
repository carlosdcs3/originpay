<?php
namespace App\Jobs\Connect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Connect\ConnectCampaignExecution;
use App\Models\Connect\ConnectCampaignRecipient;
use App\Models\Connect\ConnectCampaignDeliveryAttempt;
use App\Models\Connect\Campaign;
use App\Events\Connect\Campaign\CampaignExecutionCompleted;
use App\Services\Connect\Campaign\CampaignStateMachine;

class FinalizeCampaignExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $executionId;

    public function __construct($executionId)
    {
        $this->executionId = $executionId;
    }

    public function handle(CampaignStateMachine $stateMachine)
    {
        DB::transaction(function () use ($stateMachine) {
            $execution = ConnectCampaignExecution::where('id', $this->executionId)->lockForUpdate()->firstOrFail();

            if ($execution->status === ConnectCampaignExecution::STATUS_COMPLETED) {
                return;
            }

            // DB source of truth counting
            $pendingCount = ConnectCampaignRecipient::where('execution_id', $this->executionId)->whereIn('status', [
                ConnectCampaignRecipient::STATUS_PENDING,
                ConnectCampaignRecipient::STATUS_QUEUED,
                ConnectCampaignRecipient::STATUS_PROCESSING
            ])->count();

            if ($pendingCount > 0) {
                // Not done yet. Abort.
                return;
            }

            $processedCount = ConnectCampaignRecipient::where('execution_id', $this->executionId)->where('status', ConnectCampaignRecipient::STATUS_PROCESSED)->count();
            $failedCount = ConnectCampaignRecipient::where('execution_id', $this->executionId)->where('status', ConnectCampaignRecipient::STATUS_FAILED)->count();

            // Observability calculations
            $avgLatency = ConnectCampaignDeliveryAttempt::where('execution_id', $this->executionId)->avg('latency_ms') ?? 0;
            
            $executionTimeSeconds = $execution->started_at ? now()->diffInSeconds($execution->started_at) : 1;
            $throughputPerSecond = ($processedCount + $failedCount) / max($executionTimeSeconds, 1);

            $metadata = $execution->metadata ?? [];
            $metadata['observability'] = [
                'average_latency_ms' => round($avgLatency, 2),
                'execution_time_seconds' => $executionTimeSeconds,
                'throughput_per_second' => round($throughputPerSecond, 2)
            ];

            $execution->update([
                'status' => ConnectCampaignExecution::STATUS_COMPLETED,
                'finished_at' => now(),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'queued_count' => 0,
                'metadata' => $metadata
            ]);

            $campaign = $execution->campaign;
            $stateMachine->transitionTo($campaign, Campaign::STATUS_COMPLETED);
            $campaign->save();

            event(new CampaignExecutionCompleted($execution));
        });
    }
}
