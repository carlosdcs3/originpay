<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case INVALID_API_KEY = 'invalid_api_key';
    case MISSING_API_KEY = 'missing_api_key';
    case RESOURCE_NOT_FOUND = 'resource_not_found';
    case INTERNAL_ERROR = 'internal_error';
    case FUTURE_GATEWAY_ERROR = 'future_gateway_error';
    case INVALID_PARAMETERS = 'invalid_parameters';
}
