<?php
namespace App\Jobs\Connect\Journey;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Connect\Journey\ConnectJourneyInstance;
use App\Services\Connect\Journey\JourneyRuntimeService;

class ResumeJourneyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $instanceId;
    protected $nodeId;

    public function __construct($instanceId, $nodeId)
    {
        $this->instanceId = $instanceId;
        $this->nodeId = $nodeId;
    }

    public function handle(JourneyRuntimeService $runtime)
    {
        $instance = ConnectJourneyInstance::find($this->instanceId);
        
        // CRITICAL CHECK: Only resume if it is legitimately waiting.
        // If a Goal closed it early, abort.
        if (!$instance || !in_array($instance->status, ['DELAYED', 'RUNNING'])) {
            return;
        }

        // Find edges departing from this delay node and resume stepping
        $graph = $instance->version->graph;
        $edges = collect($graph['edges'])->where('source', $this->nodeId);

        foreach ($edges as $edge) {
            $runtime->step($instance, $edge['target']);
        }
    }
}
