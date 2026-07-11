@extends('frontend.layouts.landing')
@section('title', 'Documentação — OriginPay')
@section('description', 'Guias, Referências e Ferramentas para integração com a API.')

@section('content')

<x-frontend.editorial-hero 
    title="Documentação" 
    subtitle="Guias, Referências e Ferramentas para integração com a plataforma OriginPay."
    breadcrumb="Desenvolvedores / Documentação" />

<div style="background: var(--bg-deep); min-height: 50vh; padding-bottom: 80px;">
    <div style="max-width: 1000px; margin: 0 auto; padding: 40px 20px;">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <a href="{{ route('docs.auth') }}" style="text-decoration: none;">
                    <x-frontend.contact-card 
                        icon="fas fa-key"
                        title="Autenticação"
                        subtitle="Implemente tokens Bearer para requisições seguras à API REST."
                        actionLabel="Ler guia"
                        actionUrl="{{ route('docs.auth') }}" />
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('docs.webhooks') }}" style="text-decoration: none;">
                    <x-frontend.contact-card 
                        icon="fas fa-satellite-dish"
                        title="Webhooks"
                        subtitle="Receba postbacks HTTP em tempo real no seu servidor."
                        actionLabel="Ler guia"
                        actionUrl="{{ route('docs.webhooks') }}" />
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('docs.openapi') }}" style="text-decoration: none;">
                    <x-frontend.contact-card 
                        icon="fas fa-code"
                        title="OpenAPI"
                        subtitle="Explore o contrato YAML para construir seus clients."
                        actionLabel="Acessar"
                        actionUrl="{{ route('docs.openapi') }}" />
                </a>
            </div>
        </div>
        
        <div style="background: rgba(124, 58, 237, 0.02); border: 1px solid rgba(124, 58, 237, 0.3); border-radius: 12px; padding: 40px; text-align: center;">
            <h3 style="font-size: 1.5rem; font-weight: 600; color: #fff; margin-bottom: 16px;">Modo Sandbox</h3>
            <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto 32px;">Utilize as chaves de teste (`sk_test`) para simular aprovações e recusas de pagamentos isoladamente do ambiente Live.</p>
            <a href="{{ route('user.login') }}" class="btn btn-primary">Acessar Painel do Sandbox</a>
        </div>
    </div>
</div>

@endsection