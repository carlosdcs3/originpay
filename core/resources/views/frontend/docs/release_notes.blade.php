@extends('frontend.layouts.api_reference')

@section('title', 'Release Notes')

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.v1.api_reference.index') }}">API Reference</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>Release Notes</span>
    </div>

    <h1>Release Notes</h1>
    <p class="lead" style="margin-bottom: 48px;">
        Acompanhe as atualizações da API da OriginPay, correções de bugs, novas funcionalidades e anúncios técnicos diretamente nesta página.
    </p>

    <div style="border-left: 2px solid var(--doc-border); padding-left: 24px; position: relative;">
        <!-- Timeline Item -->
        <div style="margin-bottom: 40px; position: relative;">
            <div style="position: absolute; left: -31px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: var(--doc-surface); border: 2px solid var(--doc-primary);"></div>
            <div style="color: var(--doc-muted); font-size: 0.85rem; font-family: monospace; margin-bottom: 8px;">25 de Junho, 2026</div>
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 1.2rem;">Lançamento da API Reference Enterprise</h3>
            
            <div style="margin-bottom: 16px;">
                <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-right: 8px;">Added</span>
                <span style="color: #fff; font-size: 0.95rem;">Suporte oficial para o endpoint <code>/v1/payments</code> com roteamento dinâmico.</span>
            </div>
            
            <div style="margin-bottom: 16px;">
                <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-right: 8px;">Added</span>
                <span style="color: #fff; font-size: 0.95rem;">Novo Webhook Simulator para testar cargas locais e validação HMAC.</span>
            </div>

            <div style="margin-bottom: 16px;">
                <span style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-right: 8px;">Changed</span>
                <span style="color: #fff; font-size: 0.95rem;">Reestruturação completa da documentação para suportar múltiplos ambientes (v1).</span>
            </div>
        </div>
        
        <!-- Timeline Item -->
        <div style="position: relative;">
            <div style="position: absolute; left: -31px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: var(--doc-surface); border: 2px solid var(--doc-muted);"></div>
            <div style="color: var(--doc-muted); font-size: 0.85rem; font-family: monospace; margin-bottom: 8px;">10 de Maio, 2026</div>
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 1.2rem;">Lançamento Beta do Sandbox</h3>
            
            <div style="margin-bottom: 16px;">
                <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-right: 8px;">Added</span>
                <span style="color: #fff; font-size: 0.95rem;">Criação de chaves <code>sk_test_...</code> para simulação de PIX via Dashboard.</span>
            </div>
        </div>
    </div>

@endsection
