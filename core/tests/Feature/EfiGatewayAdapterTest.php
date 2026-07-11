<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Gateways\Adapters\EfiGatewayAdapter;
use App\Services\Gateways\Adapters\Efi\EfiHttpClient;
use App\Domain\Payments\GatewayAuthorizeRequest;
use App\Domain\Payments\GatewayRuntimeConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;

class EfiGatewayAdapterTest extends TestCase
{
    public function test_efi_authorize_creates_pix_charge()
    {
        Http::fake([
            'api-pix.gerencianet.com.br/v2/cob' => Http::response([
                'txid' => 'txid123456',
                'loc' => ['id' => 789],
                'pixCopiaECola' => '00020126...BR.GOV.BCB.PIX...',
                'status' => 'ATIVA'
            ], 201)
        ]);

        $httpClient = app(EfiHttpClient::class);
        $adapter = new EfiGatewayAdapter($httpClient);

        $config = new GatewayRuntimeConfig(
            clientId: 'client123',
            clientSecret: 'secret123',
            certificatePath: storage_path('app/private/test.pem'),
            pixKey: 'test@pix.com',
            baseUrl: 'https://api-pix.gerencianet.com.br'
        );

        $request = new GatewayAuthorizeRequest(
            chargeId: 'ch_123',
            merchantId: 1,
            amount: 5000,
            currency: 'BRL',
            paymentMethodId: null,
            merchantMetadata: [],
            environment: 'production',
            runtimeConfig: $config
        );

        // We bypass the file check for the test by mocking EfiHttpClient if needed,
        // but since Http::fake intercepts, we just need to ensure the certificate check passes
        // or we mock the HTTP Client directly. Since EfiHttpClient checks for file existence, 
        // we might get an exception. Let's mock EfiHttpClient.

        $mockHttpClient = \Mockery::mock(EfiHttpClient::class);
        $mockHttpClient->shouldReceive('makeClient')->andReturn(Http::baseUrl('https://api-pix.gerencianet.com.br'));
        
        $adapter = new EfiGatewayAdapter($mockHttpClient);
        $result = $adapter->authorize($request);

        $this->assertTrue($result->success);
        $this->assertEquals('pending', $result->status);
        $this->assertEquals('efi', $result->gatewayName);
        $this->assertEquals('txid123456', $result->metadata['txid']);
        $this->assertEquals('00020126...BR.GOV.BCB.PIX...', $result->metadata['pix_copy_paste']);
    }
}
