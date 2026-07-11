import { OriginPayAPIError } from './errors.js';

export class HttpClient {
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
