<?php

namespace App\Gateway\Enums;

enum GatewayCapabilityEnum: string
{
    case PIX = 'pix';
    case BOLETO = 'boleto';
    case CARD = 'card';
    case WITHDRAW = 'withdraw';
    case REFUND = 'refund';
    case PARTIAL_REFUND = 'partial_refund';
    case CANCEL = 'cancel';
    case SPLIT = 'split';
    case RECONCILIATION = 'reconciliation';
    case WEBHOOK = 'webhook';
    case TOKEN_REFRESH = 'token_refresh';
    case MTLS = 'mtls';
}
