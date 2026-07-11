@php use App\Enums\TrxType; @endphp
@php use App\Enums\TrxStatus; @endphp
@extends('frontend.layouts.user-v2')
@section('title', 'Transações')

@section('styles')
<style>
    body.v2-dashboard .trx-page-shell {
        min-height: 0;
    }

    body.v2-dashboard .trx-table-wrapper {
        flex: 1;
        min-height: 0;
        overflow: auto;
    }

    @media (max-width: 768px) {
        body.v2-dashboard .trx-page-shell {
            gap: 12px !important;
        }

        body.v2-dashboard .trx-page-shell .v2-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        body.v2-dashboard .trx-page-shell .v2-kpi-card {
            min-height: 78px !important;
            padding: 10px !important;
            border-radius: 10px !important;
        }

        body.v2-dashboard .trx-page-shell .v2-kpi-title {
            font-size: .64rem !important;
            line-height: 1.25 !important;
            white-space: normal !important;
        }

        body.v2-dashboard .trx-page-shell .dash-balance-hidden {
            font-size: 1rem !important;
            letter-spacing: .08em !important;
        }

        body.v2-dashboard #trx-filter-form > div {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 8px !important;
            padding: 10px !important;
        }

        body.v2-dashboard #trx-filter-form input[type="text"],
        body.v2-dashboard #trx-filter-form input[type="date"],
        body.v2-dashboard #trx-filter-form button {
            width: 100% !important;
            min-width: 0 !important;
            height: 40px !important;
            border-radius: 8px !important;
        }

        body.v2-dashboard #trx-filter-form > div > div {
            width: 100% !important;
            min-width: 0 !important;
        }

        body.v2-dashboard #trx-filter-form > div > div:nth-of-type(2) {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 16px minmax(0, 1fr) 16px !important;
            gap: 6px !important;
            height: auto !important;
            padding: 6px 10px !important;
        }

        body.v2-dashboard #trx-filter-form > div > div:nth-of-type(2) span {
            text-align: center !important;
        }

        body.v2-dashboard .trx-page-shell .v2-card > div[style*="border-bottom"] {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            flex-wrap: nowrap !important;
            scrollbar-width: none !important;
            padding: 0 12px !important;
        }

        body.v2-dashboard .trx-page-shell .v2-card > div[style*="border-bottom"]::-webkit-scrollbar {
            display: none !important;
        }

        body.v2-dashboard .trx-page-shell .v2-card > div[style*="border-bottom"] a {
            flex: 0 0 auto !important;
            padding: 11px 12px !important;
        }

        body.v2-dashboard .trx-table-wrapper {
            max-height: none !important;
        }

        body.v2-dashboard .trx-page-shell .v2-card {
            border-radius: 10px !important;
        }

        body.v2-dashboard .trx-page-shell .v2-mobile-card-table tr {
            padding: 10px 12px !important;
            margin-bottom: 10px !important;
            border-radius: 10px !important;
        }

        body.v2-dashboard .trx-page-shell .v2-mobile-card-table td {
            padding: 6px 0 !important;
        }

        body.v2-dashboard .trx-page-shell .v2-mobile-card-table td::before {
            font-size: .64rem !important;
            letter-spacing: .04em !important;
            text-transform: uppercase !important;
            min-width: 74px !important;
        }

        body.v2-dashboard .trx-page-shell .v2-mobile-card-table td:nth-child(2),
        body.v2-dashboard .trx-page-shell .v2-mobile-card-table td:nth-child(6) {
            display: none !important;
        }

        body.v2-dashboard .trx-page-shell .v2-mobile-card-table tbody tr td[colspan] > div,
        body.v2-dashboard .trx-page-shell .v2-table-wrapper tbody tr td[colspan] > div {
            min-height: 190px !important;
            padding: 26px 12px !important;
        }
    }
</style>
@endsection

@section('content')

@php
    // ── KPI Calculations ───────────────────────────────────────────────────
    $allTrx     = $transactions->getCollection();
    $total      = $transactionSummary['total'] ?? $allTrx->count();
    $approved   = $transactionSummary['approved'] ?? $allTrx->where('status', TrxStatus::COMPLETED)->count();
    $pending    = $transactionSummary['pending'] ?? $allTrx->where('status', TrxStatus::PENDING)->count();
    $failed     = $transactionSummary['failed'] ?? $allTrx->where('status', TrxStatus::FAILED)->count();
    $reversed   = $transactionSummary['canceled'] ?? $allTrx->where('status', TrxStatus::CANCELED)->count();

    $totalVolume    = $transactionSummary['total_volume'] ?? $allTrx->where('status', TrxStatus::COMPLETED)->sum('amount');
    $approvalRate   = $transactionSummary['approval_rate'] ?? ($total > 0 ? round(($approved / $total) * 100, 2) : 0);
    $avgTicket      = $transactionSummary['avg_ticket'] ?? ($approved > 0 ? $totalVolume / $approved : 0);
    $chargebacks    = $transactionSummary['chargebacks'] ?? $allTrx->where('status', TrxStatus::CANCELED)->sum('amount');

    // Current filter
    $activeTab = request('status', 'all');
    $periodLabel = request()->filled('date_from') || request()->filled('date_to') ? 'Periodo filtrado' : 'Periodo atual';
@endphp

{{-- ── Full-height flex wrapper ────────────────────────────────────────── --}}
<div class="trx-page-shell" style="display: flex; flex-direction: column; flex: 1; min-height: 0; gap: 10px;">

{{-- ── Page Header ──────────────────────────────────────────────────────── --}}
<div class="v2-page-header" style="flex-shrink: 0; margin: 0; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="v2-page-title" style="margin-bottom: 2px;">Transações</h1>
        <p class="v2-page-subtitle" style="margin: 0;">Acompanhe todas as movimentações da sua conta em tempo real.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-shrink: 0;">
        <a href="{{ route('user.transaction.index', request()->query()) }}" class="v2-btn-secondary" style="height: 36px; padding: 0 14px; font-size: 0.8125rem; gap: 7px; text-decoration: none;">
            <i class="fas fa-rotate-right" style="font-size: 0.75rem;"></i> Atualizar
        </a>
        @if(Route::has('user.charge.index'))
        <a href="{{ route('user.charge.index') }}" class="v2-btn-primary" style="height: 36px; padding: 0 16px; font-size: 0.8125rem; gap: 7px; text-decoration: none;">
            <i class="fas fa-plus" style="font-size: 0.75rem;"></i> Nova Cobrança
        </a>
        @endif
    </div>
</div>

{{-- ── KPI Cards ─────────────────────────────────────────────────────────── --}}
<div class="v2-kpi-grid" style="flex-shrink: 0; margin: 0;">

    <div class="v2-kpi-card" style="border-color: rgba(124,58,237,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(124,58,237,0.1); color: #a78bfa;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Volume total <i class="fas fa-question-circle" style="margin-left: 4px; opacity: 0.4; font-size: 0.6rem;" title="Volume total de transações aprovadas"></i></span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($totalVolume, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>

    <div class="v2-kpi-card" style="border-color: rgba(59,130,246,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(59,130,246,0.1); color: #60a5fa;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Transações <i class="fas fa-question-circle" style="margin-left: 4px; opacity: 0.4; font-size: 0.6rem;" title="Total de transações no período"></i></span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">{{ number_format($total) }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>

    <div class="v2-kpi-card" style="border-color: rgba(16,185,129,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(16,185,129,0.1); color: var(--ds-success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Taxa de aprovação <i class="fas fa-question-circle" style="margin-left: 4px; opacity: 0.4; font-size: 0.6rem;" title="Percentual de transações aprovadas"></i></span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">{{ $approvalRate }}%</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>

    <div class="v2-kpi-card" style="border-color: rgba(245,158,11,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Ticket médio <i class="fas fa-question-circle" style="margin-left: 4px; opacity: 0.4; font-size: 0.6rem;" title="Valor médio por transação aprovada"></i></span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($avgTicket, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>

    <div class="v2-kpi-card" style="border-color: rgba(239,68,68,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Estornos <i class="fas fa-question-circle" style="margin-left: 4px; opacity: 0.4; font-size: 0.6rem;" title="Volume total de estornos"></i></span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($chargebacks, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dashBtns = document.querySelectorAll('.toggle-dash-balance');
        const dashVisibles = document.querySelectorAll('.dash-balance-visible');
        const dashHiddens = document.querySelectorAll('.dash-balance-hidden');
        
        function syncDashBalances() {
            let isHidden = localStorage.getItem('hideBalance') !== 'false';
            
            dashBtns.forEach(btn => {
                btn.className = isHidden ? 'fas fa-eye-slash toggle-dash-balance' : 'fas fa-eye toggle-dash-balance';
            });
            
            dashVisibles.forEach(el => {
                el.style.display = isHidden ? 'none' : 'block';
            });
            
            dashHiddens.forEach(el => {
                el.style.display = isHidden ? 'block' : 'none';
            });
            
            // Sync with sidebar if exists in this layout
            const sbBtn = document.getElementById('toggle-balance-btn') || document.getElementById('toggle-balance-btn-v2');
            const sbVis = document.getElementById('balance-visible') || document.getElementById('balance-visible-v2');
            const sbHid = document.getElementById('balance-hidden') || document.getElementById('balance-hidden-v2');
            
            if (sbBtn && sbVis && sbHid) {
                const sbIcon = sbBtn.querySelector('i');
                if (sbIcon) {
                    sbIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
                }
                sbBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                sbVis.style.display = isHidden ? 'none' : 'block';
                sbHid.style.display = isHidden ? 'block' : 'none';
            }
        }
        
        syncDashBalances();
        
        dashBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                let isHidden = localStorage.getItem('hideBalance') !== 'false';
                localStorage.setItem('hideBalance', !isHidden);
                syncDashBalances();
            });
        });
        
        // Also listen to sidebar clicks if it exists
        const sbBtn = document.getElementById('toggle-balance-btn') || document.getElementById('toggle-balance-btn-v2');
        if (sbBtn) {
            sbBtn.addEventListener('click', function() {
                setTimeout(syncDashBalances, 50);
            });
        }
    });
</script>

{{-- ── Filter Bar ────────────────────────────────────────────────────────── --}}
<div class="v2-card" style="padding: 0; display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <form action="{{ route('user.transaction.index') }}" method="GET" id="trx-filter-form">
        @if(request('tab'))
        <input type="hidden" name="tab" value="{{ request('tab') }}">
        @endif

        <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px;">

            {{-- Search --}}
            <div style="flex: 1; min-width: 180px; position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--ds-text-muted); font-size: 0.75rem; pointer-events: none;"></i>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    placeholder="Buscar por ID, cliente, e-mail ou descrição..."
                    style="width: 100%; background: rgba(255,255,255,0.03); border: 1px solid var(--ds-border-medium); border-radius: 8px; padding: 6px 10px 6px 30px; color: var(--ds-text-main); font-size: 0.8rem; outline: none; transition: border-color 0.2s; height: 34px;"
                    onfocus="this.style.borderColor='rgba(124,58,237,0.4)'"
                    onblur="this.style.borderColor='var(--ds-border-medium)'">
            </div>

            {{-- Status dropdown removed as it duplicates the tabs functionality --}}


            {{-- Date Range --}}
            <div style="display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.03); border: 1px solid var(--ds-border-medium); border-radius: 8px; padding: 0 10px; height: 34px;">
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    style="background: transparent; border: none; color: var(--ds-text-main); font-size: 0.8rem; outline: none; cursor: pointer; width: 118px;"
                    onchange="this.form.submit()">
                <i class="fas fa-arrow-right" style="color: var(--ds-text-muted); font-size: 0.6rem; flex-shrink: 0;"></i>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    style="background: transparent; border: none; color: var(--ds-text-main); font-size: 0.8rem; outline: none; cursor: pointer; width: 118px;"
                    onchange="this.form.submit()">
                <i class="fas fa-calendar" style="color: var(--ds-text-muted); font-size: 0.7rem; flex-shrink: 0;"></i>
            </div>

            {{-- Submit --}}
            <button type="submit" class="v2-btn-secondary" style="height: 34px; padding: 0 14px; font-size: 0.8rem; gap: 6px; flex-shrink: 0;">
                <i class="fas fa-sliders-h" style="font-size: 0.7rem;"></i> Filtros
            </button>

        </div>
    </form>
    
    {{-- Tabs --}}
    <div style="display: flex; border-bottom: 1px solid var(--ds-border-light); padding: 0 16px; gap: 4px; overflow-x: auto;">
        @php
            $tabs = [
                ['key' => 'all',       'label' => 'Todas',      'count' => $transactionCounts['all'] ?? $total,          'color' => 'var(--ds-primary)'],
                ['key' => 'completed', 'label' => 'Aprovadas',  'count' => $transactionCounts['approved'] ?? $approved,  'color' => 'var(--ds-success)'],
                ['key' => 'pending',   'label' => 'Pendentes',  'count' => $transactionCounts['pending'] ?? $pending,    'color' => '#f59e0b'],
                ['key' => 'failed',    'label' => 'Falhas',     'count' => $transactionCounts['failed'] ?? $failed,      'color' => '#ef4444'],
                ['key' => 'canceled',  'label' => 'Canceladas', 'count' => $transactionCounts['canceled'] ?? $reversed,  'color' => 'var(--ds-text-muted)'],
            ];
            $activeTab = request('tab', 'all');
        @endphp
        @foreach($tabs as $tab)
        <a href="{{ route('user.transaction.index', array_merge(request()->except('tab', 'page'), ['tab' => $tab['key']])) }}"
            style="display: flex; align-items: center; gap: 8px; padding: 12px 14px; font-size: 0.875rem; font-weight: 500; text-decoration: none; white-space: nowrap; border-bottom: 2px solid {{ $activeTab === $tab['key'] ? $tab['color'] : 'transparent' }}; color: {{ $activeTab === $tab['key'] ? 'var(--ds-text-main)' : 'var(--ds-text-muted)' }}; transition: all 0.15s; margin-bottom: -1px;">
            {{ $tab['label'] }}
            <span style="background: {{ $activeTab === $tab['key'] ? $tab['color'] : 'rgba(255,255,255,0.08)' }}; color: {{ $activeTab === $tab['key'] ? '#fff' : 'var(--ds-text-muted)' }}; font-size: 0.7rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; min-width: 24px; text-align: center;">
                {{ $tab['count'] }}
            </span>
        </a>
        @endforeach
    </div>

    {{-- Table Wrapper --}}
    <div class="v2-table-wrapper trx-table-wrapper" style="overflow: auto;">
        <table class="v2-table">
            <thead>
                <tr>
                    <th>ID da Transação</th>
                    <th>Cliente</th>
                    <th>Método</th>
                    <th style="text-align: right;">Valor</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                @php
                    $txClass  = $transaction->trx_type->kebabCase();
                    $icon     = $transaction->trx_type->icon();
                    $trxLabel = $transaction->trx_type->label();
                    $statusColor = match($transaction->status) {
                        TrxStatus::COMPLETED => 'v2-badge-success',
                        TrxStatus::PENDING   => 'v2-badge-warning',
                        TrxStatus::FAILED    => 'v2-badge-error',
                        TrxStatus::CANCELED  => 'v2-badge-info',
                        default              => 'v2-badge-info',
                    };
                    $statusLabel = match($transaction->status) {
                        TrxStatus::COMPLETED => 'Aprovada',
                        TrxStatus::PENDING   => 'Pendente',
                        TrxStatus::FAILED    => 'Falhou',
                        TrxStatus::CANCELED  => 'Estornada',
                        default              => ucfirst($transaction->status->value),
                    };
                    $methodIcon = match($transaction->trx_type) {
                        TrxType::DEPOSIT => ['icon' => 'fas fa-qrcode', 'color' => 'var(--ds-pix)', 'bg' => 'rgba(0,229,200,0.1)'],
                        default          => ['icon' => 'fas fa-exchange-alt', 'color' => '#a78bfa', 'bg' => 'rgba(124,58,237,0.1)'],
                    };
                @endphp
                <tr style="cursor: pointer;"
                    data-bs-toggle="modal"
                    data-bs-target="#transactionModal{{ $transaction->id }}">
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.8rem; color: var(--ds-text-muted);">{{ $transaction->trx_id }}</span>
                            <i class="far fa-copy" style="color: var(--ds-text-muted); font-size: 0.65rem; opacity: 0.5; cursor: pointer;"
                               onclick="event.stopPropagation(); navigator.clipboard.writeText('{{ $transaction->trx_id }}');" title="Copiar ID"></i>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 1px;">
                            <span style="font-weight: 500; color: var(--ds-text-main); font-size: 0.875rem;">{{ $transaction->user->name ?? $transaction->user->first_name ?? '—' }}</span>
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted);">{{ $transaction->user->email ?? '' }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 7px;">
                            <div style="width: 28px; height: 20px; border-radius: 4px; background: {{ $methodIcon['bg'] }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="{{ $methodIcon['icon'] }}" style="font-size: 0.7rem; color: {{ $methodIcon['color'] }};"></i>
                            </div>
                            <span style="font-size: 0.875rem; color: var(--ds-text-secondary);">{{ $trxLabel }}</span>
                        </div>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: var(--ds-text-main); font-family: 'JetBrains Mono', monospace; font-size: 0.875rem;">
                        R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                    </td>
                    <td>
                        <span class="v2-badge {{ $statusColor }}">{{ $statusLabel }}</span>
                    </td>
                    <td style="font-size: 0.8rem; color: var(--ds-text-muted); white-space: nowrap;">
                        {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 4px;" onclick="event.stopPropagation();">
                            <button type="button"
                                class="v2-btn-tertiary"
                                style="height: 30px; padding: 0 10px; font-size: 0.8rem; border: 1px solid var(--ds-border-medium); border-radius: 6px;"
                                data-bs-toggle="modal"
                                data-bs-target="#transactionModal{{ $transaction->id }}">
                                Ver
                            </button>
                            <div style="position: relative;">
                                <button type="button"
                                    class="v2-icon-btn"
                                    style="width: 30px; height: 30px; border-radius: 6px;"
                                    onclick="toggleTrxMenu({{ $transaction->id }})">
                                    <i class="fas fa-ellipsis-v" style="font-size: 0.7rem;"></i>
                                </button>
                                <div id="trxMenu{{ $transaction->id }}"
                                    style="display: none; position: absolute; right: 0; top: 34px; background: var(--ds-bg-card); border: 1px solid var(--ds-border-medium); border-radius: 8px; padding: 4px; z-index: 100; min-width: 160px; box-shadow: 0 8px 24px rgba(0,0,0,0.3);">
                                    <a href="{{ route('user.transaction.download-pdf', $transaction->trx_id) }}"
                                        style="display: flex; align-items: center; gap: 8px; padding: 7px 10px; font-size: 0.8125rem; color: var(--ds-text-secondary); text-decoration: none; border-radius: 6px; transition: background 0.15s;"
                                        onmouseover="this.style.background='rgba(255,255,255,0.04)'"
                                        onmouseout="this.style.background='transparent'">
                                        <i class="fas fa-file-pdf" style="font-size: 0.8rem; width: 14px;"></i> Baixar recibo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>



                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 48px 0;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; color: var(--ds-text-muted);">
                            <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                            <span style="font-weight: 500; font-size: 0.9rem;">Nenhuma transação encontrada</span>
                            <span style="font-size: 0.8rem; opacity: 0.7;">Tente ajustar os filtros ou o período selecionado.</span>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-top: 1px solid var(--ds-border-light);">
        <span style="font-size: 0.8rem; color: var(--ds-text-muted);">
            Mostrando {{ $transactions->firstItem() ?? 0 }} a {{ $transactions->lastItem() ?? 0 }} de {{ number_format($transactions->total()) }} transações
        </span>

        @if($transactions->hasPages())
        <div style="display: flex; align-items: center; gap: 4px;">
            {{-- Anterior --}}
            @if($transactions->onFirstPage())
            <span style="padding: 5px 12px; font-size: 0.8125rem; color: var(--ds-text-muted); opacity: 0.4; pointer-events: none;">Anterior</span>
            @else
            <a href="{{ $transactions->previousPageUrl() }}" style="padding: 5px 12px; font-size: 0.8125rem; color: var(--ds-text-secondary); text-decoration: none; border: 1px solid var(--ds-border-medium); border-radius: 6px; transition: all 0.15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">Anterior</a>
            @endif

            {{-- Page numbers --}}
            @foreach($transactions->getUrlRange(max(1, $transactions->currentPage()-1), min($transactions->lastPage(), $transactions->currentPage()+1)) as $page => $url)
            @if($page == $transactions->currentPage())
            <span style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; font-weight: 700; background: var(--ds-primary); color: #fff; border-radius: 6px;">{{ $page }}</span>
            @else
            <a href="{{ $url }}" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; color: var(--ds-text-secondary); text-decoration: none; border: 1px solid var(--ds-border-medium); border-radius: 6px; transition: all 0.15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">{{ $page }}</a>
            @endif
            @endforeach

            @if($transactions->currentPage() + 1 < $transactions->lastPage())
            <span style="color: var(--ds-text-muted); padding: 0 4px;">…</span>
            <a href="{{ $transactions->url($transactions->lastPage()) }}" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; color: var(--ds-text-secondary); text-decoration: none; border: 1px solid var(--ds-border-medium); border-radius: 6px; transition: all 0.15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">{{ $transactions->lastPage() }}</a>
            @endif

            {{-- Próximo --}}
            @if($transactions->hasMorePages())
            <a href="{{ $transactions->nextPageUrl() }}" style="padding: 5px 12px; font-size: 0.8125rem; color: var(--ds-text-secondary); text-decoration: none; border: 1px solid var(--ds-border-medium); border-radius: 6px; transition: all 0.15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">Próximo</a>
            @else
            <span style="padding: 5px 12px; font-size: 0.8125rem; color: var(--ds-text-muted); opacity: 0.4; pointer-events: none;">Próximo</span>
            @endif
        </div>
        @endif
    </div>

</div>{{-- /main card --}}

@foreach($transactions as $transaction)
    @php
        $txClass = $transaction->trx_type->kebabCase();
    @endphp
    @include('frontend.user.transaction.partials._details_modal', [
        'transaction'          => $transaction,
        'transactionTypeClass' => $txClass
    ])
@endforeach

</div>{{-- /flex wrapper --}}

@endsection

@push('scripts')
<script>
function toggleTrxMenu(id) {
    const menu = document.getElementById('trxMenu' + id);
    const allMenus = document.querySelectorAll('[id^="trxMenu"]');
    allMenus.forEach(m => { if (m.id !== 'trxMenu' + id) m.style.display = 'none'; });
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function() {
    document.querySelectorAll('[id^="trxMenu"]').forEach(m => m.style.display = 'none');
});
</script>
@endpush
