import http from 'k6/http';
import { sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions, generateRandomTrxId, assertSuccess } from '../common/common.js';

export const options = getOptions();

export default function () {
    const payload = JSON.stringify({
        providerTransactionId: generateRandomTrxId(),
        status: 'PAID',
        amount: 100.00,
        currency: 'BRL',
    });

    // We assume there's a generic modern webhook endpoint, adjusting as needed based on actual routes.
    // e.g. /api/webhooks/efi or /api/webhooks/stripe
    const res = http.post(`${BASE_URL}/api/webhooks/efi`, payload, { headers: HEADERS });
    
    assertSuccess(res, 'Distinct Webhook');
    
    // Slight pause to not kill the CPU purely from k6 side
    sleep(0.1);
}
