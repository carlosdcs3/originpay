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
        Recupera o saldo atual da sua conta OriginPay. O saldo é retornado em centavos e separado por saldo disponível (pronto para saque) e saldo pendente (agendado/bloqueado).
    </p>

    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 48px; border-bottom: 1px solid var(--doc-border); padding-bottom: 16px;">
        <span class="doc-badge doc-badge-get">GET</span>
        <code style="background: none; font-size: 1rem; color: #fff;">/v1/balance</code>
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
<pre style="color: #a1a1aa; font-size: 0.75rem; line-height: 1.5;">curl -X GET https://api.originpay.com/v1/balance \
  -H "Authorization: Bearer sk_test_xxxxxxxxx"</pre>
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
    "available": 150000,
    "pending": 25000,
    "currency": "BRL"
  }
}</pre>
            </div>
        </div>
    </div>
@endsection
