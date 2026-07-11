<?php
namespace App\Services\Connect\Journey;

use App\Models\Connect\Journey\ConnectJourney;
use App\Models\Connect\Journey\ConnectJourneyInstance;
use App\Models\Connect\Journey\ConnectJourneyTriggerLock;
use Illuminate\Support\Str;

class JourneyTriggerService
{
    protected JourneyRuntimeService $runtime;

    public function __construct(JourneyRuntimeService $runtime)
    {
        $this->runtime = $runtime;
    }

    public function processEvent(int $merchantId, int $contactId, string $eventType, string $eventId)
    {
        // Find Published Journeys matching this trigger
        $journeys = ConnectJourney::where('merchant_id', $merchantId)
            ->where('status', 'PUBLISHED')
            ->get();
            
        foreach ($journeys as $journey) {
            $version = \App\Models\Connect\Journey\ConnectJourneyVersion::where('journey_id', $journey->id)
                ->where('version', $journey->version)->first();
                
            if (!$version) continue;

            $triggerNode = collect($version->graph['nodes'])->firstWhere('type', 'trigger');
            
            if ($triggerNode && ($triggerNode['data']['event_type'] ?? '') === $eventType) {
                
                // Check Idempotency
                $lockExists = ConnectJourneyTriggerLock::where([
                    'journey_id' => $journey->id,
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'contact_id' => $contactId
                ])->exists();
                
                if ($lockExists) continue; // Skip duplicate

                $instance = ConnectJourneyInstance::create([
                    'uuid' => Str::uuid()->toString(),
                    'journey_id' => $journey->id,
                    'version_id' => $version->id,
                    'merchant_id' => $merchantId,
                    'contact_id' => $contactId,
                    'status' => 'RUNNING'
                ]);

                ConnectJourneyTriggerLock::create([
                    'merchant_id' => $merchantId,
                    'journey_id' => $journey->id,
                    'version_id' => $version->id,
                    'contact_id' => $contactId,
                    'instance_id' => $instance->id,
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                ]);

                // Fire runtime
                $this->runtime->startInstance($instance);
            }
        }
    }
}
