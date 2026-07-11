@extends('frontend.layouts.landing')
@section('title', 'Changelog — OriginPay')
@section('description', 'Registro centralizado de atualizações do motor de processamento.')

@section('content')

<x-frontend.editorial-hero 
    title="Changelog" 
    subtitle="Registro centralizado de atualizações do motor de processamento da OriginPay."
    breadcrumb="Produto / Changelog" />

<x-frontend.content-section>
    <x-frontend.timeline 
        :items="[
            [
                'date' => date('F Y'),
                'title' => 'v1.0.0 — Estabilidade de Core',
                'content' => '
                    <p style=\"margin-bottom: 32px;\">O lançamento oficial do core de liquidação v1.0 focado na estabilização dos serviços e encerramento da fase beta. O ambiente Live passa a operar com contratos completos de SLA.</p>
                    <div style=\"background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; padding: 24px;\">
                        <h4 style=\"font-size: 1.1rem; color: #fff; margin-bottom: 16px;\"><i class=\"fas fa-rocket text-primary mr-2\"></i> Melhorias Entregues</h4>
                        <ul style=\"margin: 0; padding-left: 20px; line-height: 1.8;\">
                            <li>Dashboard de gerenciamento portado para arquitetura SPA com atualizações real-time sobre transações financeiras.</li>
                            <li>Disponibilização da API REST v1 englobando suporte completo ao fluxo de Checkout Transparente.</li>
                            <li>Otimização do cluster de webhooks, garantindo escalabilidade em entregas assíncronas.</li>
                        </ul>
                    </div>
                '
            ]
        ]" 
    />
</x-frontend.content-section>

@endsection