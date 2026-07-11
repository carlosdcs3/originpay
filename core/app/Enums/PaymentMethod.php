<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PIX = 'pix';
    case CARD = 'card';
    case BOLETO = 'boleto';
    case CRYPTO = 'crypto';

    public function label(): string
    {
        return match($this) {
            self::PIX => 'PIX',
            self::CARD => 'Cartão de Crédito/Débito',
            self::BOLETO => 'Boleto',
            self::CRYPTO => 'Criptomoedas',
        };
    }
}
