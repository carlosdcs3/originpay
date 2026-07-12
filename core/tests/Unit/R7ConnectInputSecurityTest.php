<?php

namespace Tests\Unit;

use App\Services\Connect\ConnectInputSecurity;
use PHPUnit\Framework\TestCase;

class R7ConnectInputSecurityTest extends TestCase
{
    public function test_provider_urls_reject_ssrf_targets_and_allow_public_https(): void
    {
        $security = new ConnectInputSecurity;

        $this->assertFalse($security->isSafeProviderUrl('http://127.0.0.1/hook'));
        $this->assertFalse($security->isSafeProviderUrl('http://169.254.169.254/latest/meta-data'));
        $this->assertFalse($security->isSafeProviderUrl('http://localhost/callback'));
        $this->assertFalse($security->isSafeProviderUrl('javascript:alert(1)'));
        $this->assertTrue($security->isSafeProviderUrl('https://example.com/callback'));
    }

    public function test_provisional_quotas_reject_excess(): void
    {
        $security = new ConnectInputSecurity(['campaign_jobs' => 1000, 'providers' => 5, 'uploads' => 20]);

        $this->assertTrue($security->withinQuota('campaign_jobs', 999, 1));
        $this->assertFalse($security->withinQuota('campaign_jobs', 1000, 1));
        $this->assertFalse($security->withinQuota('providers', 5, 1));
    }
}
