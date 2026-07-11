<?php

namespace App\Exceptions;

use RuntimeException;

class GatewayWebhookValidationException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
