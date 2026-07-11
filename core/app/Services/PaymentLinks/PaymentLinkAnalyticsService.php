<?php

namespace App\Services\PaymentLinks;

use App\Models\Charge;
use App\Models\PaymentLink;
use App\Models\PaymentLinkVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PaymentLinkAnalyticsService
{
    /**
     * Records a visit to a payment link.
     */
    public function recordVisit(PaymentLink $link, Request $request): void
    {
        try {
            $userAgent = $request->userAgent() ?? '';
            $ip = $request->ip();
            $sessionId = $request->session()->getId();

            $isBot = $this->isBot($userAgent);
            
            // Hash unique visitors using IP + User Agent + Session
            $visitorHash = hash('sha256', $ip . $userAgent . $sessionId);

            // Check if this visitor already visited in the last 30 minutes
            $recentVisit = PaymentLinkVisit::where('payment_link_id', $link->id)
                ->where('visitor_hash', $visitorHash)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->first();

            if ($recentVisit) {
                // Store visit ID in session to associate with charge later
                $request->session()->put('payment_link_visit_id', $recentVisit->id);
                return;
            }

            // Detect Device, Browser, OS
            $device = $this->detectDevice($userAgent);
            $browser = $this->detectBrowser($userAgent);
            $platform = $this->detectPlatform($userAgent);

            $visit = PaymentLinkVisit::create([
                'payment_link_id' => $link->id,
                'session_id' => $sessionId,
                'visitor_hash' => $visitorHash,
                'ip_address' => $ip,
                'user_agent' => Str::limit($userAgent, 500),
                'referer' => Str::limit($request->header('referer'), 255),
                'utm_source' => Str::limit($request->query('utm_source'), 255),
                'utm_medium' => Str::limit($request->query('utm_medium'), 255),
                'utm_campaign' => Str::limit($request->query('utm_campaign'), 255),
                'utm_content' => Str::limit($request->query('utm_content'), 255),
                'utm_term' => Str::limit($request->query('utm_term'), 255),
                'device' => $device,
                'browser' => $browser,
                'platform' => $platform,
                'country' => null, // Reserved for future V2 GeoIP
                'state' => null,
                'city' => null,
                'is_bot' => $isBot,
            ]);

            $request->session()->put('payment_link_visit_id', $visit->id);

        } catch (\Exception $e) {
            Log::error('Failed to record payment link visit: ' . $e->getMessage(), [
                'link_id' => $link->id,
                'ip' => $request->ip()
            ]);
        }
    }

    /**
     * Marks a visit as converted when a charge is approved.
     */
    public function markConverted(Charge $charge): void
    {
        try {
            $metadata = $charge->metadata ?? [];
            $visitId = $metadata['payment_link_visit_id'] ?? null;
            $paymentLinkUuid = $metadata['payment_link_uuid'] ?? null;

            if (!$visitId) {
                // Not generated via our payment link flow
                return;
            }

            $visit = PaymentLinkVisit::find($visitId);

            if (!$visit || $visit->converted_at !== null) {
                // Does not exist or already converted
                return;
            }

            // Ensure the visit belongs to the corresponding payment link
            if ($paymentLinkUuid) {
                if ($visit->paymentLink->uuid !== $paymentLinkUuid) {
                    return;
                }
            }

            $visit->update([
                'converted_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark payment link visit as converted: ' . $e->getMessage(), [
                'charge_id' => $charge->id
            ]);
        }
    }

    private function isBot(string $userAgent): bool
    {
        $bots = [
            'googlebot', 'bingbot', 'yandexbot', 'duckduckbot', 'slurp',
            'twitterbot', 'facebookexternalhit', 'linkedinbot', 'embedly',
            'baiduspider', 'pinterest', 'slackbot', 'vkShare', 'facebot',
            'outbrain', 'W3C_Validator', 'whatsapp', 'telegrambot', 'discordbot'
        ];

        $ua = strtolower($userAgent);
        
        foreach ($bots as $bot) {
            if (str_contains($ua, $bot)) {
                return true;
            }
        }
        
        return false;
    }

    private function detectDevice(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'Tablet';
        }
        
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'Mobile';
        }
        
        return 'Desktop';
    }

    private function detectBrowser(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'edge') || str_contains($ua, 'edg')) return 'Edge';
        if (str_contains($ua, 'opr') || str_contains($ua, 'opera')) return 'Opera';
        if (str_contains($ua, 'chrome')) return 'Chrome';
        if (str_contains($ua, 'firefox')) return 'Firefox';
        if (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) return 'Safari';

        return 'Other';
    }

    private function detectPlatform(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'windows')) return 'Windows';
        if (str_contains($ua, 'mac')) return 'macOS';
        if (str_contains($ua, 'linux')) return 'Linux';
        if (str_contains($ua, 'android')) return 'Android';
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) return 'iOS';

        return 'Other';
    }
}
