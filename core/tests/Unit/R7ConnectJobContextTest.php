<?php

namespace Tests\Unit;

use App\Jobs\Connect\ProcessCampaignRecipientJob;
use PHPUnit\Framework\TestCase;

class R7ConnectJobContextTest extends TestCase
{
    public function test_job_payload_contains_tenant_and_correlation_but_not_credentials(): void
    {
        $job = new ProcessCampaignRecipientJob(10, 20, '11111111-1111-4111-8111-111111111111');
        $serialized = serialize($job);

        $this->assertStringContainsString('11111111-1111-4111-8111-111111111111', $serialized);
        $this->assertStringContainsString('20', $serialized);
        $this->assertStringNotContainsStringIgnoringCase('password', $serialized);
        $this->assertStringNotContainsStringIgnoringCase('secret', $serialized);
        $this->assertStringNotContainsStringIgnoringCase('token', $serialized);
    }
}
