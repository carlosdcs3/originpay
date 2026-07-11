@extends('frontend.layouts.api_reference')

@section('title', 'Webhook Simulator')

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.v1.api_reference.index') }}">API Reference</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>Webhook Simulator</span>
    </div>

    <h1>Webhook Simulator</h1>
    <p class="lead" style="margin-bottom: 32px;">
        Gere payloads e assinaturas reais para testar o seu listener de webhooks localmente.
    </p>

    <div style="background: var(--doc-surface); border: 1px solid var(--doc-border); border-radius: 12px; padding: 24px; margin-bottom: 48px;">
        <div style="margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; color: #fff; font-weight: 500;">Evento a simular</label>
            <select id="whEvent" style="width: 100%; background: #000; border: 1px solid var(--doc-border); color: #fff; padding: 12px; border-radius: 8px; font-family: var(--doc-font); outline: none;">
                <option value="payment.created">payment.created</option>
                <option value="payment.paid">payment.paid</option>
                <option value="payment.failed">payment.failed</option>
                <option value="payment.refunded">payment.refunded</option>
                <option value="chargeback.opened">chargeback.opened</option>
                <option value="chargeback.won">chargeback.won</option>
                <option value="payout.created">payout.created</option>
                <option value="payout.paid">payout.paid</option>
            </select>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; color: #fff; font-weight: 500;">Sua Webhook Secret (whsec_...)</label>
            <input type="text" id="whSecret" value="whsec_test_123456789" style="width: 100%; background: #000; border: 1px solid var(--doc-border); color: #fff; padding: 12px; border-radius: 8px; font-family: monospace;">
            <p style="color: var(--doc-muted); font-size: 0.8rem; margin-top: 8px;">Apenas para gerar a assinatura localmente no seu browser. Nunca é enviada ao servidor.</p>
        </div>

        <button onclick="generateWebhook()" style="background: var(--doc-primary); color: #000; font-weight: 600; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="zap" style="width: 16px;"></i>
            Gerar Webhook
        </button>
    </div>

    <h2 id="expected">O que esperamos de você</h2>
    <p>O seu servidor deve validar a assinatura usando o secret, processar o evento, e retornar <strong>HTTP 200 OK</strong> o mais rápido possível.</p>
    <div class="doc-code-block" style="background: transparent; border: 1px dashed var(--doc-border);">
        <div class="doc-code-content" style="padding: 16px;">
            <pre style="color: #10b981;">HTTP/1.1 200 OK</pre>
        </div>
    </div>
@endsection

@section('code_panel')
    <div style="margin-bottom: 32px;">
        <h3 style="color: #fff; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Generated Request</h3>
        <div class="doc-code-block" style="margin: 0; background: #000; border-color: rgba(255,255,255,0.1);">
            <div class="doc-code-header" style="background: transparent; border-color: rgba(255,255,255,0.05);">
                <div class="doc-code-tabs">
                    <div class="doc-code-tab active">Headers & Body</div>
                </div>
            </div>
            <div class="doc-code-content" style="padding: 16px; position: relative;">
                <button onclick="copyWebhook()" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.1); border: none; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">Copiar</button>
<pre id="whOutput" style="color: #a1a1aa; font-size: 0.75rem; line-height: 1.5;"></pre>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Simple HMAC SHA256 simulation using Web Crypto API
    async function simHmac(secret, text) {
        const enc = new TextEncoder();
        const keyData = enc.encode(secret);
        const textData = enc.encode(text);
        
        const cryptoKey = await crypto.subtle.importKey(
            "raw", keyData, { name: "HMAC", hash: "SHA-256" }, false, ["sign"]
        );
        const signature = await crypto.subtle.sign("HMAC", cryptoKey, textData);
        
        const hashArray = Array.from(new Uint8Array(signature));
        const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        return hashHex;
    }

    function uuidv4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    async function generateWebhook() {
        const eventName = document.getElementById('whEvent').value;
        const secret = document.getElementById('whSecret').value;
        
        const ts = Math.floor(Date.now() / 1000);
        const reqId = "req_" + uuidv4().split('-')[0];
        
        const payloadObj = {
            id: "evt_" + uuidv4().split('-')[1],
            event: eventName,
            created_at: new Date().toISOString(),
            data: {
                object: {
                    id: "pay_xyz",
                    amount: 5000,
                    status: eventName.split('.')[1]
                }
            }
        };
        
        const payloadStr = JSON.stringify(payloadObj, null, 2);
        const sigString = ts + "." + payloadStr;
        const hmac = await simHmac(secret, sigString);
        
        const output = `POST /your-webhook-endpoint HTTP/1.1
Host: your-domain.com
Content-Type: application/json
OriginPay-Signature: t=${ts},v1=${hmac}
OriginPay-Request-Id: ${reqId}

${payloadStr}`;

        document.getElementById('whOutput').innerText = output;
    }

    function copyWebhook() {
        const text = document.getElementById('whOutput').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Copiado!');
        });
    }

    // Init
    generateWebhook();
</script>
@endsection
