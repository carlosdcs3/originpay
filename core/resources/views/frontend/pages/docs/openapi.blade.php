@extends('frontend.layouts.landing')
@section('title', 'Especificação OpenAPI — OriginPay')
@section('description', 'Integração tipada e contrato OpenAPI da OriginPay.')

@section('content')

<div class="docs-header" style="padding: 80px 20px 40px; border-bottom: 1px solid var(--border); background: var(--bg-deep); text-align: center;">
    <div class="container">
        <div class="docs-breadcrumb" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px; font-weight: 500;">
            Desenvolvedores <span style="margin:0 8px;">/</span> Documentação <span style="margin:0 8px;">/</span> OpenAPI
        </div>
        <h1 class="docs-title" style="font-size: 2.8rem; font-weight: 700; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em;">Especificação OpenAPI</h1>
    </div>
</div>

<div class="docs-container" style="max-width: 1200px; margin: 0 auto; display: flex; gap: 40px; padding: 40px 20px; align-items: flex-start;">
    
    <x-frontend.sticky-toc :sections="[
        'intro' => 'Integração Tipada',
        'download' => 'Arquivo da Especificação'
    ]" />

    <main class="docs-main" style="max-width: 800px; flex-grow: 1;">
        <div class="docs-content">
            
            <x-frontend.legal-section id="intro" title="Integração Tipada">
                <p>A documentação interativa baseada em OpenAPI permite explorar os endpoints da OriginPay, conhecer as estruturas aceitas e acelerar a geração de SDKs a partir do contrato oficial da API.</p>
            </x-frontend.legal-section>

            <x-frontend.legal-section id="download" title="Arquivo da Especificação">
                <p>O arquivo YAML público ainda não está disponível para download nesta área. Quando a exportação oficial for habilitada, ele aparecerá aqui com versionamento e acesso direto.</p>
                <div style="margin-top: 24px;">
                    <span class="btn btn-outline-secondary disabled" aria-disabled="true">Arquivo OpenAPI ainda indisponível</span>
                </div>
            </x-frontend.legal-section>

        </div>
    </main>
</div>

@endsection
