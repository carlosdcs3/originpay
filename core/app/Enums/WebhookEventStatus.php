<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case RECEIVED = 'RECEIVED';
    case PROCESSING = 'PROCESSING';
    case PROCESSED = 'PROCESSED';
    case FAILED = 'FAILED';
    case DEAD_LETTER = 'DEAD_LETTER';
    case RETRYING = 'RETRYING';
    case DUPLICATED = 'DUPLICATED';
    case MANUALLY_RESOLVED = 'MANUALLY_RESOLVED';
}
