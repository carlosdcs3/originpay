import { OriginPayValidationError } from './errors.js';

export class SessionManager {
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
