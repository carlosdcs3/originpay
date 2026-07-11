<?php

namespace App\Enums;

/**
 * Represents an atomic payment operation, which is more granular
 * than a PaymentMethod. For example, PIX has two distinct operations:
 * a charge (cash-in) and a withdrawal (cash-out).
 *
 * This enum is used by the GatewayResolver to determine which gateway
 * should handle each specific operation.
 */
enum PaymentOperation: string
{
    // ────────── PIX ──────────
    /** Standard PIX charge / cash-in. Used by ChargeService. */
    case PIX_CHARGE = 'pix_charge';

    /**
     * PIX withdrawal / cash-out.
     *
     * @future Will be used by the Withdrawal module / WithdrawalService.
     *         Do NOT connect to ChargeService.
     * @see GatewayResolver::resolveForWithdrawal()
     */
    case PIX_WITHDRAW = 'pix_withdraw';

    // ────────── CARD ──────────
    /** Credit card charge (full amount, single installment). */
    case CARD_CREDIT = 'card_credit';

    /** Debit card charge. */
    case CARD_DEBIT = 'card_debit';

    /** Credit card installment charge. */
    case CARD_INSTALLMENTS = 'card_installments';

    // ────────── BOLETO ──────────
    /** Boleto bancário. */
    case BOLETO = 'boleto';

    // ────────── CRYPTO ──────────
    /** Cryptocurrency payment. */
    case CRYPTO = 'crypto';

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Human-readable label for use in Admin UI.
     */
    public function label(): string
    {
        return match($this) {
            self::PIX_CHARGE      => 'PIX — Cobrança',
            self::PIX_WITHDRAW    => 'PIX — Saque (Cash-out)',
            self::CARD_CREDIT     => 'Cartão — Crédito',
            self::CARD_DEBIT      => 'Cartão — Débito',
            self::CARD_INSTALLMENTS => 'Cartão — Parcelado',
            self::BOLETO          => 'Boleto',
            self::CRYPTO          => 'Criptomoedas',
        };
    }

    /**
     * Returns the parent PaymentMethod for compatibility with legacy code.
     */
    public function paymentMethod(): PaymentMethod
    {
        return match($this) {
            self::PIX_CHARGE, self::PIX_WITHDRAW => PaymentMethod::PIX,
            self::CARD_CREDIT, self::CARD_DEBIT, self::CARD_INSTALLMENTS => PaymentMethod::CARD,
            self::BOLETO => PaymentMethod::BOLETO,
            self::CRYPTO => PaymentMethod::CRYPTO,
        };
    }

    /**
     * Returns the gateway boolean flag that signals support for this operation.
     * Used to filter compatible gateways in the Resolver.
     */
    public function supportFlag(): string
    {
        return match($this) {
            self::PIX_CHARGE, self::PIX_WITHDRAW => 'supports_pix',
            self::CARD_CREDIT, self::CARD_DEBIT, self::CARD_INSTALLMENTS => 'supports_card',
            self::BOLETO => 'supports_boleto',
            self::CRYPTO => 'supports_crypto',
        };
    }

    /**
     * Maps a legacy PaymentMethod to the primary PaymentOperation.
     * Used by resolveAllForCharge() for backward compatibility.
     */
    public static function fromPaymentMethod(PaymentMethod $method): self
    {
        return match($method) {
            PaymentMethod::PIX    => self::PIX_CHARGE,
            PaymentMethod::CARD   => self::CARD_CREDIT,
            PaymentMethod::BOLETO => self::BOLETO,
            PaymentMethod::CRYPTO => self::CRYPTO,
        };
    }

    /**
     * Returns all operations grouped by parent method for Admin UI rendering.
     *
     * @return array<string, PaymentOperation[]>
     */
    public static function groupedByMethod(): array
    {
        $groups = [];
        foreach (self::cases() as $case) {
            $groups[$case->paymentMethod()->value][] = $case;
        }
        return $groups;
    }
}
