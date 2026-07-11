<?php
namespace App\Services\Connect\Campaign;

use App\Models\Connect\Campaign;
use App\Services\Connect\Template\TemplateEngine;

class CampaignDryRunService
{
    protected $audienceResolver;
    protected $templateEngine;

    public function __construct(CampaignAudienceResolver $audienceResolver, TemplateEngine $templateEngine)
    {
        $this->audienceResolver = $audienceResolver;
        $this->templateEngine = $templateEngine;
    }

    public function run(Campaign $campaign): array
    {
        $query = $this->audienceResolver->getQuery($campaign->segment);
        // Dry run is always limited to 10 contacts to protect memory
        $contacts = $query->limit(10)->get();
        
        $results = [];
        $ast = $campaign->template->content;

        foreach ($contacts as $contact) {
            // Simplified Fake Context mapping for the real contact
            $context = [
                'contact.name' => $contact->name,
                'contact.email' => $contact->email,
                'merchant.name' => 'Lojista Teste',
            ];
            
            $output = $this->templateEngine->compile($ast, $campaign->channel, $context);
            
            $results[] = [
                'contact_id' => $contact->id,
                'name' => $contact->name,
                'output' => $output
            ];
        }

        return [
            'total_previewed' => count($results),
            'samples' => $results
        ];
    }
}
