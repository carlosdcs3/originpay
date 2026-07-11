<?php
namespace App\Services\Connect\Journey;

use App\Models\Connect\Journey\ConnectJourneyInstance;
use App\Models\Connect\Journey\ConnectJourneyScheduledTask;
use App\Models\Connect\ConnectEventLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JourneyRuntimeService
{
    protected JourneyActionExecutor $executor;
    protected JourneyConditionEvaluator $evaluator;

    public function __construct(JourneyActionExecutor $executor, JourneyConditionEvaluator $evaluator)
    {
        $this->executor = $executor;
        $this->evaluator = $evaluator;
    }

    public function startInstance(ConnectJourneyInstance $instance)
    {
        $graph = $instance->version->graph;
        
        // Find trigger node (source of everything)
        $triggerNode = collect($graph['nodes'])->firstWhere('type', 'trigger');
        if (!$triggerNode) return;

        $this->logEvent($instance, 'JourneyStarted', ['node_id' => $triggerNode['id']]);
        
        $this->step($instance, $triggerNode['id']);
    }

    public function step(ConnectJourneyInstance $instance, string $nodeId)
    {
        if (in_array($instance->status, ['COMPLETED', 'CANCELLED', 'FAILED'])) {
            return;
        }

        $graph = $instance->version->graph;
        $node = collect($graph['nodes'])->firstWhere('id', $nodeId);
        
        if (!$node) {
            // Node not found, end journey
            $this->completeJourney($instance);
            return;
        }

        $instance->update([
            'current_node' => $nodeId,
            'status' => 'RUNNING' // If it was delayed, we are running again
        ]);

        $this->logEvent($instance, 'NodeEntered', ['node_id' => $nodeId, 'type' => $node['type']]);

        // Evaluate Node
        $edgeLabelToFollow = 'default';

        switch ($node['type']) {
            case 'action':
                // Hand off to Executor which plugs into Epic 8 DeliveryService
                $this->executor->execute($instance, $node);
                $this->logEvent($instance, 'ActionExecuted', ['node_id' => $nodeId]);
                break;
                
            case 'delay':
                $this->handleDelay($instance, $node);
                return; // HALT execution here. It will be resumed by cron.
                
            case 'condition':
                $edgeLabelToFollow = $this->evaluator->evaluate($instance, $node) ? 'yes' : 'no';
                $this->logEvent($instance, 'ConditionEvaluated', ['node_id' => $nodeId, 'result' => $edgeLabelToFollow]);
                break;
                
            case 'goal':
                $this->completeJourney($instance);
                return;
        }

        $this->logEvent($instance, 'NodeCompleted', ['node_id' => $nodeId]);

        // Find next edge
        $nextEdges = collect($graph['edges'])
            ->where('source', $nodeId)
            ->where('label', $edgeLabelToFollow);

        // Splitting into multiple paths if they have the same label (Parallel execution)
        foreach ($nextEdges as $edge) {
            // We use a job for the next step to prevent deep recursion locking the worker
            \App\Jobs\Connect\Journey\StepJourneyNodeJob::dispatch($instance->id, $edge['target'])
                ->onQueue('connect_system');
        }
        
        // If no edges out, we are technically at the end of this branch
        if ($nextEdges->isEmpty()) {
            // Check if there are other active branches? In a simple model, if all branches dead end, complete journey.
            // For now, let's mark complete if no out edge and not a delay.
            $this->completeJourney($instance);
        }
    }

    protected function handleDelay(ConnectJourneyInstance $instance, array $node)
    {
        $instance->update(['status' => 'DELAYED']);
        
        // Example data: { "minutes": 1440 } or { "timestamp": "2026-10-01..." }
        $resumeAt = now()->addMinutes($node['data']['minutes'] ?? 60);

        ConnectJourneyScheduledTask::create([
            'instance_id' => $instance->id,
            'node_id' => $node['id'],
            'resume_at' => $resumeAt,
            'status' => 'PENDING'
        ]);

        $this->logEvent($instance, 'DelayStarted', ['node_id' => $node['id'], 'resume_at' => $resumeAt]);
    }

    public function completeJourney(ConnectJourneyInstance $instance)
    {
        $instance->update([
            'status' => 'COMPLETED',
            'finished_at' => now(),
            'current_node' => null
        ]);
        
        $this->logEvent($instance, 'JourneyCompleted', []);
    }

    protected function logEvent(ConnectJourneyInstance $instance, string $type, array $payload)
    {
        ConnectEventLog::create([
            'uuid' => Str::uuid()->toString(),
            'merchant_id' => $instance->merchant_id,
            'aggregate_type' => 'JourneyInstance',
            'aggregate_id' => $instance->id,
            'event_type' => $type,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
