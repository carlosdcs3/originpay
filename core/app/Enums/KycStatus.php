<?php

namespace App\Enums;

enum KycStatus: int
{
    case PENDING  = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    case NOT_STARTED = 3;
    case DRAFT = 4;

    /**
     * Get the human-readable label for the KYC status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING  => __('Em Análise'),
            self::APPROVED => __('Aprovado'),
            self::REJECTED => __('Rejeitado'),
            self::NOT_STARTED => __('Não Iniciado'),
            self::DRAFT => __('Rascunho'),
        };
    }

    /**
     * Get the Bootstrap color class for the KYC status.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING  => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::NOT_STARTED => 'secondary',
            self::DRAFT => 'info',
        };
    }

    /**
     * Get an array of all enum values.
     *
     * @return int[]
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Get an associative array of options for dropdowns.
     * Keys are enum values, values are the labels.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return array_combine(
            self::all(),
            array_map(fn (self $case) => $case->label(), self::cases())
        );
    }
}
