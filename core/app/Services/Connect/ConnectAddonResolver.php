<?php
namespace App\Services\Connect;

class ConnectAddonResolver
{
    /**
     * Placeholder pipeline step.
     * In the future, it merges the base capabilities with addon capabilities based on merchantId.
     */
    public function resolve(array $capabilities, $merchantId): array
    {
        return $capabilities;
    }
}
