<?php

namespace App\Enums;

enum ChargeStatus: string
{
    case PENDING = 'pending';
    case WAITING_PAYMENT = 'waiting_payment';
    case PROCESSING = 'processing';
    case AUTHORIZED = 'authorized';
    case PAID = 'paid';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::WAITING_PAYMENT => 'Aguardando pagamento',
            self::PROCESSING => 'Processando',
            self::AUTHORIZED => 'Autorizado',
            self::PAID => 'Pago',
            self::SUCCEEDED => 'Sucesso',
            self::FAILED => 'Falhou',
            self::EXPIRED => 'Expirado',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
            self::REJECTED => 'Rejeitado',
        };
    }
}
