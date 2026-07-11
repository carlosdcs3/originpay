<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Enums\ProviderType;
use App\Payment\Modern\ModernPaymentGatewayFactory;
use App\Payment\Modern\ModernPaymentGatewayInterface;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\GatewayResponseDTO;
use App\Payment\Modern\DTO\RefundDTO;
use App\Payment\Modern\DTO\WithdrawDTO;
use App\Payment\Modern\DTO\GatewayTransactionDTO;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

// Stub adapter for testing
class DummyModernGateway implements ModernPaymentGatewayInterface
{
    public function createDeposit(DepositDTO $dto): GatewayResponseDTO {
        return new GatewayResponseDTO(true, 'dummy_123', 'https://dummy.link');
    }
    public function createPix(DepositDTO $dto): GatewayResponseDTO {
        return new GatewayResponseDTO(true, 'dummy_pix_123', null, 'qr_code_string');
    }
    public function createCheckout(DepositDTO $dto): GatewayResponseDTO {
        return new GatewayResponseDTO(true, 'dummy_chk_123', 'https://dummy.checkout');
    }
    public function verifyWebhook(Request $request): bool {
        return $request->header('x-dummy-sig') === 'valid';
    }
    public function parseWebhook(Request $request): WebhookDTO {
        return new WebhookDTO(
            providerTransactionId: $request->input('id'),
            externalReference: $request->input('ref'),
            status: 'PAID',
            amount: (float) $request->input('amount'),
            currency: 'USD'
        );
    }
    public function refund(RefundDTO $dto): GatewayResponseDTO {
        return new GatewayResponseDTO(true, 'dummy_ref_123');
    }
    public function withdraw(WithdrawDTO $dto): GatewayResponseDTO {
        return new GatewayResponseDTO(true, 'dummy_wd_123');
    }
    public function getTransaction(string $providerTrxId): GatewayTransactionDTO {
        return new GatewayTransactionDTO($providerTrxId, 'PAID', 100, 'USD');
    }
    public function healthCheck(): string {
        return 'CONNECTED';
    }
}

// Violating adapter for testing database isolation
class ViolatingModernGateway extends DummyModernGateway
{
    public function parseWebhook(Request $request): WebhookDTO {
        // Simulating a malicious or poorly designed gateway that touches the DB
        DB::table('users')->first(); // DB hit
        return parent::parseWebhook($request);
    }
}


class ModernPaymentGatewayTest extends TestCase
{
    public function test_factory_resolves_provider_type_correctly()
    {
        $factory = new ModernPaymentGatewayFactory();
        $factory->registerGateway(ProviderType::STRIPE, DummyModernGateway::class);

        $gateway = $factory->getGateway(ProviderType::STRIPE);
        
        $this->assertInstanceOf(ModernPaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf(DummyModernGateway::class, $gateway);
    }

    public function test_invalid_provider_fails_with_clear_error()
    {
        $factory = new ModernPaymentGatewayFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Modern payment gateway adapter not implemented for provider: ' . ProviderType::PAYPAL->value);

        $factory->getGateway(ProviderType::PAYPAL); // Not registered
    }

    public function test_modern_webhook_returns_webhook_dto()
    {
        $gateway = new DummyModernGateway();
        
        $request = Request::create('/webhook', 'POST', [
            'id' => 'tx_999',
            'ref' => 'order_1',
            'amount' => 500.00
        ]);

        $dto = $gateway->parseWebhook($request);

        $this->assertInstanceOf(WebhookDTO::class, $dto);
        $this->assertEquals('tx_999', $dto->providerTransactionId);
        $this->assertEquals('PAID', $dto->status);
        $this->assertEquals(500.00, $dto->amount);
    }

    public function test_modern_gateway_does_not_touch_database()
    {
        $gateway = new DummyModernGateway();
        $request = Request::create('/webhook', 'POST', ['id' => 'tx_123', 'amount' => 10]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        // This method should be completely pure and disconnected from Laravel DB
        $gateway->parseWebhook($request);

        $this->assertEmpty(DB::getQueryLog(), "Modern Gateway is improperly hitting the database during pure DTO parsing.");
    }
}
