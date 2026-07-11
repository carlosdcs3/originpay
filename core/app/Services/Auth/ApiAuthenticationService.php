<?php

namespace App\Services\Auth;

use App\Contracts\Auth\ApiCredentialRepositoryInterface;
use App\Domain\Auth\MerchantContext;

class ApiAuthenticationService
{
    public function __construct(
        private readonly ApiCredentialRepositoryInterface $repository
    ) {
    }

    public function authenticate(string $authorizationHeader, string $requestId): ?MerchantContext
    {
        if (empty($authorizationHeader) || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authorizationHeader, 7);
        $credential = null;
        $environment = 'production';

        if (str_starts_with($token, 'sk_test_')) {
            $environment = 'sandbox';
        }

        if (str_starts_with($token, 'sk_')) {
            $credential = $this->repository->findBySecretKey($token);
        }

        if (!$credential || ! in_array($credential->status, ['active', 'rotating'], true)) {
            return null;
        }

        return new MerchantContext(
            merchantId: $credential->merchantId,
            merchantName: $credential->merchantId,
            environment: $environment,
            permissions: [],
            requestId: $requestId,
            apiVersion: 'v1',
            credentialId: $credential->id
        );
    }
}
