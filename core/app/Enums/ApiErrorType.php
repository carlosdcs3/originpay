<?php

namespace App\Enums;

enum ApiErrorType: string
{
    case AUTHENTICATION_ERROR = 'authentication_error';
    case VALIDATION_ERROR = 'validation_error';
    case INVALID_REQUEST = 'invalid_request';
    case API_ERROR = 'api_error';
    case RATE_LIMIT_ERROR = 'rate_limit_error';
}
