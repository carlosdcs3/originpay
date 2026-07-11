<?php
namespace App\Services\Connect\Delivery;
use App\Models\Connect\ConnectCampaignRecipient;

interface ChannelAdapterInterface
{
    public function send(ConnectCampaignRecipient $recipient, string $compiledMessage, array $metadata = []): DeliveryResult;
}
