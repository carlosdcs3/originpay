@extends('frontend.layouts.landing')
@section('title', 'Contato — OriginPay')
@section('description', 'Fale com nosso time de vendas ou suporte técnico.')

@section('content')

<x-frontend.editorial-hero 
    title="Fale com a OriginPay." 
    subtitle="Estamos estruturados para apoiar operações complexas. Fale diretamente com o time de engenharia para integrações ou com o nosso setor comercial."
    breadcrumb="Institucional / Contato" 
    align="left" />

<div style="background: var(--bg-surface); padding-bottom: 100px;">
    <div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 48px; margin-top: 64px;">
            
            <!-- Terminal Card: Engenharia -->
            <div style="background: #090B10; border: 1px solid var(--border); border-radius: 12px; padding: 40px; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
                <div style="display: flex; gap: 6px; margin-bottom: 24px;">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></div>
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></div>
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></div>
                </div>
                <h3 style="font-size: 1.6rem; font-weight: 600; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em; font-family: monospace;">> Engenharia e Integração_</h3>
                <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; flex-grow: 1;">Canal dedicado à resolução técnica de chamados. Acesso direto a engenheiros L3 para plataformas ativas enfrentando problemas na comunicação com nossa API de liquidação.</p>
                <div style="margin-top: 40px;">
                    <span style="color: #10b981; font-family: monospace; font-size: 0.9rem; display: block; margin-bottom: 12px;">[Status: Online] Resposta < 15 min</span>
                    <a href="{{ route('user.login') }}" class="btn btn-primary" style="padding: 12px 24px; font-family: monospace;">$ connect --support</a>
                </div>
            </div>

            <!-- Corporate Card: Vendas -->
            <div style="background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, transparent 100%); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 40px; display: flex; flex-direction: column;">
                <i class="fas fa-handshake mb-4" style="font-size: 2.2rem; color: var(--primary);"></i>
                <h3 style="font-size: 1.6rem; font-weight: 600; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em;">Vendas (Enterprise)</h3>
                <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; flex-grow: 1;">A OriginPay não cobra taxas percentuais altas para grandes clientes. Estruture SLAs personalizados e negocie volumes transacionais elevados com os nossos Key Account Managers.</p>
                <a href="mailto:sales@originpay.com" class="btn btn-secondary" style="margin-top: 40px; align-self: flex-start; padding: 12px 24px;">Falar com Vendas <i class="fas fa-arrow-right"></i></a>
            </div>
            
        </div>
    </div>
</div>

<x-frontend.metric-banner 
    metric="< 24h" 
    label="Tempo máximo de resposta comercial. Chamados de infraestrutura para contas ativas possuem priorização P1 com resposta imediata." />

<div style="background: var(--bg-panel); padding: 80px 20px;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h3 style="font-size: 1.8rem; font-weight: 600; color: #fff; margin-bottom: 32px; text-align: center;">Dúvidas Frequentes (FAQ)</h3>
        
        <x-frontend.faq-accordion question="A API Sandbox é totalmente gratuita?">
            O ambiente Sandbox da OriginPay é aberto. Nenhum cartão de crédito é exigido e não há taxas para simular pagamentos com as chaves `sk_test`.
        </x-frontend.faq-accordion>
        
        <x-frontend.faq-accordion question="Sou um parceiro/imprensa. Onde entro em contato?">
            Para solicitações institucionais de imprensa, marketing ou parcerias acadêmicas, encaminhe suas solicitações para: press@originpay.com.
        </x-frontend.faq-accordion>
    </div>
</div>

@endsection