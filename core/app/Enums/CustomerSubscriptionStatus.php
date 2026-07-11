<?php

namespace App\Enums;

enum CustomerSubscriptionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELED = 'canceled';
    case INCOMPLETE = 'incomplete';
}
