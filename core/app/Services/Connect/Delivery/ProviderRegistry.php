<?php
namespace App\Services\Connect\Delivery;

use Illuminate\Support\Facades\Cache;
use App\Models\Connect\ConnectProviderCredential;
use App\Services\Connect\Delivery\Adapters\EmailAdapter;
use App\Services\Connect\Delivery\Adapters\WhatsAppAdapter;
use App\Services\Connect\Delivery\Adapters\SmsAdapter;
use App\Services\Connect\Delivery\Drivers\AwsSesDriver;
use App\Services\Connect\Delivery\Drivers\MetaCloudApiDriver;
use App\Services\Connect\Delivery\Drivers\TwilioDriver;
use Exception;

class ProviderRegistry
{
    public function getActiveCredentials(int $merchantId, string $channel): array
    {
        $cacheKey = "connect_provider_credentials_m{$merchantId}_c{$channel}";
        
        return Cache::remember($cacheKey, 300, function () use ($merchantId, $channel) {
            return ConnectProviderCredential::where('merchant_id', $merchantId)
                ->where('channel', $channel)
                ->where('is_active', true)
                ->orderBy('priority', 'asc')
                ->get()
                ->all();
        });
    }

    public function resolveAdapter(string $channel, ProviderInterface $driver, ConnectProviderCredential $credential): ChannelAdapterInterface
    {
        return match($channel) {
            'email' => new EmailAdapter($driver, $credential),
            'whatsapp' => new WhatsAppAdapter($driver, $credential),
            'sms' => new SmsAdapter($driver, $credential),
            default => throw new Exception("Channel não suportado pelo Adapter Registry: {$channel}")
        };
    }

    public function resolveDriver(string $provider): ProviderInterface
    {
        return match($provider) {
            'aws_ses' => new AwsSesDriver(),
            'meta_cloud' => new MetaCloudApiDriver(),
            'twilio' => new TwilioDriver(),
            default => throw new Exception("Provider Driver não encontrado: {$provider}")
        };
    }
}
