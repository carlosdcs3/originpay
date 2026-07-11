<?php

namespace App\Gateway\Security\Drivers;

interface AuthenticationDriverInterface
{
    /**
     * Retorna um array de headers (ou opcoes) de autenticacao para anexar na request
     */
    public function authenticate(array $config): array;
}
