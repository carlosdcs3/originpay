@extends('frontend.layouts.landing')
@section('title', 'Status dos Serviços — OriginPay')
@section('description', 'Acompanhe a disponibilidade e o uptime das nossas APIs.')

@section('content')
<section class="op-hero op-hero-internal" style="padding: 100px 0 40px; background: var(--bg-deep); min-height: 280px; display:flex; align-items:center;">
    <div class="container">
        <div class="op-breadcrumb" style="color: var(--primary-light); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 15px;">Produto &gt; Status</div>
        <h1 class="op-hero-title" style="font-size: 2.8rem; margin-bottom: 10px;">Status da Plataforma</h1>
        <p class="op-hero-subtitle" style="font-size: 1.1rem; color: var(--text-muted); max-width: 650px;">Monitoramento do estado atual dos serviços da OriginPay.</p>
    </div>
</section>

<section class="op-content-section" style="padding: 40px 0 100px; background: var(--bg-deep);">
    <div class="container">
        <div class="p-4 text-center mb-5" style="background:rgba(16, 185, 129, 0.1); border:1px solid #10b981; border-radius:12px;">
            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
            <h4 class="text-white m-0">Todos os sistemas operacionais</h4>
        </div>
        
        <div class="card border-secondary bg-transparent mb-4" style="border-radius:12px;">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" style="background:transparent;">
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center p-4 border-secondary">
                        <span class="text-white font-weight-bold" style="font-size:1.1rem;">API REST Pública</span>
                        <span class="badge" style="background:#10b981; color:#fff; font-size:0.85rem;">Operacional</span>
                    </li>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center p-4 border-secondary">
                        <span class="text-white font-weight-bold" style="font-size:1.1rem;">Entrega de Webhooks</span>
                        <span class="badge" style="background:#10b981; color:#fff; font-size:0.85rem;">Operacional</span>
                    </li>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center p-4 border-secondary">
                        <span class="text-white font-weight-bold" style="font-size:1.1rem;">Liquidação Pix</span>
                        <span class="badge" style="background:#10b981; color:#fff; font-size:0.85rem;">Operacional</span>
                    </li>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center p-4 border-secondary">
                        <span class="text-white font-weight-bold" style="font-size:1.1rem;">Processamento de Cartões</span>
                        <span class="badge" style="background:#10b981; color:#fff; font-size:0.85rem;">Operacional</span>
                    </li>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center p-4 border-secondary border-0">
                        <span class="text-white font-weight-bold" style="font-size:1.1rem;">Dashboard Painel Web</span>
                        <span class="badge" style="background:#10b981; color:#fff; font-size:0.85rem;">Operacional</span>
                    </li>
                </ul>
            </div>
        </div>
        <p class="text-muted text-right" style="font-size:0.85rem;">Atualizado via simulação em {{ date('H:i') }}.</p>
    </div>
</section>
@endsection