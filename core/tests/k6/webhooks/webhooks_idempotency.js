import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions, generateRandomTrxId } from '../common/common.js';

export const options = getOptions();

// We generate ONE transaction ID for all VUs to try to double-spend
const TARGET_TRX_ID = 'TRX-IDEMPOTENCY-TEST-001';
const STATIC_PAYLOAD = JSON.stringify({
    providerTransactionId: TARGET_TRX_ID,
    status: 'PAID',
    amount: 100.00,
    currency: 'BRL',
});

export default function () {
    const res = http.post(`${BASE_URL}/api/webhooks/efi`, STATIC_PAYLOAD, { headers: HEADERS });
    
    // Idempotency usually returns 200/201 even on duplicate, but only processes ONCE
    check(res, {
        'status is 200 or 201': (r) => r.status === 200 || r.status === 201,
        'response time < 500ms': (r) => r.timings.duration < 500,
    });
    
    sleep(0.1);
}
