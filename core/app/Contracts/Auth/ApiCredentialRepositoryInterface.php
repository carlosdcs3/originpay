<?php

namespace App\Contracts\Auth;

use App\Domain\Auth\ApiCredential;

interface ApiCredentialRepositoryInterface
{
    public function findByPublicKey(string $publicKey): ?ApiCredential;
    
    public function findBySecretKey(string $secretKey): ?ApiCredential;
}
