import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions } from '../common/common.js';

export const options = getOptions();

const TARGET_WALLET_UUID = 'LOAD-TEST-WALLET-UUID-001';

export default function () {
    // Randomize whether this VU does a credit or debit to simulate chaotic traffic
    const isCredit = Math.random() > 0.5;
    
    const payload = JSON.stringify({
        wallet_uuid: TARGET_WALLET_UUID,
        amount: Math.floor(Math.random() * 50) + 1,
        currency: 'BRL',
    });

    let res;
    if (isCredit) {
        res = http.post(`${BASE_URL}/api/wallets/credit`, payload, { headers: HEADERS });
    } else {
        res = http.post(`${BASE_URL}/api/wallets/withdraw`, payload, { headers: HEADERS });
    }
    
    check(res, {
        'status is 200 or 400 (NSF if withdraw)': (r) => r.status === 200 || r.status === 400,
        'response time < 500ms': (r) => r.timings.duration < 500,
    });
    
    sleep(0.1);
}
