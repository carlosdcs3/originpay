import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions, generateRandomTrxId } from '../common/common.js';

export const options = getOptions();

const TARGET_TRX_ID = 'TRX-REPLAY-TEST-002';

// Original Payload that system knows
const ORIGINAL_PAYLOAD = JSON.stringify({
    providerTransactionId: TARGET_TRX_ID,
    status: 'PAID',
    amount: 100.00,
    currency: 'BRL',
});

export function setup() {
    // Fire the original payload ONCE to register the idempotency key in the DB
    http.post(`${BASE_URL}/api/webhooks/efi`, ORIGINAL_PAYLOAD, { headers: HEADERS });
}

export default function () {
    // Malicious payload trying to replay the same transaction but alter the amount
    const MALICIOUS_PAYLOAD = JSON.stringify({
        providerTransactionId: TARGET_TRX_ID,
        status: 'PAID',
        amount: 9999.00, // Replay attack: modified amount
        currency: 'BRL',
    });

    const res = http.post(`${BASE_URL}/api/webhooks/efi`, MALICIOUS_PAYLOAD, { headers: HEADERS });
    
    // The system should detect replay, block it, and throw a 500 or 400.
    check(res, {
        'status is not 200 (Replay Blocked)': (r) => r.status !== 200 && r.status !== 201,
        'response time < 500ms': (r) => r.timings.duration < 500,
    });
    
    sleep(0.1);
}
