import http from 'k6/http';
import { sleep } from 'k6';
import { BASE_URL, HEADERS, getOptions, assertSuccess } from '../common/common.js';

export const options = getOptions();

export default function () {
    const payload = JSON.stringify({
        amount: Math.floor(Math.random() * (1000 - 10 + 1) + 10), // Random between 10 and 1000
        currency: 'BRL',
        description: 'Load test spike charge',
    });

    const res = http.post(`${BASE_URL}/api/charges`, payload, { headers: HEADERS });
    
    assertSuccess(res, 'Charge Creation Spike');
    
    sleep(0.1);
}
