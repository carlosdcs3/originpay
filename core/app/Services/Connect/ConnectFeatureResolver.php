<?php
namespace App\Services\Connect;

use App\Support\Connect\Capabilities;

class ConnectFeatureResolver
{
    /**
     * Pure function that maps a plan name to an array of base capabilities.
     */
    public function resolve($planName): array
    {
        $planName = strtolower($planName ?? 'free');

        $free = [
            Capabilities::EMAIL_SEND,
            Capabilities::CONTACT_READ,
        ];

        $starter = array_merge($free, [
            Capabilities::WHATSAPP_SEND,
            Capabilities::TEMPLATES_CREATE,
            Capabilities::TEMPLATES_UPDATE,
            Capabilities::TEMPLATES_DELETE,
            Capabilities::TEMPLATES_USE,
            Capabilities::CAMPAIGNS_CREATE,
            Capabilities::CAMPAIGNS_UPDATE,
            Capabilities::CAMPAIGNS_DELETE,
            Capabilities::CONTACT_WRITE,
            Capabilities::CONTACT_IMPORT,
            Capabilities::CONTACT_EXPORT,
            Capabilities::AUTOMATION_RUN,
            Capabilities::TEMPLATE_READ,
            Capabilities::TEMPLATE_WRITE,
            Capabilities::TEMPLATE_PUBLISH,
            Capabilities::TEMPLATE_DELETE,
            Capabilities::CAMPAIGN_READ,
            Capabilities::CAMPAIGN_WRITE,
            Capabilities::CAMPAIGN_EXECUTE,
            Capabilities::CAMPAIGN_CANCEL,
            Capabilities::SEGMENT_READ,
            Capabilities::SEGMENT_WRITE,
            Capabilities::SEGMENT_DELETE,
            Capabilities::ANALYTICS_VIEW,
        ]);

        $pro = array_merge($starter, [
            Capabilities::API_ACCESS,
            Capabilities::WHATSAPP_TEMPLATES,
            Capabilities::WHATSAPP_CONTACTS,
            Capabilities::WHATSAPP_ANALYTICS,
        ]);

        switch ($planName) {
            case 'origin connect starter':
            case 'starter':
            case 'origin connect':
                return $starter;
            case 'pro':
                return $pro;
            case 'free':
            default:
                return $free;
        }
    }
}
