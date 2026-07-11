<?php
namespace App\Services\Connect;

use App\Models\Connect\Campaign;
use App\Models\Connect\Template;
use App\Models\Connect\ConnectSegment;
use App\Services\Connect\Campaign\CampaignStateMachine;
use App\Services\Connect\Campaign\CampaignValidator;
use App\Services\Connect\Campaign\CampaignAudienceResolver;
use App\Events\Connect\Campaign\CampaignCreated;
use App\Events\Connect\Campaign\CampaignScheduled;
use App\Events\Connect\Campaign\CampaignStarted;
use App\Events\Connect\Campaign\CampaignCancelled;
use Illuminate\Support\Str;

class CampaignService
{
    protected $stateMachine;
    protected $validator;
    protected $audienceResolver;

    public function __construct(CampaignStateMachine $stateMachine, CampaignValidator $validator, CampaignAudienceResolver $audienceResolver)
    {
        $this->stateMachine = $stateMachine;
        $this->validator = $validator;
        $this->audienceResolver = $audienceResolver;
    }

    public function createCampaign(array $data, $merchantId, $userId)
    {
        $campaign = Campaign::create([
            'uuid' => Str::uuid()->toString(), // Exists from initial setup
            'merchant_id' => $merchantId,
            'segment_id' => $data['segment_id'],
            'template_id' => $data['template_id'],
            'channel' => $data['channel'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'campaign_version' => 1,
            'status' => Campaign::STATUS_DRAFT,
            'created_by' => $userId,
        ]);

        event(new CampaignCreated($campaign));
        return $campaign;
    }

    public function scheduleCampaign(Campaign $campaign, $scheduledAt, $userId)
    {
        $template = $campaign->template;
        $segment = $campaign->segment;
        
        $this->validator->validateForExecution($template, $segment, $campaign->channel);
        $this->stateMachine->transitionTo($campaign, Campaign::STATUS_SCHEDULED);
        
        // Estimate audience count
        $estimatedCount = $this->audienceResolver->getQuery($segment)->count();

        // Calculate Checksum of AST
        $astJson = is_string($template->content) ? $template->content : json_encode($template->content);
        $checksum = hash('sha256', $astJson);

        // Take Snapshot & set Correlation ID for this execution
        $campaign->update([
            'scheduled_at' => $scheduledAt,
            'updated_by' => $userId,
            'execution_uuid' => Str::uuid()->toString(),
            'estimated_audience_count' => $estimatedCount,
            'snapshot_template_id' => $template->id,
            'snapshot_template_version' => $template->version,
            'snapshot_segment_id' => $segment->id,
            'snapshot_segment_version' => 1, // Assume segment rules don't have integer versioning yet
            'metadata' => [
                'snapshot' => [
                    'template_id' => $template->id,
                    'template_version' => $template->version,
                    'template_ast' => $template->content,
                    'compiled_template_checksum' => $checksum,
                    'segment_id' => $segment->id,
                    'segment_version' => 1,
                    'segment_rules' => $segment->rules,
                    'channel' => $campaign->channel
                ]
            ]
        ]);

        event(new CampaignScheduled($campaign));
    }

    public function lockAndPrepareExecution($campaignId)
    {
        // Pessimistic Lock to avoid Double Execution by multiple Workers
        $campaign = Campaign::where('id', $campaignId)->lockForUpdate()->firstOrFail();
        
        $this->stateMachine->transitionTo($campaign, Campaign::STATUS_PREPARING);
        $campaign->save();
        
        return $campaign;
    }

    public function startCampaign(Campaign $campaign)
    {
        $this->stateMachine->transitionTo($campaign, Campaign::STATUS_RUNNING);
        $campaign->update(['started_at' => now()]);
        event(new CampaignStarted($campaign));
    }

    public function cancelCampaign(Campaign $campaign, $userId)
    {
        $this->stateMachine->transitionTo($campaign, Campaign::STATUS_CANCELLED);
        $campaign->update([
            'cancelled_at' => now(),
            'updated_by' => $userId
        ]);
        event(new CampaignCancelled($campaign));
    }
}
