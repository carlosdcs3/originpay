<?php

namespace App\Enums\Finance;

enum TransactionOperation: string
{
    case PIX_CHARGE = 'PIX_CHARGE';
    case PIX_WITHDRAW = 'PIX_WITHDRAW';
    case BOLETO_CHARGE = 'BOLETO_CHARGE';
    case CARD_CHARGE = 'CARD_CHARGE';
    case INTERNAL_TRANSFER = 'INTERNAL_TRANSFER';
    case WALLET_ADJUSTMENT = 'WALLET_ADJUSTMENT';
    case CHARGEBACK_HOLD = 'CHARGEBACK_HOLD';
    case CHARGEBACK_RELEASE = 'CHARGEBACK_RELEASE';
    case CHARGEBACK_DEBIT = 'CHARGEBACK_DEBIT';
    case SETTLEMENT_SCHEDULED = 'SETTLEMENT_SCHEDULED';
    case SETTLEMENT_PAID = 'SETTLEMENT_PAID';

    public function label(): string
    {
        return match($this) {
            self::PIX_CHARGE => 'Cobrança PIX',
            self::PIX_WITHDRAW => 'Saque PIX',
            self::BOLETO_CHARGE => 'Boleto',
            self::CARD_CHARGE => 'Cartão de Crédito',
            self::INTERNAL_TRANSFER => 'Transferência Interna',
            self::WALLET_ADJUSTMENT => 'Ajuste de Carteira',
            self::CHARGEBACK_HOLD => 'Retenção por Chargeback',
            self::CHARGEBACK_RELEASE => 'Liberação de Chargeback',
            self::CHARGEBACK_DEBIT => 'Débito por Chargeback',
            self::SETTLEMENT_SCHEDULED => 'Liquidação Agendada',
            self::SETTLEMENT_PAID => 'Liquidação Paga',
        };
    }
}
