<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Webhooks\WebhookSignatureService;

class WebhookSignatureTest extends TestCase
{
    public function test_signature_is_correctly_generated()
    {
        $service = new WebhookSignatureService();
        
        $payload = json_encode(['event' => 'payment.succeeded']);
        $secret = 'whsec_test_secret_123';
        $timestamp = 1620000000;

        $signature = $service->generateSignature($payload, $secret, $timestamp);
        
        // Expected hash
        $expected = hash_hmac('sha256', "1620000000.{$payload}", $secret);
        
        $this->assertEquals($expected, $signature);
    }

    public function test_generate_header_contains_t_and_v1()
    {
        $service = new WebhookSignatureService();
        
        $payload = json_encode(['event' => 'payment.succeeded']);
        $secret = 'whsec_test_secret_123';

        $header = $service->generateHeader($payload, $secret);

        $this->assertArrayHasKey('OriginPay-Signature', $header);
        
        $headerValue = $header['OriginPay-Signature'];
        
        $this->assertStringContainsString('t=', $headerValue);
        $this->assertStringContainsString(',v1=', $headerValue);
    }
}
