import http from 'k6/http';
import { check, sleep } from 'k6';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// Perfis de carga passados via variável de ambiente (k6 run -e PROFILE=standard k6-webhooks.js)
const profile = __ENV.PROFILE || 'quick';

const profiles = {
  quick: { vus: 10, duration: '10s' },
  standard: { vus: 100, duration: '1m' },
  extreme: { vus: 500, duration: '3m' }, // 500 usuários disparando contínuo, testando Idris/Redis max connections
};

export const options = {
  vus: profiles[profile].vus,
  duration: profiles[profile].duration,
  thresholds: {
    // 95% of requests must complete below 500ms
    http_req_duration: ['p(95)<500'],
    // Error rate must be less than 1%
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.APP_URL || 'http://localhost/api';

export default function () {
  // Simulando Webhook do Gateway (Pagamento Aprovado)
  const chargeId = `chg_${randomString(10)}`;
  const correlationId = `wh_evt_${randomString(15)}`;
  
  const payload = JSON.stringify({
    gateway: 'PAGARME',
    event: 'transaction.paid',
    data: {
      id: chargeId,
      status: 'paid',
      amount: 15000, // R$ 150.00
      correlation_id: correlationId
    }
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer test_token_webhook_123',
    },
  };

  // 1. Cenário: Webhook Inédito
  let res = http.post(`${BASE_URL}/webhook/pagarme`, payload, params);
  check(res, { 'status is 200 (Inédito)': (r) => r.status === 200 });

  // 2. Cenário: Duplicidade Imediata (Testando Idempotência / Lock)
  let resDup = http.post(`${BASE_URL}/webhook/pagarme`, payload, params);
  check(resDup, { 'status is 200 (Ignorado via Idempotência)': (r) => r.status === 200 });

  sleep(Math.random() * 2); // Simula intervalo realista
}
