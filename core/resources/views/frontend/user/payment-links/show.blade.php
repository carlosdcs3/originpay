@extends('frontend.layouts.user-v2')
@section('title', 'Analytics do Link')

@section('styles')
<style>
    /* OriginPay SaaS Minimal Variables */
    :root {
        --op-bg: #09090B;
        --op-card-bg: #111318;
        --op-border: rgba(255, 255, 255, 0.08);
        --op-border-hover: rgba(255, 255, 255, 0.15);
        --op-primary: #7C3AED;
        --op-primary-hover: #6D28D9;
        --op-text-main: #FAFAFA;
        --op-text-muted: #A1A1AA;
        --op-text-dark: #52525B;
        --op-radius-lg: 12px;
        --op-radius-md: 8px;
        --op-radius-sm: 6px;
        --transition: 200ms ease;
    }

    body { background: var(--op-bg); color: var(--op-text-main); font-family: 'Inter', sans-serif; }

    /* Override V2 Container */
    .v2-header { height: 44px !important; }
    .v2-content { padding: 12px 24px !important; display: flex; flex-direction: column; min-height: calc(100vh - 44px); }

    /* Page Header */
    .op-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--op-border); }
    .op-header-titles h1 { font-size: 1.15rem; font-weight: 600; margin: 0 0 4px 0; color: #FFF; letter-spacing: -0.01em; }
    .op-header-titles p { font-size: 0.75rem; color: var(--op-text-muted); margin: 0; }
    .op-btn-secondary { height: 32px; padding: 0 12px; background: transparent; border: 1px solid var(--op-border); color: var(--op-text-muted); font-size: 0.75rem; font-weight: 500; border-radius: var(--op-radius-sm); cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
    .op-btn-secondary:hover { color: #FFF; background: rgba(255,255,255,0.05); }

    .pl-shell { display: flex; flex-direction: column; gap: 16px; }
    
    .op-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
    .op-kpi-card { background: var(--op-card-bg); border: 1px solid var(--op-border); border-radius: var(--op-radius-lg); padding: 14px; display: flex; flex-direction: column; position: relative; overflow: hidden; }
    .op-kpi-icon { width: 32px; height: 32px; border-radius: var(--op-radius-md); display: flex; align-items: center; justify-content: center; font-size: 0.95rem; margin-bottom: 10px; }
    .op-kpi-title { font-size: 0.75rem; color: var(--op-text-muted); font-weight: 500; margin-bottom: 4px; }
    .op-kpi-val { font-size: 1.15rem; font-weight: 700; color: var(--op-text-main); letter-spacing: -0.02em; }
    .op-kpi-trend { font-size: 0.65rem; margin-top: 6px; font-weight: 500; }

    .chart-container {
        background: var(--op-card-bg);
        border: 1px solid var(--op-border);
        border-radius: var(--op-radius-lg);
        padding: 16px;
    }
    .chart-container h3 { font-size: 0.85rem; color: var(--op-text-main); margin: 0 0 16px 0; font-weight: 600; }
    
    .source-bar-wrap { display: flex; align-items: center; margin-bottom: 10px; }
    .source-bar-label { width: 120px; font-size: 0.75rem; color: var(--op-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
    .source-bar-track { flex: 1; height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; margin: 0 12px; }
    .source-bar-fill { height: 100%; background: var(--op-primary); border-radius: 3px; }
    .source-bar-val { width: 35px; text-align: right; font-size: 0.75rem; font-weight: 600; color: var(--op-text-main); }
    
    .perf-row { margin-bottom: 12px; }
    .perf-row:last-child { margin-bottom: 0; }
    .perf-label { font-size: 0.7rem; color: var(--op-text-muted); margin-bottom: 2px; font-weight: 500; }
    .perf-val { font-size: 0.95rem; font-weight: 600; color: var(--op-text-main); }
</style>
@endsection

@section('content')
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
@endphp

<div class="pl-shell">
    <div class="op-header">
        <div class="op-header-titles">
            <a href="{{ route('user.payment-links.index') }}" style="color: var(--op-primary); text-decoration: none; font-size: 0.75rem; margin-bottom: 6px; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Voltar para links
            </a>
            <h1>{{ $paymentLink->title }}</h1>
            <p>Analytics e desempenho deste link de pagamento.</p>
        </div>
        <div>
            <button type="button" class="op-btn-secondary" onclick="navigator.clipboard.writeText('{{ $paymentLink->publicUrl() }}')">
                <i class="far fa-copy" style="margin-right: 6px;"></i> Copiar URL
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="op-stats-grid">
        <div class="op-kpi-card">
            <div class="op-kpi-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                <i class="fas fa-eye"></i>
            </div>
            <div class="op-kpi-title">Visitas Únicas</div>
            <div class="op-kpi-val">{{ number_format($visitsCount) }}</div>
            <div class="op-kpi-trend" style="color: var(--op-text-muted);">Usuários reais</div>
        </div>

        <div class="op-kpi-card">
            <div class="op-kpi-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="op-kpi-title">Pagamentos</div>
            <div class="op-kpi-val">{{ number_format($paymentsCount) }}</div>
            <div class="op-kpi-trend" style="color: var(--op-text-muted);">Aprovados via checkout</div>
        </div>

        <div class="op-kpi-card">
            <div class="op-kpi-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="op-kpi-title">Conversão</div>
            <div class="op-kpi-val">{{ $conversionRate }}%</div>
            <div class="op-kpi-trend" style="color: #10b981;">Taxa de sucesso</div>
        </div>

        <div class="op-kpi-card">
            <div class="op-kpi-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="op-kpi-title">Receita Gerada</div>
            <div class="op-kpi-val">{{ $money($revenue) }}</div>
            <div class="op-kpi-trend" style="color: var(--op-text-muted);">Volume financeiro</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: minmax(0, 2fr) 1fr; gap: 16px;">
        <div class="chart-container">
            <h3>Origem do Tráfego</h3>
            
            @if($trafficSources->isEmpty())
                <div style="text-align: center; color: var(--op-text-muted); padding: 20px; font-size: 0.75rem;">
                    Ainda não há dados de visitas suficientes.
                </div>
            @else
                @php $maxTraffic = $trafficSources->max('total'); @endphp
                @foreach($trafficSources as $source)
                    @php $percent = $maxTraffic > 0 ? ($source->total / $maxTraffic) * 100 : 0; @endphp
                    <div class="source-bar-wrap">
                        <div class="source-bar-label" title="{{ $source->source }}">
                            {{ Str::title(str_replace(['https://', 'http://', 'www.'], '', $source->source)) }}
                        </div>
                        <div class="source-bar-track">
                            <div class="source-bar-fill" style="width: {{ $percent }}%;"></div>
                        </div>
                        <div class="source-bar-val">{{ number_format($source->total) }}</div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="chart-container">
            <h3>Desempenho</h3>
            
            <div class="perf-row">
                <div class="perf-label">Ticket Médio</div>
                <div class="perf-val">{{ $money($avgTicket) }}</div>
            </div>

            <div class="perf-row">
                <div class="perf-label">Última Venda</div>
                <div class="perf-val">
                    {{ $lastSale ? $lastSale->converted_at->format('d/m/Y H:i') : 'Nenhuma' }}
                </div>
            </div>
            
            <div class="perf-row">
                <div class="perf-label">Status do Link</div>
                <div class="perf-val" style="color: #10b981;">
                    {{ ucfirst($paymentLink->status) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
