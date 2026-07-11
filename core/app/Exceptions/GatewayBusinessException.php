<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a gateway request fails due to a business rule
 * (e.g., invalid document, invalid amount, blocked user).
 * This type of error should NOT trigger a fallback, as the same
 * payload will likely fail on the next gateway.
 */
class GatewayBusinessException extends Exception
{
}
