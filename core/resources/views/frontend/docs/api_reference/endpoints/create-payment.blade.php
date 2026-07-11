@extends('frontend.layouts.api_reference')

@section('title', $endpointTitle)

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.v1.api_reference.index') }}">API Reference</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>Payments</span>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>{{ $endpointTitle }}</span>
    </div>

    <h1>{{ $endpointTitle }}</h1>
    <p class="lead" style="margin-bottom: 32px;">
        Cria uma nova intenção de pagamento. Após a criação, se o método for PIX, um QR Code será retornado. Se o método for cartão, a transação será processada (autorizada e capturada por padrão).
    </p>

    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 48px; border-bottom: 1px solid var(--doc-border); padding-bottom: 16px;">
        <span class="doc-badge doc-badge-post">POST</span>
        <code style="background: none; font-size: 1rem; color: #fff;">/v1/payments</code>
    </div>

    <h2 id="authentication">Authentication</h2>
    <div class="doc-table-wrap">
        <table class="doc-table">
            <tbody>
                <tr>
                    <td style="width: 150px; color: #fff; font-weight: 500;">Bearer Token</td>
                    <td style="color: var(--doc-muted);">Required. Ex: <code>Authorization: Bearer sk_test_...</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 id="body-parameters">Body Parameters</h2>
    
    <div style="margin-top: 24px;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 0;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <strong style="color: #fff; font-family: monospace;">amount</strong>
                    <span style="color: #f43f5e; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Required</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">integer</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">Valor do pagamento em centavos (ex: <code>10000</code> para R$ 100,00).</p>
            </div>
        </div>

        <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 0;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <strong style="color: #fff; font-family: monospace;">method</strong>
                    <span style="color: #f43f5e; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Required</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">string</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">O método de pagamento a ser utilizado. Valores suportados: <code>pix</code>, <code>credit_card</code>.</p>
            </div>
        </div>

        <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 0;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <strong style="color: #fff; font-family: monospace;">customer</strong>
                    <span style="color: #f43f5e; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Required</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">object</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">Dados do cliente realizando o pagamento.</p>
                <div style="margin-left: 24px; margin-top: 12px; border-left: 2px solid var(--doc-border); padding-left: 16px;">
                    <div style="margin-bottom: 8px;"><strong style="font-family: monospace; color: #cbd5e1;">name</strong> <span style="font-size: 0.8rem; color: var(--doc-muted);">string</span></div>
                    <div style="margin-bottom: 8px;"><strong style="font-family: monospace; color: #cbd5e1;">email</strong> <span style="font-size: 0.8rem; color: var(--doc-muted);">string</span></div>
                    <div style="margin-bottom: 8px;"><strong style="font-family: monospace; color: #cbd5e1;">document</strong> <span style="font-size: 0.8rem; color: var(--doc-muted);">string (CPF/CNPJ)</span></div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 0;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <strong style="color: #fff; font-family: monospace;">card_token</strong>
                    <span style="color: var(--doc-muted); font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Optional</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">string</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">Obrigatório se <code>method</code> for <code>credit_card</code>. O token gerado pelo SDK frontend contendo os dados sensíveis (PCI) criptografados.</p>
            </div>
        </div>
    </div>
@endsection

@section('code_panel')
    <div style="margin-bottom: 32px;">
        <h3 style="color: #fff; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Request Example</h3>
        <div class="doc-code-block" style="margin: 0; background: #000; border-color: rgba(255,255,255,0.1);">
            <div class="doc-code-header" style="background: transparent; border-color: rgba(255,255,255,0.05);">
                <div class="doc-code-tabs">
                    <div class="doc-code-tab active">cURL</div>
                </div>
            </div>
            <div class="doc-code-content" style="padding: 16px;">
<pre style="color: #a1a1aa; font-size: 0.75rem; line-height: 1.5;">curl -X POST https://api.originpay.com/v1/payments \
  -H "Authorization: Bearer sk_test_xxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: uuid-v4" \
  -d '{
    "amount": 10000,
    "method": "pix",
    "customer": {
      "name": "João Silva",
      "email": "joao@exemplo.com",
      "document": "11111111111"
    }
  }'</pre>
            </div>
        </div>
    </div>

    <div>
        <h3 style="color: #fff; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Response Example</h3>
        <div class="doc-code-block" style="margin: 0; background: #000; border-color: rgba(255,255,255,0.1);">
            <div class="doc-code-header" style="background: transparent; border-color: rgba(255,255,255,0.05);">
                <div class="doc-code-tabs">
                    <div class="doc-code-tab active">200 OK</div>
                </div>
            </div>
            <div class="doc-code-content" style="padding: 16px;">
<pre style="color: #a1a1aa; font-size: 0.75rem; line-height: 1.5;">{
  "status": "success",
  "request_id": "req_88jjs8k",
  "data": {
    "id": "pay_59d8s21k",
    "amount": 10000,
    "status": "pending",
    "method": "pix",
    "qr_code": {
      "payload": "00020101021226...",
      "image_base64": "iVBORw0KGgo..."
    },
    "created_at": "2026-06-25T14:30:00Z"
  }
}</pre>
            </div>
        </div>
    </div>
@endsection
