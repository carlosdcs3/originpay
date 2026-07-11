@extends('frontend.layouts.landing')
@section('title', 'Autenticação API — OriginPay')
@section('description', 'Diretrizes de autenticação da API da OriginPay.')

@section('content')

<div class="docs-header" style="padding: 80px 20px 40px; border-bottom: 1px solid var(--border); background: var(--bg-deep); text-align: center;">
    <div class="container">
        <div class="docs-breadcrumb" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px; font-weight: 500;">
            Desenvolvedores <span style="margin:0 8px;">/</span> Documentação <span style="margin:0 8px;">/</span> Autenticação
        </div>
        <h1 class="docs-title" style="font-size: 2.8rem; font-weight: 700; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em;">Autenticação API</h1>
    </div>
</div>

<div class="docs-container" style="max-width: 1200px; margin: 0 auto; display: flex; gap: 40px; padding: 40px 20px; align-items: flex-start;">
    
    <x-frontend.sticky-toc :sections="[
        'intro' => 'Visão Geral', 
        'tipos' => 'Tipos de Chaves', 
        'exemplo' => 'Autenticando uma Requisição'
    ]" />

    <main class="docs-main" style="max-width: 800px; flex-grow: 1;">
        <div class="docs-content">
            
            <x-frontend.legal-section id="intro" title="Visão Geral">
                <p>A API da OriginPay utiliza a autenticação padrão via HTTP Bearer Token. Todas as interações com nossos endpoints REST exigem conexão segura (HTTPS). Qualquer tentativa de chamada via protocolo HTTP não criptografado será imediatamente recusada em nível de rede para garantir a integridade dos dados.</p>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="tipos" title="Tipos de Chaves">
                <p>Sua conta possui dois conjuntos principais de chaves, separados por ambiente (Test e Live):</p>
                <ul>
                    <li><strong>Secret Keys (sk_):</strong> Têm controle total e permissões de escrita sobre a conta. Devem ser armazenadas apenas nos servidores de backend. Se uma chave secreta for comprometida, ela deve ser revogada imediatamente no Dashboard.</li>
                    <li><strong>Public Keys (pk_):</strong> Utilizadas exclusivamente em aplicações client-side (navegadores web e aplicativos móveis) para a geração de tokens seguros de cartão, impedindo que os dados brutos trafeguem pelos seus servidores.</li>
                </ul>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="exemplo" title="Autenticando uma Requisição">
                <p>Envie sua chave secreta no cabeçalho <code>Authorization</code> da requisição HTTP.</p>
                <pre style="background: #0f172a; padding: 24px; border-radius: 12px; border: 1px solid var(--border); color: #f8fafc; font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; overflow-x: auto; margin: 24px 0;"><code>curl -X GET https://api.originpay.com/v1/balance \
  -H "Authorization: Bearer sk_test_1234567890abcdef" \
  -H "Content-Type: application/json"</code></pre>
                <p>Se a chave for inválida ou não for informada, o servidor retornará um status HTTP <code>401 Unauthorized</code>.</p>
            </x-frontend.legal-section>

        </div>
    </main>
</div>

@endsection