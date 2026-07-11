<?php

namespace App\Services\Security;

class TenantBypass
{
    private static bool $isBypassed = false;

    public static function run(\Closure $callback)
    {
        $previous = self::$isBypassed;
        self::$isBypassed = true;

        try {
            return $callback();
        } finally {
            self::$isBypassed = $previous;
        }
    }

    public static function enable(): void
    {
        self::$isBypassed = true;
    }

    public static function disable(): void
    {
        self::$isBypassed = false;
    }

    public static function isBypassed(): bool
    {
        return self::$isBypassed;
    }
}
