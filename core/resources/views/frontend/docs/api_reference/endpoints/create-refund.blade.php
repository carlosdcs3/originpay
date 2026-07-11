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
        Realiza o estorno de um pagamento que já foi capturado. O estorno pode ser total ou parcial.
    </p>

    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 48px; border-bottom: 1px solid var(--doc-border); padding-bottom: 16px;">
        <span class="doc-badge doc-badge-post">POST</span>
        <code style="background: none; font-size: 1rem; color: #fff;">/v1/refunds</code>
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
                    <strong style="color: #fff; font-family: monospace;">payment_id</strong>
                    <span style="color: #f43f5e; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Required</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">string</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">O identificador único do pagamento a ser estornado.</p>
            </div>
        </div>

        <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 0;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <strong style="color: #fff; font-family: monospace;">amount</strong>
                    <span style="color: var(--doc-muted); font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Optional</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">integer</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">O valor do estorno em centavos. Se omitido, o valor total do pagamento será estornado. Não pode ser maior que o valor do pagamento original.</p>
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
<pre style="color: #a1a1aa; font-size: 0.75rem; line-height: 1.5;">curl -X POST https://api.originpay.com/v1/refunds \
  -H "Authorization: Bearer sk_test_xxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: uuid-v4" \
  -d '{
    "payment_id": "pay_59d8s21k",
    "amount": 5000
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
  "data": {
    "id": "ref_9j2ks11",
    "payment_id": "pay_59d8s21k",
    "amount": 5000,
    "status": "processed",
    "created_at": "2026-06-25T14:40:00Z"
  }
}</pre>
            </div>
        </div>
    </div>
@endsection
