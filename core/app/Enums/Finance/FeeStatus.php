<?php

namespace App\Enums\Finance;

enum FeeStatus: string
{
    case EXPECTED = 'expected';
    case CONFIRMED = 'confirmed';
    case DIVERGENT = 'divergent';

    public function label(): string
    {
        return match($this) {
            self::EXPECTED => 'Esperada',
            self::CONFIRMED => 'Confirmada',
            self::DIVERGENT => 'Divergente',
        };
    }
}
