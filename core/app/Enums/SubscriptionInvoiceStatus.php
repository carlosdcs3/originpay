<?php

namespace App\Enums;

enum SubscriptionInvoiceStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case PAID = 'paid';
    case FAILED = 'failed';
    case VOID = 'void';
}
