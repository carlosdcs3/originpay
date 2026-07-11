@extends('frontend.layouts.api_reference')

@section('title', 'API Reference')

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>API Reference v1</span>
    </div>

    <h1>API Reference</h1>
    <p class="lead" style="margin-bottom: 48px;">
        Bem-vindo à documentação oficial da API da OriginPay. A nossa API é organizada em torno do padrão REST. Ela tem URLs previsíveis orientadas a recursos, aceita corpos de requisição form-encoded e JSON, retorna respostas em formato JSON e usa códigos de resposta HTTP padrão.
    </p>

    <h2 id="base-url">Base URL</h2>
    <p>Todas as requisições para a API devem ser feitas para a seguinte URL base:</p>
    <div class="doc-code-block" style="margin-bottom: 48px;">
        <div class="doc-code-content" style="padding: 16px 24px;">
<pre style="color: #fff; font-size: 0.95rem;">https://api.originpay.com/v1</pre>
        </div>
    </div>

    <h2 id="customer-subscriptions">Customer Subscriptions</h2>
    <p>O MVP de assinaturas recorrentes merchant-facing está documentado nos arquivos técnicos do projeto:</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 48px;">
        <li><code>docs/customer-subscriptions-mvp.md</code></li>
        <li><code>docs/customer-subscriptions-final-report.md</code></li>
    </ul>

    <h2 id="authentication">Autenticação</h2>
    <p>A API da OriginPay usa chaves de API para autenticar requisições. Você pode ver e gerenciar suas chaves de API no <a href="{{ route('user.dashboard') }}" style="color: var(--doc-primary); text-decoration: underline;">Dashboard</a>.</p>
    <p>A autenticação da API deve ser enviada através de Basic Auth (passando a chave como Username e deixando a senha em branco) ou através de HTTP Bearer Authentication.</p>
    
    <div class="doc-alert doc-alert-important">
        <i data-lucide="shield-alert"></i>
        <div>
            <strong>Mantenha suas chaves seguras</strong>
            <p>Suas chaves de API carregam muitos privilégios, não as compartilhe em locais publicamente acessíveis (como GitHub ou código frontend não criptografado).</p>
        </div>
    </div>

    <h2 id="errors">Erros</h3>
    <p>A OriginPay usa códigos de resposta HTTP convencionais para indicar sucesso ou falha na requisição.</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>Códigos no intervalo <code>2xx</code> indicam sucesso.</li>
        <li>Códigos no intervalo <code>4xx</code> indicam um erro decorrente das informações fornecidas (ex: parâmetro ausente, saldo insuficiente).</li>
        <li>Códigos no intervalo <code>5xx</code> indicam um erro nos servidores da OriginPay (são raros).</li>
    </ul>

    <div class="doc-pagination">
        <div></div>
        <a href="{{ route('docs.v1.api_reference.show', 'create-payment') }}" class="doc-pagination-link next">
            <span class="dir">Next</span>
            <span class="title">Create a Payment <i data-lucide="arrow-right" style="width: 16px;"></i></span>
        </a>
    </div>
@endsection

@section('code_panel')
    <h3 style="color: #fff; font-size: 0.85rem; margin-top: 0; text-transform: uppercase; letter-spacing: 0.05em;">Autenticação via cURL</h3>
    <div class="doc-code-block" style="border-color: rgba(255,255,255,0.1); background: transparent;">
        <div class="doc-code-content" style="padding: 16px;">
<pre style="color: #a1a1aa; font-size: 0.8rem; line-height: 1.6;">curl https://api.originpay.com/v1/balance \
  -H "Authorization: Bearer sk_test_xxxxxxxxx"</pre>
        </div>
    </div>
@endsection
