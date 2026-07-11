@extends('frontend.layouts.landing')
@section('title', 'Webhooks — OriginPay')
@section('description', 'Diretrizes de Webhooks da API da OriginPay.')

@section('content')

<div class="docs-header" style="padding: 80px 20px 40px; border-bottom: 1px solid var(--border); background: var(--bg-deep); text-align: center;">
    <div class="container">
        <div class="docs-breadcrumb" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px; font-weight: 500;">
            Desenvolvedores <span style="margin:0 8px;">/</span> Documentação <span style="margin:0 8px;">/</span> Webhooks
        </div>
        <h1 class="docs-title" style="font-size: 2.8rem; font-weight: 700; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em;">Webhooks</h1>
    </div>
</div>

<div class="docs-container" style="max-width: 1200px; margin: 0 auto; display: flex; gap: 40px; padding: 40px 20px; align-items: flex-start;">
    
    <x-frontend.sticky-toc :sections="[
        'intro' => 'Visão Geral', 
        'assinatura' => 'Verificação de Assinatura', 
        'retentativas' => 'Política de Retentativas'
    ]" />

    <main class="docs-main" style="max-width: 800px; flex-grow: 1;">
        <div class="docs-content">
            
            <x-frontend.legal-section id="intro" title="Visão Geral">
                <p>Os webhooks permitem que seu sistema seja notificado de forma assíncrona sobre eventos que ocorrem na OriginPay (como aprovação de um pagamento Pix, processamento de um estorno, ou recusa de cartão por risco). Você registra uma URL HTTPS válida no Dashboard para receber payloads JSON com os detalhes do evento.</p>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="assinatura" title="Verificação de Assinatura">
                <p>Para garantir que o webhook recebido foi originado exclusivamente pela OriginPay, enviamos o cabeçalho <code>X-OriginPay-Signature</code> em todas as requisições. O valor deste cabeçalho é um HMAC-SHA256 computado a partir do corpo bruto (raw payload) da requisição usando a chave secreta do seu webhook configurado.</p>
                <pre style="background: #0f172a; padding: 24px; border-radius: 12px; border: 1px solid var(--border); color: #f8fafc; font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; overflow-x: auto; margin: 24px 0;"><code>// Exemplo PHP de validação HMAC
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_ORIGINPAY_SIGNATURE'];
$secret = 'wh_secret_xyz';

$hash = hash_hmac('sha256', $payload, $secret);
if (hash_equals($hash, $signature)) {
    // A requisição é autêntica. Proceder com o fluxo.
}</code></pre>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="retentativas" title="Política de Retentativas">
                <p>Caso o seu servidor não responda com um status code HTTP 2xx (por exemplo, retornando 500 Internal Server Error ou ocorrendo um timeout), o motor de webhooks iniciará a rotina de retentativas. O payload será reenviado usando um algoritmo de exponential backoff, garantindo a entrega do evento sem causar negação de serviço na sua infraestrutura.</p>
            </x-frontend.legal-section>

        </div>
    </main>
</div>

@endsection