@extends('frontend.layouts.api_reference')

@section('title', $endpointTitle)

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.v1.api_reference.index') }}">API Reference</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>Core</span>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>{{ $endpointTitle }}</span>
    </div>

    <h1>{{ $endpointTitle }}</h1>
    <p class="lead" style="margin-bottom: 32px;">
        Cria um objeto Customer. Útil para gerenciar dados de clientes e reutilizá-los em pagamentos recorrentes ou para associar cartões salvos.
    </p>

    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 48px; border-bottom: 1px solid var(--doc-border); padding-bottom: 16px;">
        <span class="doc-badge doc-badge-post">POST</span>
        <code style="background: none; font-size: 1rem; color: #fff;">/v1/customers</code>
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
                    <strong style="color: #fff; font-family: monospace;">name</strong>
                    <span style="color: #f43f5e; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Required</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">string</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">Nome completo do cliente.</p>
            </div>
        </div>

        <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 0;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <strong style="color: #fff; font-family: monospace;">email</strong>
                    <span style="color: #f43f5e; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Required</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">string</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">O e-mail do cliente.</p>
            </div>
        </div>
        
        <div style="display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 0;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <strong style="color: #fff; font-family: monospace;">document</strong>
                    <span style="color: var(--doc-muted); font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Optional</span>
                </div>
                <div style="color: var(--doc-muted); font-size: 0.85rem;">string</div>
                <p style="margin-top: 8px; font-size: 0.9rem;">CPF ou CNPJ do cliente.</p>
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
<pre style="color: #a1a1aa; font-size: 0.75rem; line-height: 1.5;">curl -X POST https://api.originpay.com/v1/customers \
  -H "Authorization: Bearer sk_test_xxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Maria Silva",
    "email": "maria@exemplo.com",
    "document": "22222222222"
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
    "id": "cus_m20199s1k",
    "name": "Maria Silva",
    "email": "maria@exemplo.com",
    "document": "22222222222",
    "created_at": "2026-06-25T15:30:00Z"
  }
}</pre>
            </div>
        </div>
    </div>
@endsection
