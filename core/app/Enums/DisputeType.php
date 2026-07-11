<?php

namespace App\Enums;

enum DisputeType: string
{
    case MED = 'med';
    case CHARGEBACK = 'chargeback';
    case REFUND_REQUEST = 'refund_request';
    case CONTESTATION = 'contestation';

    public function label(): string
    {
        return match($this) {
            self::MED => 'MED Pix',
            self::CHARGEBACK => 'Chargeback',
            self::REFUND_REQUEST => 'Extorno',
            self::CONTESTATION => 'Contestação',
        };
    }
}
