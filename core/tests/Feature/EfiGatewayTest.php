<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Payment\Modern\Providers\EfiGateway;
use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Payment\Modern\DTO\RefundDTO;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Services\TransactionService;

class EfiGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.efi.env' => 'sandbox',
            'services.efi.client_id' => 'fake_client',
            'services.efi.client_secret' => 'fake_secret',
            'services.efi.certificate_path' => 'storage/app/private/efi/test.pem',
            'services.efi.pix_key' => 'fake-pix-key',
        ]);
        Cache::forget('efi_access_token');
    }

    public function test_efi_gateway_create_pix()
    {
        Http::fake([
            '*oauth/token' => Http::response(['access_token' => 'fake_token'], 200),
            '*v2/cob' => Http::response(['txid' => '1234567890', 'pixCopiaECola' => '00020126...'], 201),
        ]);

        $gateway = new EfiGateway();
        $dto = new DepositDTO(100.00, 'BRL', 'internal-123');

        $response = $gateway->createPix($dto);

        $this->assertTrue($response->isSuccess);
        $this->assertEquals('1234567890', $response->providerTransactionId);
    }

    public function test_efi_webhook_parsing()
    {
        $gateway = new EfiGateway();
        $request = Request::create('/webhook/efi', 'POST', [
            'pix' => [
                [
                    'txid' => '1234567890',
                    'endToEndId' => 'E123456789',
                    'valor' => '100.00'
                ]
            ]
        ]);

        $webhookDto = $gateway->parseWebhook($request);

        $this->assertEquals('1234567890', $webhookDto->providerTransactionId);
        $this->assertEquals('E123456789', $webhookDto->externalReference);
        $this->assertEquals(100.00, $webhookDto->amount);
        $this->assertEquals('PAID', $webhookDto->status);
        $this->assertEquals('BRL', $webhookDto->currency);
    }

    public function test_efi_refund()
    {
        Http::fake([
            '*oauth/token' => Http::response(['access_token' => 'fake_token'], 200),
            '*devolucao*' => Http::response(['id' => 'REF123', 'status' => 'EM_PROCESSAMENTO'], 201),
        ]);

        $gateway = new EfiGateway();
        $dto = new RefundDTO('1234567890', 50.00, 'BRL', metadata: ['refund_id' => 'REF123']);

        $response = $gateway->refund($dto);

        $this->assertTrue($response->isSuccess);
        $this->assertEquals('REF123', $response->providerTransactionId);
    }

    public function test_efi_missing_credentials_fails_controlled()
    {
        config([
            'services.efi.client_id' => null,
            'services.efi.client_secret' => null,
        ]);

        $gateway = new EfiGateway();
        $dto = new DepositDTO(100.00, 'BRL', 'internal-123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('EFI credentials are not configured.');

        $gateway->createPix($dto);
    }
}
