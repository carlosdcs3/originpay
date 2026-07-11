import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions } from '../common/common.js';

export const options = getOptions();

const TARGET_WALLET_UUID = 'LOAD-TEST-WALLET-UUID-001';

export default function () {
    const payload = JSON.stringify({
        wallet_uuid: TARGET_WALLET_UUID,
        amount: 15.00, // Slightly higher than credit to force NSF eventually
        currency: 'BRL',
    });

    const res = http.post(`${BASE_URL}/api/wallets/withdraw`, payload, { headers: HEADERS });
    
    // In withdraws, we expect some to fail with 400 (Insufficient Funds), which is correct behavior under load
    check(res, {
        'status is 200 or 400 (NSF)': (r) => r.status === 200 || r.status === 400,
        'response time < 500ms': (r) => r.timings.duration < 500,
    });
    
    sleep(0.1);
}
