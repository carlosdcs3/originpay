<?php
namespace App\Services\Connect\Delivery\Adapters;

use App\Services\Connect\Delivery\ChannelAdapterInterface;
use App\Services\Connect\Delivery\ProviderInterface;
use App\Services\Connect\Delivery\DeliveryResult;
use App\Models\Connect\ConnectCampaignRecipient;
use App\Models\Connect\ConnectProviderCredential;

class SmsAdapter implements ChannelAdapterInterface
{
    protected ProviderInterface $driver;
    protected ConnectProviderCredential $credential;

    public function __construct(ProviderInterface $driver, ConnectProviderCredential $credential)
    {
        $this->driver = $driver;
        $this->credential = $credential;
    }

    public function send(ConnectCampaignRecipient $recipient, string $compiledMessage, array $metadata = []): DeliveryResult
    {
        $payload = [
            'to' => $recipient->contact->phone,
            'body' => strip_tags($compiledMessage),
        ];

        return $this->driver->send($payload, $this->credential->credentials ?? [], $this->credential->configuration ?? []);
    }
}
