<?php

namespace Tests\Unit\PaymentMethod;

use PHPUnit\Framework\TestCase;
use App\DTOs\PaymentMethod\CreatePaymentMethodRequestDTO;
use App\Factories\PaymentMethod\PaymentMethodFactory;
use App\Repositories\PaymentMethod\MockPaymentMethodRepository;
use App\Services\PaymentMethod\PaymentMethodService;
use App\Vault\MockPaymentMethodVault;

class PaymentMethodCoreTest extends TestCase
{
    private PaymentMethodService $service;
    private MockPaymentMethodVault $vault;
    private MockPaymentMethodRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        MockPaymentMethodVault::flushMockStorage();
        MockPaymentMethodRepository::flushMockStorage();

        $this->vault = new MockPaymentMethodVault();
        $this->repository = new MockPaymentMethodRepository();
        $factory = new PaymentMethodFactory();

        $this->service = new PaymentMethodService($this->vault, $this->repository, $factory);
    }

    public function test_can_create_payment_method_safely()
    {
        $dto = new CreatePaymentMethodRequestDTO(
            'card',
            '4111111111111234',
            '12',
            '28',
            '123',
            'JOHN DOE'
        );

        $response = $this->service->createPaymentMethod($dto);

        // Assert Prefix standards
        $this->assertStringStartsWith('pm_', $response->getId());
        $this->assertStringStartsWith('fp_', $response->toArray()['fingerprint']);

        // Assert Safe Data in Response
        $this->assertEquals('card', $response->toArray()['type']);
        $this->assertEquals('1234', $response->toArray()['last4']);
        $this->assertEquals('visa', $response->toArray()['brand']);

        // Assert CVV and full PAN do NOT exist in Response
        $responseArray = $response->toArray();
        $this->assertArrayNotHasKey('cvv', $responseArray);
        $this->assertArrayNotHasKey('pan', $responseArray);

        // Assert Repository does not store PAN or CVV
        $entity = $this->repository->findById($response->getId());
        $this->assertNotNull($entity);
        $this->assertEquals('1234', $entity->getLast4());
        $this->assertFalse(method_exists($entity, 'getPan')); // PAN is never on Entity
        $this->assertFalse(method_exists($entity, 'getCvv')); // CVV is never on Entity

        // Assert Vault does not store CVV
        $storedData = null;
        // We know that storeMock returns a token format "vault_tok_...". We can't fetch it easily from service, 
        // but we can test vault logic directly.
    }

    public function test_vault_mock_strips_cvv_and_returns_token()
    {
        $dto = new CreatePaymentMethodRequestDTO('card', '5111111111111234', '10', '28', '888', 'JANE DOE');
        
        $token = $this->vault->storeMock($dto);
        
        $this->assertStringStartsWith('vault_tok_', $token);

        $vaultData = $this->vault->retrieveMock($token);
        
        $this->assertIsArray($vaultData);
        $this->assertArrayHasKey('pan', $vaultData);
        $this->assertArrayNotHasKey('cvv', $vaultData, 'Vault must never store CVV even in mock');
    }

    public function test_expiration_logic()
    {
        // Card expiring in the past should throw exception
        $dtoExpired = new CreatePaymentMethodRequestDTO('card', '4111111111111111', '01', '20'); // Jan 2020
        
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createPaymentMethod($dtoExpired);
    }
    
    public function test_expiration_logic_valid()
    {
        // Card expiring in the future
        $dtoValid = new CreatePaymentMethodRequestDTO('card', '4111111111111111', '12', '30'); // Dec 2030
        $responseValid = $this->service->createPaymentMethod($dtoValid);

        $entityValid = $this->repository->findById($responseValid->getId());
        $this->assertFalse($entityValid->isExpired(new \DateTimeImmutable('2026-07-01')));
    }
}
