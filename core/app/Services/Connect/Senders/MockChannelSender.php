<?php
namespace App\Services\Connect\Senders;

use App\Models\Connect\ConnectCampaignRecipient;
use Illuminate\Support\Str;

class MockChannelSender
{
    public static function send(ConnectCampaignRecipient $recipient, string $compiledPayload): array
    {
        if (!app()->environment('testing')) {
            usleep(rand(100000, 500000)); // 0.1s to 0.5s latency simulation
        }

        // Simulate 95% success rate
        $isSuccess = rand(1, 100) <= 95;

        if ($isSuccess) {
            return [
                'success' => true,
                'provider' => 'mock_provider',
                'message_id' => 'mock-' . Str::uuid()->toString(),
                'error' => null
            ];
        }

        return [
            'success' => false,
            'provider' => 'mock_provider',
            'message_id' => null,
            'error' => 'Simulated external provider timeout or failure.'
        ];
    }
}
