
/**
 * OriginPay JS SDK v1
 * Build date: 2026-07-02T21:08:13.896Z
 * Environment support: Modern Browsers (ES2015+)
 */
(function(global) {
    'use strict';

    // --- errors.js ---
    class OriginPayError extends Error {
    constructor(message, type, code) {
        super(message);
        this.name = 'OriginPayError';
        this.type = type;
        this.code = code;
    }
}

class OriginPayAPIError extends OriginPayError {
    constructor(message, code = 'api_error', statusCode = 500) {
        super(message, 'API_ERROR', code);
        this.name = 'OriginPayAPIError';
        this.statusCode = statusCode;
    }
}

class OriginPayValidationError extends OriginPayError {
    constructor(message, code = 'validation_error') {
        super(message, 'VALIDATION_ERROR', code);
        this.name = 'OriginPayValidationError';
    }
}

class OriginPayAuthenticationError extends OriginPayError {
    constructor(message, code = 'authentication_error') {
        super(message, 'AUTHENTICATION_ERROR', code);
        this.name = 'OriginPayAuthenticationError';
    }
}


    // --- http-client.js ---
    

class HttpClient {
    constructor(publicKey, environment, options = {}) {
        this.publicKey = publicKey;
        this.environment = environment;
        this.correlationId = options.correlationId || null;
        this.baseURL = this._getBaseURL();
    }

    _getBaseURL() {
        // Mock endpoints for Sprint 3. In production, these would point to real API subdomains.
        return this.environment === 'sandbox' 
            ? 'https://sandbox-api.originpay.com/v1/transparent' 
            : 'https://api.originpay.com/v1/transparent';
    }

    _generateRequestId() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        
        // Simple fallback for environments without crypto.randomUUID
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    async post(endpoint, data = {}) {
        const requestId = this._generateRequestId();
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.publicKey}`,
            'OriginPay-Request-ID': requestId
        };

        if (this.correlationId) {
            headers['X-Correlation-ID'] = this.correlationId;
        }

        try {
            // Sprint 3: Avoid real API calls. We will mock the fetch behavior if testing.
            // In a real environment, this would execute:
            /*
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'POST',
                headers,
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new OriginPayAPIError(
                    result.message || 'API request failed',
                    result.code || 'api_error',
                    response.status
                );
            }

            return result;
            */
           
            // Stubbed successful response for Sprint 3
            return {
                _mocked: true,
                request_id: requestId,
                correlation_id: this.correlationId,
                endpoint: endpoint
            };

        } catch (error) {
            if (error instanceof OriginPayAPIError) {
                throw error;
            }
            throw new OriginPayAPIError('Network or parsing error occurred', 'network_error', 0);
        }
    }
}


    // --- session.js ---
    

class SessionManager {
    constructor(httpClient) {
        this.http = httpClient;
    }

    async createSession(payload) {
        // Basic Client-Side Validations
        if (!payload) {
            throw new OriginPayValidationError("Payload is required for creating a session.");
        }

        if (!payload.amount || typeof payload.amount !== 'number' || payload.amount <= 0) {
            throw new OriginPayValidationError("Valid 'amount' is required.");
        }

        if (!payload.currency) {
            throw new OriginPayValidationError("Valid 'currency' is required.");
        }

        // STUB - Sprint 3 Mock Implementation
        // In the final version, this will trigger the real API via this.http.post('/sessions', payload)
        
        console.warn("[OriginPay SDK] STUB MODE: createSession is mocked for Sprint 3. No real API call will be made.");

        // Simulate network delay
        await new Promise(resolve => setTimeout(resolve, 300));

        // Using the HTTP client to ensure request IDs are attached and logic flows
        const httpContext = await this.http.post('/sessions-mock-stub', payload);

        return {
            session_id: `cs_${this._generateMockId()}`,
            status: "AWAITING_PAYMENT_METHOD",
            expires_at: new Date(Date.now() + 30 * 60000).toISOString(),
            _debug_context: httpContext
        };
    }

    _generateMockId() {
        return Math.random().toString(36).substring(2, 10);
    }
}


    // --- originpay.js ---
    



const SDK_VERSION = '1.0.0';

class OriginPayCore {
    constructor() {
        this._initialized = false;
        this._publicKey = null;
        this._environment = null;
        this._http = null;
        this._sessionManager = null;
    }

    init(publicKey, options = {}) {
        if (!publicKey || typeof publicKey !== 'string' || !publicKey.startsWith('pk_')) {
            throw new OriginPayAuthenticationError("Invalid Public Key. It must start with 'pk_'.");
        }

        this._publicKey = publicKey;
        this._environment = publicKey.startsWith('pk_test_') ? 'sandbox' : 'live';
        
        this._http = new HttpClient(this._publicKey, this._environment, options);
        this._sessionManager = new SessionManager(this._http);
        
        this._initialized = true;

        // Prevent DOM manipulation enforcement
        if (typeof document !== 'undefined' && options.debugDOM) {
            console.warn("[OriginPay SDK] Warning: This SDK is headless and does not manage DOM elements.");
        }

        return this;
    }

    createSession(payload) {
        this._ensureInitialized();
        return this._sessionManager.createSession(payload);
    }

    getEnvironment() {
        this._ensureInitialized();
        return this._environment;
    }

    getVersion() {
        return SDK_VERSION;
    }

    _ensureInitialized() {
        if (!this._initialized) {
            throw new OriginPayAuthenticationError("OriginPay SDK has not been initialized. Call OriginPay.init(publicKey) first.");
        }
    }
}

// Export a singleton instance for global usage, or the class itself.
const OriginPay = new OriginPayCore();


    // Expose to global window object
    if (typeof window !== 'undefined') {
        window.OriginPay = OriginPay;
    }
    
    // Also expose to global for node tests
    global.OriginPay = OriginPay;

})(typeof window !== 'undefined' ? window : global);
