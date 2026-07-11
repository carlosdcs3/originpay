import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions, generateRandomTrxId } from './common/common.js';

export const options = getOptions();

const TARGET_WALLET_UUID = 'LOAD-TEST-WALLET-UUID-001';

export default function () {
    // Determine action: 40% charge, 40% webhook, 20% wallet directly
    const rand = Math.random();

    if (rand < 0.4) {
        // CHARGE
        const payload = JSON.stringify({
            amount: Math.floor(Math.random() * (100 - 10 + 1) + 10),
            currency: 'BRL',
            description: 'Soak test charge',
        });
        const res = http.post(`${BASE_URL}/api/charges`, payload, { headers: HEADERS });
        check(res, { 'status is 200 or 201': (r) => r.status === 200 || r.status === 201 });
    } else if (rand < 0.8) {
        // WEBHOOK
        const payload = JSON.stringify({
            event_type: 'PAYMENT_RECEIVED',
            transaction_id: generateRandomTrxId(),
            amount: 50.00,
            status: 'COMPLETED'
        });
        const res = http.post(`${BASE_URL}/api/webhooks`, payload, { headers: HEADERS });
        check(res, { 'status is 200': (r) => r.status === 200 });
    } else {
        // WALLET (mix credit/withdraw)
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
        check(res, { 'status 200 or 400': (r) => r.status === 200 || r.status === 400 });
    }
    
    // Pace requests slightly for soak
    sleep(0.5);
}
