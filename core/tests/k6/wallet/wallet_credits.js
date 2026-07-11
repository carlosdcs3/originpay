import http from 'k6/http';
import { sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions, assertSuccess } from '../common/common.js';

export const options = getOptions();

// Assume a seeded user wallet UUID for these tests
const TARGET_WALLET_UUID = 'LOAD-TEST-WALLET-UUID-001';

export default function () {
    const payload = JSON.stringify({
        wallet_uuid: TARGET_WALLET_UUID,
        amount: 10.00, // Small amount for credits
        currency: 'BRL',
    });

    // Targeting an internal credit API (e.g. system adjustments, deposits)
    const res = http.post(`${BASE_URL}/api/wallets/credit`, payload, { headers: HEADERS });
    
    assertSuccess(res, 'Wallet Credit');
    
    sleep(0.1);
}
