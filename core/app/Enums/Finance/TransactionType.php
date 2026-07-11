<?php

namespace App\Enums\Finance;

enum TransactionType: string
{
    case CHARGE = 'CHARGE';
    case WITHDRAW = 'WITHDRAW';
    case REFUND = 'REFUND';
    case CHARGEBACK = 'CHARGEBACK';
    case FEE = 'FEE';
    case SPLIT = 'SPLIT';
    case ADJUSTMENT = 'ADJUSTMENT';
    case TRANSFER = 'TRANSFER';
    case LEGACY_MIGRATION = 'LEGACY_MIGRATION';
    case SETTLEMENT = 'SETTLEMENT';

    public function label(): string
    {
        return match($this) {
            self::CHARGE => 'Cobrança',
            self::WITHDRAW => 'Saque',
            self::REFUND => 'Estorno',
            self::CHARGEBACK => 'Chargeback',
            self::FEE => 'Taxa',
            self::SPLIT => 'Repasse',
            self::ADJUSTMENT => 'Ajuste',
            self::TRANSFER => 'Transferência',
            self::LEGACY_MIGRATION => 'Migração de Saldo',
            self::SETTLEMENT => 'Liquidação',
        };
    }
}
