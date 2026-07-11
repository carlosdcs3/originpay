@extends('frontend.layouts.landing')
@section('title', 'Sobre Nós — OriginPay')
@section('description', 'Conheça a história, a engenharia e os princípios da OriginPay.')

@section('content')

<x-frontend.editorial-hero 
    title="Infraestrutura Financeira." 
    subtitle="Construímos sistemas que movem capital de forma segura e escalável, removendo a complexidade técnica para que nossos clientes foquem no produto principal."
    breadcrumb="Institucional / Sobre nós" 
    align="left" />

<x-frontend.editorial-quote 
    quote="O código reflete a clareza do pensamento. Quando a infraestrutura falha, não é apenas um erro sistêmico, é uma interrupção no modelo de negócios do cliente."
    author="Engenharia OriginPay"
    role="Core Team" />

<x-frontend.highlight-section 
    title="O Desafio da Escala" 
    desc="A OriginPay nasceu de uma frustração técnica. As documentações do mercado estavam desatualizadas e as integrações eram frágeis. Construímos do zero a infraestrutura que nós mesmos queríamos utilizar: previsível, robusta e imutável." />

<x-frontend.metric-banner 
    metric="< 10ms" 
    label="Latência P99. Desenhado para operar em milissegundos, minimizando timeouts nas pontas e garantindo a conversão de pagamentos em momentos críticos de pico." />

<x-frontend.content-section maxWidth="800px" padding="80px 20px" style="text-align: left;">
    <h2>A História</h2>
    <p>Iniciamos construindo uma API limpa com um motor de risco preditivo. Nosso foco inicial foi resolver o problema de repasse (split) em marketplaces, que historicamente exigia conciliação manual propensa a falhas.</p>
    <p>Hoje, fornecemos a base transacional para operações complexas, operando sob uma filosofia de 'APIs como produto final'. Se a integração exige intervenção humana excessiva, consideramos que o design falhou em sua origem.</p>
</x-frontend.content-section>

<div style="background: var(--bg-surface); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 100px 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 64px; align-items: center;">
            <div style="text-align: left;">
                <h3 style="font-size: 2.2rem; font-weight: 700; color: #fff; margin-bottom: 24px; letter-spacing: -0.03em;">Princípios Fundamentais</h3>
                <div style="margin-bottom: 32px;">
                    <h4 style="font-size: 1.1rem; color: #fff; margin-bottom: 8px;">Confiabilidade acima de tudo</h4>
                    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">A disponibilidade não é uma meta secundária. Sistemas financeiros requerem isolamento estrutural para continuar operando mesmo sob falhas parciais de rede.</p>
                </div>
                <div style="margin-bottom: 32px;">
                    <h4 style="font-size: 1.1rem; color: #fff; margin-bottom: 8px;">Contratos Estritos</h4>
                    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">Nossas APIs não introduzem breaking changes silenciosos. O contrato OpenAPI reflete o que o servidor executa em produção, sem ambiguidades.</p>
                </div>
                <div>
                    <h4 style="font-size: 1.1rem; color: #fff; margin-bottom: 8px;">Segurança e Latência</h4>
                    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">Isolamento de dados criptográfico, registros append-only imutáveis para transações e latência otimizada. Menor latência significa menos abandono de carrinho.</p>
                </div>
            </div>
            
            <div style="background: #090B10; border: 1px solid var(--border); border-radius: 12px; padding: 24px; font-family: monospace; font-size: 0.85rem; color: #a5b4fc; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
                <div style="display: flex; gap: 6px; margin-bottom: 16px;">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></div>
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></div>
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></div>
                </div>
                <div style="color: var(--text-muted); margin-bottom: 12px;">$ op-cli infra check --region sa-east-1</div>
                <div style="margin-bottom: 4px;">> Validating core services... <span style="color: #10b981;">[OK]</span></div>
                <div style="margin-bottom: 4px;">> Checking cryptographic isolation... <span style="color: #10b981;">[VERIFIED]</span></div>
                <div style="margin-bottom: 4px;">> P99 Latency: <span style="color: #00e5c8;">8.4ms</span></div>
                <div style="margin-bottom: 4px;">> Active Replicas: 12 (Auto-scaling enabled)</div>
                <div style="margin-bottom: 12px;">> Database sync: Append-only strict mode <span style="color: #10b981;">[ACTIVE]</span></div>
                <div style="color: var(--text-muted);">> System ready to accept connections.</div>
            </div>
        </div>
    </div>
</div>

<x-frontend.cta-section 
    title="Construa com a OriginPay" 
    subtitle="Descubra por que as plataformas mais exigentes confiam em nossa infraestrutura." 
    buttonText="Criar conta Sandbox" 
    buttonUrl="{{ route('user.register') }}" />

@endsection