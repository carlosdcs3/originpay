<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case RECEIVED = 'received';
    case UNDER_REVIEW = 'under_review';
    case WAITING_MERCHANT_DOCS = 'waiting_merchant_docs';
    case DOCS_RECEIVED = 'docs_received';
    case EVIDENCE_SENT = 'evidence_sent';
    case GATEWAY_REVIEW = 'gateway_review';
    case BANK_REVIEW = 'bank_review';
    case PENDING_DECISION = 'pending_decision';
    case WON = 'won';
    case LOST = 'lost';
    case CLOSED = 'closed';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match($this) {
            self::RECEIVED => 'Recebido',
            self::UNDER_REVIEW => 'Em análise',
            self::WAITING_MERCHANT_DOCS => 'Aguardando docs do lojista',
            self::DOCS_RECEIVED => 'Documentos recebidos',
            self::EVIDENCE_SENT => 'Evidências enviadas',
            self::GATEWAY_REVIEW => 'Em análise do gateway',
            self::BANK_REVIEW => 'Em análise do banco',
            self::PENDING_DECISION => 'Decisão pendente',
            self::WON => 'Ganha',
            self::LOST => 'Perdida',
            self::CLOSED => 'Encerrada',
            self::CANCELED => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::RECEIVED, self::DOCS_RECEIVED, self::EVIDENCE_SENT => 'info',
            self::UNDER_REVIEW, self::GATEWAY_REVIEW, self::BANK_REVIEW, self::PENDING_DECISION => 'primary',
            self::WAITING_MERCHANT_DOCS => 'warning',
            self::WON => 'success',
            self::LOST, self::CANCELED => 'danger',
            self::CLOSED => 'secondary',
        };
    }
}
