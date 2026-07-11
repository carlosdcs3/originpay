import { check } from 'k6';
import crypto from 'k6/crypto';
import { getProfile } from '../configs/profiles.js';

// The base URL of the local API for testing. Change this if running against staging.
export const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

// Default headers for JSON API requests
export const HEADERS = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
};

// Strict Performance and Availability Thresholds for Go Live
export const THRESHOLDS = {
    'http_req_failed': ['rate<0.01'], // Erro máximo de 1%
    'http_req_duration': ['p(95)<500', 'p(99)<1000'], // Latência
    'checks': ['rate>0.99'], // 99% das validações lógicas (checks) devem passar
};

export function getOptions() {
    const profile = getProfile();
    return {
        scenarios: profile.scenarios,
        thresholds: THRESHOLDS,
    };
}

/**
 * Creates a unique random payload to avoid unintentional idempotency clashes.
 */
export function generateRandomTrxId() {
    return 'TRX-' + crypto.hexEncode(crypto.randomBytes(8)).toUpperCase();
}

/**
 * Common success checks.
 */
export function assertSuccess(res, name = 'Request') {
    check(res, {
        [`${name} is status 200 or 201`]: (r) => r.status === 200 || r.status === 201,
        [`${name} time < 500ms`]: (r) => r.timings.duration < 500,
    });
}
