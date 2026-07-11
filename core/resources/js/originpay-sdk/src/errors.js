class OriginPayError extends Error {
    constructor(message, type, code) {
        super(message);
        this.name = 'OriginPayError';
        this.type = type;
        this.code = code;
    }
}

export class OriginPayAPIError extends OriginPayError {
    constructor(message, code = 'api_error', statusCode = 500) {
        super(message, 'API_ERROR', code);
        this.name = 'OriginPayAPIError';
        this.statusCode = statusCode;
    }
}

export class OriginPayValidationError extends OriginPayError {
    constructor(message, code = 'validation_error') {
        super(message, 'VALIDATION_ERROR', code);
        this.name = 'OriginPayValidationError';
    }
}

export class OriginPayAuthenticationError extends OriginPayError {
    constructor(message, code = 'authentication_error') {
        super(message, 'AUTHENTICATION_ERROR', code);
        this.name = 'OriginPayAuthenticationError';
    }
}
