@extends('frontend.layouts.landing')
@section('title', 'Conformidade LGPD — OriginPay')
@section('description', 'Diretrizes oficiais da OriginPay sobre a LGPD.')

@section('content')

<div class="docs-header" style="padding: 80px 20px 40px; border-bottom: 1px solid var(--border); background: var(--bg-deep); text-align: center;">
    <div class="container">
        <div class="docs-breadcrumb" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px; font-weight: 500;">
            Institucional <span style="margin:0 8px;">/</span> Conformidade LGPD
        </div>
        <h1 class="docs-title" style="font-size: 2.8rem; font-weight: 700; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em;">Conformidade LGPD</h1>
        <div class="docs-meta" style="display: flex; gap: 24px; justify-content: center; color: var(--text-muted); font-size: 0.95rem;">
            <div class="docs-meta-item"><i class="fas fa-clock"></i> 3 min de leitura</div>
            <div class="docs-meta-item"><i class="far fa-calendar-alt"></i> Vigência: {{ date('F \d\e Y') }}</div>
        </div>
    </div>
</div>

<div class="docs-container" style="max-width: 1200px; margin: 0 auto; display: flex; gap: 40px; padding: 40px 20px; align-items: flex-start;">
    
    <x-frontend.sticky-toc :sections="[
        'direitos' => '1. Direitos do Titular', 
        'bases' => '2. Bases Legais de Tratamento', 
        'exercicio' => '3. Exercício de Direitos',
        'retencao' => '4. Retenção',
        'contato' => '5. Contato do DPO'
    ]" />

    <main class="docs-main" style="max-width: 720px; flex-grow: 1;">
        <div class="docs-content">
            
            <x-frontend.legal-section id="direitos" title="1. Direitos do Titular">
                <x-frontend.highlight-box>Os titulares de dados podem solicitar portabilidade, anonimização ou revogação de consentimento contatando diretamente nosso DPO.</x-frontend.highlight-box>
                <p>A OriginPay garante todos os direitos estabelecidos pela Lei Geral de Proteção de Dados Pessoais (Lei nº 13.709/2018). Os titulares podem requerer, a qualquer momento: o acesso aos dados armazenados, a anonimização de informações desnecessárias, a correção de dados inexatos, a revogação do consentimento (quando aplicável) e a portabilidade sistêmica dos dados.</p>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="bases" title="2. Bases Legais de Tratamento">
                <p>As informações são processadas fundamentadas juridicamente em:</p>
                <ul>
                    <li><strong>Execução de Contrato:</strong> Para processamento de pagamentos originados via API e transferências para a conta bancária domicílio.</li>
                    <li><strong>Obrigação Legal ou Regulatória:</strong> Armazenamento de logs sistêmicos e dados financeiros exigidos por órgãos competentes.</li>
                    <li><strong>Legítimo Interesse:</strong> Execução de inteligência antifraude para mitigar riscos na nossa infraestrutura e proteger o ecossistema de pagamentos.</li>
                </ul>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="exercicio" title="3. Exercício de Direitos">
                <p>Solicitações de titulares de dados referentes aos portadores de cartão deverão ser primariamente direcionadas ao lojista. A OriginPay, na qualidade de operadora ou controladora conjunta na prevenção à fraude, responderá às requisições oficiais conforme os fluxos sistêmicos definidos.</p>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="retencao" title="4. Retenção">
                <p>Dados estritamente financeiros são armazenados por 5 anos, por força do Marco Civil da Internet e do Banco Central do Brasil. Após este período, se não houver outra justificativa legal, os dados são sistematicamente destruídos e/ou completamente anonimizados do nosso Data Warehouse.</p>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="contato" title="5. Contato do DPO">
                <p>Nosso Encarregado de Proteção de Dados (Data Protection Officer) está à disposição para dúvidas ou exercícios de direitos formais.</p>
                <p><a href="mailto:dpo@originpay.com">dpo@originpay.com</a></p>
            </x-frontend.legal-section>

        </div>
    </main>
</div>

@endsection