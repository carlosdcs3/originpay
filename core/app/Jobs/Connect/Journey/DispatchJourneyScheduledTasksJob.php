<?php
namespace App\Jobs\Connect\Journey;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Connect\Journey\ConnectJourneyScheduledTask;

class DispatchJourneyScheduledTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        ConnectJourneyScheduledTask::where('resume_at', '<=', now())
            ->where('status', 'PENDING')
            ->chunkById(1000, function ($tasks) {
                foreach ($tasks as $task) {
                    $task->update(['status' => 'PROCESSED']);
                    ResumeJourneyJob::dispatch($task->instance_id, $task->node_id)->onQueue('connect_system');
                }
            });
    }
}
