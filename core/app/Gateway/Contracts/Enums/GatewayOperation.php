<?php

namespace App\Gateway\Contracts\Enums;

enum GatewayOperation: string
{
    case CHARGE_PIX = 'charge_pix';
    case CHARGE_BOLETO = 'charge_boleto';
    case CHARGE_CREDIT_CARD = 'charge_credit_card';
    case WITHDRAW_PIX = 'withdraw_pix';
    case WITHDRAW_TED = 'withdraw_ted';
    case REFUND = 'refund';
    case CHECK_STATUS = 'check_status';
}
