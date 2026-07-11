@extends('frontend.layouts.landing')
@section('title', 'Ecossistema — OriginPay')
@section('description', 'A plataforma unificada para construir fluxos de pagamentos.')

@section('content')

<x-frontend.editorial-hero 
    title="Infraestrutura unificada." 
    subtitle="Substitua múltiplos provedores por uma plataforma focada em escala e robustez. APIs, Split e Antifraude em um único contrato."
    breadcrumb="Produto / Ecossistema" />

<div style="background: var(--bg-deep); padding-bottom: 80px;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <x-frontend.feature-grid columns="2">
            <x-frontend.feature-item 
                icon="fas fa-exchange-alt" 
                title="Motor de Split B2B2C" 
                desc="Repasse automático de comissões. Defina regras de split percentuais ou fixas diretamente na criação da transação pela API, sem conciliação manual." />
            
            <x-frontend.feature-item 
                icon="fas fa-link" 
                title="Links e Assinaturas" 
                desc="Crie fluxos recorrentes para SaaS ou gere faturas pontuais via dashboard de forma nativa. Suporte a cobranças prorratadas e trials." />
                
            <x-frontend.feature-item 
                icon="fas fa-shield-alt" 
                title="Análise de Risco" 
                desc="Modelo preditivo que avalia o risco de cada requisição. Bloqueia comportamentos anômalos e tentativas de fraude em milissegundos sem adicionar fricção ao usuário legítimo." />
                
            <x-frontend.feature-item 
                icon="fas fa-chart-line" 
                title="Conciliação Financeira" 
                desc="Saiba exatamente quais tarifas foram descontadas. Exportação detalhada de extratos D+1 para seu ERP ou conciliação automatizada via API." />
        </x-frontend.feature-grid>
    </div>
</div>

<x-frontend.cta-section 
    title="Comece a construir" 
    subtitle="Crie sua conta Sandbox e tenha acesso à API em menos de um minuto." 
    buttonText="Acessar Dashboard" 
    buttonUrl="{{ route('user.login') }}" />

@endsection