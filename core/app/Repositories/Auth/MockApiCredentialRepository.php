<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\ApiCredentialRepositoryInterface;
use App\Domain\Auth\ApiCredential;

class MockApiCredentialRepository implements ApiCredentialRepositoryInterface
{
    private array $credentials;

    public function __construct()
    {
        $this->credentials = [
            new ApiCredential(
                id: 'cred_mock_1',
                publicKey: 'pk_test_123456789',
                secretKey: 'sk_test_123456789',
                merchantId: 'merch_mock_1',
                status: 'active',
                createdAt: '2023-01-01T00:00:00Z'
            ),
        ];
    }

    public function findByPublicKey(string $publicKey): ?ApiCredential
    {
        foreach ($this->credentials as $credential) {
            if ($credential->publicKey === $publicKey) {
                return $credential;
            }
        }

        return null;
    }

    public function findBySecretKey(string $secretKey): ?ApiCredential
    {
        foreach ($this->credentials as $credential) {
            if ($credential->secretKey === $secretKey) {
                return $credential;
            }
        }

        return null;
    }
}
