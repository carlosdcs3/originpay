<?php
namespace App\Jobs\Connect\Journey;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Connect\Journey\ConnectJourneyInstance;
use App\Services\Connect\Journey\JourneyRuntimeService;

class StepJourneyNodeJob implements ShouldQueue
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
        if ($instance) {
            $runtime->step($instance, $this->nodeId);
        }
    }
}
