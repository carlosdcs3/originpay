<?php
namespace App\Jobs\Connect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Connect\ConnectCampaignExecution;
use App\Models\Connect\ConnectCampaignRecipient;
use App\Services\Connect\CampaignService;
use App\Services\Connect\Campaign\CampaignAudienceResolver;
use App\Events\Connect\Campaign\CampaignExecutionStarted;
use App\Events\Connect\Campaign\CampaignAudienceResolved;
use App\Events\Connect\Campaign\CampaignRecipientsQueued;
use Exception;

class PrepareCampaignExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(CampaignService $campaignService, CampaignAudienceResolver $resolver)
    {
        DB::beginTransaction();

        try {
            $campaign = $campaignService->lockAndPrepareExecution($this->campaignId);
            $executionUuid = $campaign->execution_uuid;

            $execution = ConnectCampaignExecution::create([
                'uuid' => $executionUuid,
                'campaign_id' => $campaign->id,
                'merchant_id' => $campaign->merchant_id,
                'status' => ConnectCampaignExecution::STATUS_PROCESSING,
                'started_at' => now(),
            ]);

            event(new CampaignExecutionStarted($execution));

            // Chunk insert recipients
            $chunkSize = 1000;
            $contactsLazy = $resolver->getChunked($campaign->segment, $chunkSize);
            $totalContacts = 0;

            foreach ($contactsLazy->chunk($chunkSize) as $contactsChunk) {
                $inserts = [];
                $now = now();
                
                foreach ($contactsChunk as $contact) {
                    $inserts[] = [
                        'uuid' => Str::uuid()->toString(),
                        'execution_id' => $execution->id,
                        'campaign_id' => $campaign->id,
                        'merchant_id' => $campaign->merchant_id,
                        'contact_id' => $contact->id,
                        'channel' => $campaign->channel,
                        'status' => ConnectCampaignRecipient::STATUS_QUEUED,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $totalContacts++;
                }

                // insertOrIgnore prevents PK duplicates on accidental job retry
                ConnectCampaignRecipient::insertOrIgnore($inserts);
            }

            event(new CampaignAudienceResolved($execution));

            // Update stats from DB absolute truth
            $trueCount = ConnectCampaignRecipient::where('execution_id', $execution->id)->count();
            
            $execution->update([
                'total_audience' => $trueCount,
                'queued_count' => $trueCount
            ]);

            $campaign->update([
                'estimated_audience_count' => $trueCount,
                'status' => \App\Models\Connect\Campaign::STATUS_QUEUEING
            ]);

            event(new CampaignRecipientsQueued($execution));

            DB::commit();
            
            // Priority Queues isolated by Channel
            $queueName = 'connect_' . $campaign->channel;

            // Dispatch Individual Jobs
            ConnectCampaignRecipient::where('execution_id', $execution->id)
                ->where('status', ConnectCampaignRecipient::STATUS_QUEUED)
                ->select('id')
                ->chunkById(1000, function ($recipients) use ($queueName) {
                    foreach ($recipients as $rec) {
                        ProcessCampaignRecipientJob::dispatch($rec->id)->onQueue($queueName);
                    }
                });

            // Start Finalizer just in case everything is ultra fast or empty
            FinalizeCampaignExecutionJob::dispatch($execution->id)->onQueue('connect_system');

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
