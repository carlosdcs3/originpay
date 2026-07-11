@extends('frontend.layouts.landing')
@section('title', 'Segurança — OriginPay')
@section('description', 'A arquitetura de segurança da OriginPay.')

@section('content')

<x-frontend.editorial-hero 
    title="Zero Trust Architecture." 
    subtitle="Nossa plataforma processa dados financeiros críticos. Operamos sob a premissa de que a rede está sempre hostil, exigindo criptografia e autenticação estrita em cada camada."
    breadcrumb="Institucional / Segurança" 
    align="center" />

<x-frontend.architecture-diagram 
    :nodes="[
        ['icon' => 'fas fa-globe', 'label' => 'Internet', 'sub' => 'Tráfego Público'],
        ['icon' => 'fas fa-shield-alt', 'label' => 'Cloud WAF', 'sub' => 'Filtragem DDoS (L7)'],
        ['icon' => 'fas fa-server', 'label' => 'API Gateway', 'sub' => 'Autenticação mTLS', 'highlight' => true],
        ['icon' => 'fas fa-microchip', 'label' => 'Payment Core', 'sub' => 'Isolamento Lógico'],
        ['icon' => 'fas fa-lock', 'label' => 'HSM', 'sub' => 'Storage Criptográfico']
    ]" />

<x-frontend.highlight-section 
    title="Segurança não é um feature." 
    desc="É a base estrutural do nosso código. Nenhuma requisição percorre os microsserviços internos sem validação rigorosa de assinatura." />

<x-frontend.content-section maxWidth="800px" padding="80px 20px">
    <h2>Isolamento e Controle</h2>
    <p>O ambiente onde ocorre o processamento do cartão de crédito (CDE) é fisicamente isolado dos nós da aplicação pública. Esse particionamento previne que vulnerabilidades em camadas externas interajam diretamente com o fluxo financeiro.</p>
    
    <h2>Monitoramento Contínuo</h2>
    <p>Telemetria e observabilidade em tempo real nos permitem agir proativamente. Comportamentos que desviam da baseline estatística são bloqueados automaticamente através de firewalls de aplicação configurados dinamicamente.</p>
</x-frontend.content-section>

<h3 style="font-size: 1.8rem; font-weight: 600; color: #fff; text-align: center; margin: 40px 0 0; background: var(--bg-deep); padding-top: 40px;">Padrões Criptográficos</h3>
<x-frontend.compliance-grid 
    :items="[
        [
            'icon' => 'fas fa-key', 
            'title' => 'Perfect Forward Secrecy', 
            'desc' => 'Toda comunicação via API é exigida em TLS 1.2+ garantindo confidencialidade efêmera.'
        ],
        [
            'icon' => 'fas fa-database', 
            'title' => 'Criptografia Simétrica', 
            'desc' => 'Dados sensíveis em repouso são protegidos utilizando AES-256 com chaves rotacionadas.'
        ],
        [
            'icon' => 'fas fa-fingerprint', 
            'title' => 'Autenticação Multissistema', 
            'desc' => 'Comunicação interna de backends via mTLS e JSON Web Tokens assinados.'
        ],
        [
            'icon' => 'fas fa-list-alt', 
            'title' => 'Auditoria Imutável', 
            'desc' => 'Logs de acesso e repasse financeiro utilizam estruturas append-only (sem deletes).'
        ]
    ]" />

<div style="background: var(--bg-panel); padding: 80px 20px;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h3 style="font-size: 1.8rem; font-weight: 600; color: #fff; margin-bottom: 32px; text-align: center;">Vulnerabilidades e Incidentes</h3>
        
        <x-frontend.faq-accordion question="Responsible Disclosure (Bug Bounty)">
            Nós valorizamos o ecossistema de segurança cibernética. Se você encontrou uma vulnerabilidade, nos envie os detalhes de forma confidencial. Reportes validados que evitem exposição pública e demonstrem impacto crítico serão recebidos positivamente pela engenharia. Contate: security@originpay.com
        </x-frontend.faq-accordion>
        
        <x-frontend.faq-accordion question="Plano de Resposta a Incidentes">
            Incidentes de degradação são automaticamente publicados na nossa página de Status. Operadores contam com SLAs para isolamento de nós infectados ou comprometidos, efetuando failover de forma transparente aos usuários.
        </x-frontend.faq-accordion>
    </div>
</div>

<x-frontend.cta-section 
    title="Ambiente Seguro" 
    subtitle="Confie na OriginPay para processar, custodiar e conciliar seus recebíveis." 
    buttonText="Fale com Vendas" 
    buttonUrl="{{ route('contato') }}" />

@endsection