<?php
namespace App\Services\Connect\Journey;

use App\Models\Connect\Journey\ConnectJourneyInstance;
use App\Models\Connect\ConnectCampaignRecipient;
use App\Services\Connect\DeliveryService;
use App\Services\Connect\Template\TemplateEngine;
use Illuminate\Support\Str;

class JourneyActionExecutor
{
    protected DeliveryService $delivery;
    protected TemplateEngine $engine;

    public function __construct(DeliveryService $delivery, TemplateEngine $engine)
    {
        $this->delivery = $delivery;
        $this->engine = $engine;
    }

    public function execute(ConnectJourneyInstance $instance, array $node)
    {
        $action = $node['data']['action'] ?? null;
        
        if ($action === 'send_message') {
            $channel = $node['data']['channel'] ?? 'whatsapp';
            $templateAst = $node['data']['template_ast'] ?? [];
            
            $contact = $instance->contact;
            
            $context = [
                'contact.name' => $contact->name ?? '',
                'contact.email' => $contact->email ?? '',
            ];

            $compiledPayload = $this->engine->compile($templateAst, $channel, $context);
            
            // Build a volatile Recipient representation for the DeliveryService
            // Since DeliveryService requires a ConnectCampaignRecipient, we mock one or adjust DeliveryService to accept an interface.
            // For Epic 10 purity without changing Epic 8:
            $virtualRecipient = new ConnectCampaignRecipient([
                'id' => 0, // Volatile
                'execution_id' => 0,
                'merchant_id' => $instance->merchant_id,
                'contact_id' => $contact->id,
                'channel' => $channel,
                'attempts' => 0,
            ]);
            $virtualRecipient->setRelation('contact', $contact);

            // Reuses Epic 8!
            $result = $this->delivery->dispatch($virtualRecipient, $compiledPayload, ['subject' => 'Journey Automática']);
            
            // Note: In real life we'd save the DeliveryAttempt referencing the InstanceId instead of ExecutionId
        }
    }
}
