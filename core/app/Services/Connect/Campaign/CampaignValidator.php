<?php
namespace App\Services\Connect\Campaign;

use App\Models\Connect\Template;
use App\Models\Connect\ConnectSegment;
use Exception;

class CampaignValidator
{
    public function validateForExecution(Template $template, ConnectSegment $segment, string $campaignChannel)
    {
        if ($template->status !== Template::STATUS_PUBLISHED) {
            throw new Exception("O Template deve estar publicado para ser utilizado em uma campanha.");
        }
        if (!$template->is_current) {
            throw new Exception("Apenas a versão ativa (is_current=true) de um template pode ser utilizada.");
        }
        if ($template->channel !== $campaignChannel) {
            throw new Exception("Canal incompatível. A campanha é {$campaignChannel} mas o template é {$template->channel}.");
        }
    }
}
