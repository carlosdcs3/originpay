<?php

namespace App\Services\Finance\Formatting;

use App\Enums\Finance\TransactionStatus;
use App\Enums\Finance\FeeStatus;

class TransactionBadgeService
{
    /**
     * Retorna [label, css_class, icon]
     */
    public static function getBadge(mixed $status): array
    {
        if (is_string($status)) {
            $statusEnum = TransactionStatus::tryFrom(strtolower($status)) 
                       ?? FeeStatus::tryFrom(strtolower($status));
        } else {
            $statusEnum = $status;
        }
        
        if (!$statusEnum) {
            return ['label' => strtoupper((string)$status), 'class' => 'badge bg-secondary', 'icon' => 'fas fa-info-circle'];
        }

        if ($statusEnum instanceof FeeStatus) {
            return match($statusEnum) {
                FeeStatus::EXPECTED => ['label' => $statusEnum->label(), 'class' => 'badge bg-warning', 'icon' => 'fas fa-clock'],
                FeeStatus::CONFIRMED => ['label' => $statusEnum->label(), 'class' => 'badge bg-success', 'icon' => 'fas fa-check-double'],
                FeeStatus::DIVERGENT => ['label' => $statusEnum->label(), 'class' => 'badge bg-danger', 'icon' => 'fas fa-exclamation-triangle'],
            };
        }

        return match($statusEnum) {
            TransactionStatus::SUCCEEDED => ['label' => 'Concluído', 'class' => 'badge bg-success', 'icon' => 'fas fa-check-circle'],
            TransactionStatus::PENDING, TransactionStatus::PROCESSING => ['label' => $statusEnum->label(), 'class' => 'badge bg-warning', 'icon' => 'fas fa-clock'],
            TransactionStatus::FAILED, TransactionStatus::CANCELED, TransactionStatus::REJECTED, TransactionStatus::EXPIRED => ['label' => $statusEnum->label(), 'class' => 'badge bg-danger', 'icon' => 'fas fa-times-circle'],
            TransactionStatus::CHARGEBACK, TransactionStatus::LOST => ['label' => $statusEnum->label(), 'class' => 'badge bg-danger', 'icon' => 'fas fa-exclamation-triangle'],
            TransactionStatus::DISPUTED => ['label' => 'Em Disputa', 'class' => 'badge bg-warning', 'icon' => 'fas fa-balance-scale'],
            TransactionStatus::REFUNDED => ['label' => 'Reembolsado', 'class' => 'badge bg-info', 'icon' => 'fas fa-undo'],
            TransactionStatus::WON => ['label' => 'Disputa Ganha', 'class' => 'badge bg-success', 'icon' => 'fas fa-trophy'],
        };
    }

    public static function render(mixed $status): string
    {
        $badge = self::getBadge($status);
        return "<span class=\"{$badge['class']}\"><i class=\"{$badge['icon']} me-1\"></i> {$badge['label']}</span>";
    }
}
