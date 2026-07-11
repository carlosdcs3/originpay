<?php

namespace App\Enums;

class WebhookDeliveryStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const DELIVERED = 'delivered';
    public const FAILED = 'failed';
    public const RETRYING = 'retrying';
    public const CANCELLED = 'cancelled';
    public const DEAD_LETTER = 'dead_letter';
}
