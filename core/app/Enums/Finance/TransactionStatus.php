<?php

namespace App\Enums\Finance;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'completed'; // Mapeando pro ledger antigo completed
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case EXPIRED = 'expired';
    case REJECTED = 'rejected';
    case REFUNDED = 'refunded';
    case DISPUTED = 'disputed';
    case WON = 'won';
    case LOST = 'lost';
    case CHARGEBACK = 'chargeback';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::SUCCEEDED => 'Concluído',
            self::FAILED => 'Falhou',
            self::CANCELED => 'Cancelado',
            self::EXPIRED => 'Expirado',
            self::REJECTED => 'Rejeitado',
            self::REFUNDED => 'Reembolsado',
            self::DISPUTED => 'Em Disputa',
            self::WON => 'Ganha',
            self::LOST => 'Perdida',
            self::CHARGEBACK => 'Chargeback',
        };
    }
}
