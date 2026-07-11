@extends('frontend.layouts.landing')
@section('title', 'Preços — OriginPay')
@section('description', 'Precificação dimensionada para as operações da sua plataforma.')

@section('content')

<x-frontend.editorial-hero 
    title="Custos transparentes." 
    subtitle="Precificação dimensionada para as operações da sua plataforma financeira, sem taxas surpresas."
    breadcrumb="Produto / Preços" />

<div style="background: var(--bg-deep); padding: 40px 0 80px;">
    <div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
        <div class="row g-4 justify-content-center">
            
            <!-- Start Plan -->
            <div class="col-lg-6">
                <div style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; padding: 48px 40px; height: 100%; display: flex; flex-direction: column;">
                    <h3 style="font-size: 1.8rem; font-weight: 600; color: #fff; margin-bottom: 8px;">Start</h3>
                    <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 32px;">Taxas nativas para volumes padrão.</p>
                    
                    <div style="border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 24px 0; margin-bottom: 32px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                            <span style="color: #fff; font-size: 1.1rem;">Liquidação PIX</span>
                            <span style="color: var(--primary); font-weight: 600; font-size: 1.1rem;">0.99%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                            <span style="color: #fff; font-size: 1.1rem;">Cartão de Crédito</span>
                            <span style="color: var(--primary); font-weight: 600; font-size: 1.1rem;">3.99%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted); font-size: 1.1rem;">Mensalidade</span>
                            <span style="color: #fff; font-weight: 600; font-size: 1.1rem;">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <ul style="list-style: none; padding: 0; margin: 0 0 40px; color: var(--text-muted); font-size: 1.05rem; line-height: 2;">
                        <li><i class="fas fa-check text-primary mr-3" style="width: 16px;"></i> Acesso total à API REST</li>
                        <li><i class="fas fa-check text-primary mr-3" style="width: 16px;"></i> Dashboard de gestão de recebíveis</li>
                        <li><i class="fas fa-check text-primary mr-3" style="width: 16px;"></i> Suporte padronizado</li>
                    </ul>
                    
                    <a href="{{ route('user.register') }}" class="btn btn-outline-secondary w-100" style="padding: 14px; margin-top: auto; font-size: 1.1rem;">Começar agora</a>
                </div>
            </div>

            <!-- Enterprise Plan -->
            <div class="col-lg-6">
                <div style="background: rgba(124, 58, 237, 0.02); border: 1px solid rgba(124, 58, 237, 0.3); border-radius: 12px; padding: 48px 40px; height: 100%; display: flex; flex-direction: column;">
                    <h3 style="font-size: 1.8rem; font-weight: 600; color: #fff; margin-bottom: 8px;">Enterprise</h3>
                    <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 32px;">Para operações de altíssimo volume.</p>
                    
                    <div style="border-top: 1px solid rgba(124, 58, 237, 0.2); border-bottom: 1px solid rgba(124, 58, 237, 0.2); padding: 24px 0; margin-bottom: 32px;">
                        <h2 style="font-size: 2.2rem; font-weight: 700; color: var(--primary); margin: 0 0 8px;">Sob Consulta</h2>
                        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Modelagem feita com base no volume processado projetado e necessidades de liquidação (Split, D+1).</p>
                    </div>
                    
                    <ul style="list-style: none; padding: 0; margin: 0 0 40px; color: var(--text-muted); font-size: 1.05rem; line-height: 2;">
                        <li><i class="fas fa-check text-primary mr-3" style="width: 16px;"></i> Acordo Tarifário Personalizado</li>
                        <li><i class="fas fa-check text-primary mr-3" style="width: 16px;"></i> Engajamento de Arquitetura de Software</li>
                        <li><i class="fas fa-check text-primary mr-3" style="width: 16px;"></i> Key Account Manager (KAM) Exclusivo</li>
                    </ul>
                    
                    <a href="{{ route('contato') }}" class="btn btn-primary w-100" style="padding: 14px; margin-top: auto; font-size: 1.1rem;">Falar com o comercial</a>
                </div>
            </div>

        </div>
    </div>
</div>

<div style="background: var(--bg-deep); padding-bottom: 120px;">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <h3 style="font-size: 1.8rem; font-weight: 600; color: #fff; margin-bottom: 32px; text-align: center;">Perguntas Frequentes</h3>
        
        <x-frontend.faq-accordion question="A liquidação é feita em D+1?">
            Sim. Para cartões de crédito, a OriginPay antecipa automaticamente os recebimentos, liquidando o montante (descontadas as taxas) em sua conta domicílio em D+1 (um dia útil) de forma transparente.
        </x-frontend.faq-accordion>
        
        <x-frontend.faq-accordion question="Existe custo de adesão ou de gateway?">
            Não. A OriginPay não cobra taxas de adesão, mensalidades, nem custos de "centavos por clique no gateway". Você só paga a taxa percentual sobre transações aprovadas com sucesso.
        </x-frontend.faq-accordion>
        
        <x-frontend.faq-accordion question="O PIX tem taxa fixa ou variável?">
            No plano Start o PIX possui taxa variável. Caso a sua operação envolva micropagamentos constantes ou faturas de ticket muito alto, recomendamos o plano Enterprise para negociação de uma taxa de centavos (fixa) por transação.
        </x-frontend.faq-accordion>
    </div>
</div>

@endsection