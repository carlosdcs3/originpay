import { OriginPayAuthenticationError } from './errors.js';
import { HttpClient } from './http-client.js';
import { SessionManager } from './session.js';

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
export const OriginPay = new OriginPayCore();
