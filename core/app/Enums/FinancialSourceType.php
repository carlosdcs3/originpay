<?php

namespace App\Enums;

enum FinancialSourceType: string
{
    case MERCHANT = 'MERCHANT';
    case SYSTEM = 'SYSTEM';
    case GATEWAY = 'GATEWAY';
}
