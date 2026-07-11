@php use App\Enums\ChargeStatus; @endphp
@extends('frontend.layouts.user-v2')
@section('title', 'Boletos')

@section('styles')
<style>
    html,
    body.v2-dashboard {
        overflow: hidden !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    html::-webkit-scrollbar,
    body.v2-dashboard::-webkit-scrollbar,
    body.v2-dashboard .boleto-page-shell::-webkit-scrollbar,
    body.v2-dashboard .boleto-page-shell *::-webkit-scrollbar,
    body.v2-dashboard .v2-content::-webkit-scrollbar,
    body.v2-dashboard .v2-main::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }

    body.v2-dashboard .v2-main,
    body.v2-dashboard .v2-content {
        overflow: hidden !important;
    }

    .boleto-page-shell {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        gap: 12px;
    }

    .boleto-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-shrink: 0;
    }

    .boleto-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
        flex-shrink: 0;
    }

    .boleto-kpi-card {
        background: var(--ds-bg-card);
        border: 1px solid var(--ds-border-light);
        border-radius: 10px;
        padding: 14px 16px;
        min-height: 94px;
        display: grid;
        grid-template-columns: auto 1fr;
        grid-template-rows: auto 1fr auto;
        column-gap: 12px;
        align-items: center;
    }

    .boleto-kpi-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        grid-row: 1 / span 2;
        font-size: 0.95rem;
    }

    .boleto-kpi-label {
        color: var(--ds-text-muted);
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .boleto-kpi-value {
        color: var(--ds-text-main);
        font-size: 1.2rem;
        line-height: 1.15;
        font-weight: 800;
        margin-top: 4px;
    }

    .boleto-kpi-foot {
        grid-column: 1 / -1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        color: var(--ds-text-muted);
        font-size: 0.72rem;
    }

    .boleto-panel {
        background: var(--ds-bg-card);
        border: 1px solid var(--ds-border-light);
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
    }

    .boleto-filter-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 12px;
        align-items: center;
        padding: 0 0 8px;
        flex-shrink: 0;
    }

    .boleto-tabs {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow: hidden;
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--ds-border-light);
        border-radius: 10px;
        padding: 4px;
        min-width: 0;
    }

    .boleto-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 8px;
        color: var(--ds-text-muted);
        text-decoration: none;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .boleto-tab.active {
        color: var(--ds-text-main);
        background: rgba(124,58,237,0.16);
        box-shadow: inset 0 -1px 0 rgba(124,58,237,0.7);
    }

    .boleto-count {
        min-width: 22px;
        padding: 1px 7px;
        border-radius: 999px;
        text-align: center;
        color: #fff;
        background: rgba(255,255,255,0.1);
        font-size: 0.68rem;
        font-weight: 800;
    }

    .boleto-tab.active .boleto-count {
        background: var(--ds-primary);
    }

    .boleto-control {
        height: 38px;
        border-radius: 9px;
        border: 1px solid var(--ds-border-medium);
        background: rgba(255,255,255,0.03);
        color: var(--ds-text-secondary);
        font-size: 0.8rem;
        outline: none;
    }

    .boleto-date-range {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
    }

    .boleto-date-range input {
        width: 116px;
        border: 0;
        background: transparent;
        color: var(--ds-text-secondary);
        outline: none;
        font-size: 0.78rem;
    }

    .boleto-table-wrap {
        overflow: hidden;
        flex: 1;
        min-height: 0;
    }

    .boleto-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .boleto-table thead th {
        color: var(--ds-text-muted);
        background: rgba(255,255,255,0.015);
        border-bottom: 1px solid var(--ds-border-light);
        padding: 13px 14px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        text-align: left;
        white-space: nowrap;
    }

    .boleto-table tbody td {
        border-bottom: 1px solid var(--ds-border-light);
        padding: 12px 14px;
        color: var(--ds-text-secondary);
        font-size: 0.8rem;
        vertical-align: middle;
    }

    .boleto-table tbody tr:hover {
        background: rgba(255,255,255,0.018);
    }

    .boleto-id-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .boleto-row-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #fff;
    }

    .boleto-main-text {
        color: var(--ds-text-main);
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .boleto-sub-text {
        color: var(--ds-text-muted);
        font-size: 0.72rem;
        margin-top: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .boleto-money {
        color: var(--ds-text-main);
        font-weight: 800;
        white-space: nowrap;
    }

    .boleto-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 78px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
    }

    .boleto-status.pending,
    .boleto-status.waiting_payment {
        color: #fbbf24;
        background: rgba(245,158,11,0.12);
    }

    .boleto-status.paid {
        color: #22c55e;
        background: rgba(34,197,94,0.12);
    }

    .boleto-status.expired {
        color: #ef4444;
        background: rgba(239,68,68,0.12);
    }

    .boleto-status.cancelled,
    .boleto-status.refunded {
        color: var(--ds-text-muted);
        background: rgba(148,163,184,0.12);
    }

    .boleto-action-group {
        display: flex;
        justify-content: flex-end;
        gap: 7px;
    }

    .boleto-icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--ds-border-medium);
        background: rgba(255,255,255,0.02);
        color: var(--ds-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .boleto-icon-btn:hover {
        color: var(--ds-text-main);
        border-color: rgba(124,58,237,0.35);
    }

    .boleto-empty {
        height: 100%;
        min-height: 320px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--ds-text-muted);
        text-align: center;
    }

    .boleto-empty i {
        font-size: 2rem;
        color: rgba(148,163,184,0.35);
        margin-bottom: 12px;
    }

    .boleto-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 48px;
        padding: 10px 14px;
        color: var(--ds-text-muted);
        border-top: 1px solid var(--ds-border-light);
        font-size: 0.78rem;
        flex-shrink: 0;
    }

    .boleto-pages {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .boleto-page-btn {
        min-width: 34px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--ds-border-medium);
        background: rgba(255,255,255,0.02);
        color: var(--ds-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.76rem;
    }

    .boleto-page-btn.active {
        background: var(--ds-primary);
        color: #fff;
        border-color: var(--ds-primary);
    }

    .boleto-help {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 54px;
        padding: 12px 16px;
        border: 1px solid var(--ds-border-light);
        border-radius: 10px;
        background: var(--ds-bg-card);
        flex-shrink: 0;
    }

    @media (max-width: 1400px) {
        .boleto-kpi-value { font-size: 1.02rem; }
        .boleto-kpi-card { padding: 12px; }
        .boleto-table thead th,
        .boleto-table tbody td { padding-left: 10px; padding-right: 10px; }
    }
</style>
@endsection

@section('content')
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $statusTabs = [
        'all' => ['label' => 'Todos', 'count' => $statusCounts['all'] ?? 0],
        ChargeStatus::PENDING->value => ['label' => 'Pendentes', 'count' => $statusCounts['pending'] ?? 0],
        ChargeStatus::PAID->value => ['label' => 'Pagos', 'count' => $statusCounts['paid'] ?? 0],
        ChargeStatus::EXPIRED->value => ['label' => 'Vencidos', 'count' => $statusCounts['expired'] ?? 0],
        ChargeStatus::CANCELLED->value => ['label' => 'Cancelados', 'count' => $statusCounts['cancelled'] ?? 0],
    ];
    $activeStatus = request('status', 'all');
    $statusLabels = [
        ChargeStatus::PENDING->value => 'Pendente',
        ChargeStatus::WAITING_PAYMENT->value => 'Pendente',
        ChargeStatus::PAID->value => 'Pago',
        ChargeStatus::EXPIRED->value => 'Vencido',
        ChargeStatus::CANCELLED->value => 'Cancelado',
        ChargeStatus::REFUNDED->value => 'Reembolsado',
    ];
@endphp

<div class="boleto-page-shell">
    <div class="v2-page-header" style="flex-shrink: 0; margin: 0; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="v2-page-title" style="margin-bottom: 2px;">Boletos</h1>
            <p class="v2-page-subtitle" style="margin: 0;">Gerencie seus boletos, acompanhe pagamentos e recebimentos.</p>
        </div>
        <div class="boleto-actions">
            <a href="{{ route('user.boleto.index', request()->query()) }}" class="v2-btn-secondary" style="height: 36px; padding: 0 14px; font-size: 0.8125rem; gap: 7px; text-decoration: none;">
                <i class="fas fa-download" style="font-size: 0.75rem;"></i> Exportar
            </a>
            @if(Route::has('user.charge.create'))
                <a href="{{ route('user.charge.create', ['method' => 'boleto']) }}" class="v2-btn-primary" style="height: 36px; padding: 0 16px; font-size: 0.8125rem; gap: 7px; text-decoration: none;">
                    <i class="fas fa-plus" style="font-size: 0.75rem;"></i> Novo Boleto
                </a>
            @endif
        </div>
    </div>

    <div class="boleto-kpi-grid">
        <div class="boleto-kpi-card" style="border-color: rgba(124,58,237,0.14);">
            <div class="boleto-kpi-icon" style="background: rgba(124,58,237,0.16); color: #a78bfa;"><i class="fas fa-barcode"></i></div>
            <div class="boleto-kpi-label">Volume de boletos</div>
            <div class="boleto-kpi-value">{{ $money($stats['total_volume'] ?? 0) }}</div>
            <div class="boleto-kpi-foot"><span>Ultimos 7 dias</span><strong style="color: var(--ds-success);">+0,0%</strong></div>
        </div>
        <div class="boleto-kpi-card" style="border-color: rgba(34,197,94,0.14);">
            <div class="boleto-kpi-icon" style="background: rgba(34,197,94,0.14); color: #22c55e;"><i class="far fa-file-alt"></i></div>
            <div class="boleto-kpi-label">Boletos pagos</div>
            <div class="boleto-kpi-value">{{ $money($stats['paid_volume'] ?? 0) }}</div>
            <div class="boleto-kpi-foot"><span>Ultimos 7 dias</span><strong style="color: var(--ds-success);">+0,0%</strong></div>
        </div>
        <div class="boleto-kpi-card" style="border-color: rgba(245,158,11,0.14);">
            <div class="boleto-kpi-icon" style="background: rgba(245,158,11,0.14); color: #f59e0b;"><i class="fas fa-clock"></i></div>
            <div class="boleto-kpi-label">Pendentes</div>
            <div class="boleto-kpi-value">{{ $money($stats['pending_volume'] ?? 0) }}</div>
            <div class="boleto-kpi-foot"><span>Ultimos 7 dias</span><strong style="color: #ef4444;">0,0%</strong></div>
        </div>
        <div class="boleto-kpi-card" style="border-color: rgba(234,179,8,0.14);">
            <div class="boleto-kpi-icon" style="background: rgba(234,179,8,0.14); color: #eab308;"><i class="fas fa-chart-line"></i></div>
            <div class="boleto-kpi-label">Taxa de recebimento</div>
            <div class="boleto-kpi-value">{{ number_format((float) ($stats['receive_rate'] ?? 0), 1, ',', '.') }}%</div>
            <div class="boleto-kpi-foot"><span>Periodo total</span><strong style="color: var(--ds-success);">+0,0%</strong></div>
        </div>
        <div class="boleto-kpi-card" style="border-color: rgba(59,130,246,0.14);">
            <div class="boleto-kpi-icon" style="background: rgba(59,130,246,0.14); color: #60a5fa;"><i class="fas fa-list"></i></div>
            <div class="boleto-kpi-label">Total de boletos</div>
            <div class="boleto-kpi-value">{{ number_format((int) ($stats['total_count'] ?? 0), 0, ',', '.') }}</div>
            <div class="boleto-kpi-foot"><span>Periodo total</span><strong style="color: var(--ds-success);">+0</strong></div>
        </div>
    </div>

    <form action="{{ route('user.boleto.index') }}" method="GET" class="boleto-filter-row">
        <div class="boleto-tabs">
            @foreach($statusTabs as $status => $tab)
                <a href="{{ route('user.boleto.index', array_merge(request()->except('status', 'page'), ['status' => $status])) }}" class="boleto-tab {{ $activeStatus === $status ? 'active' : '' }}">
                    {{ $tab['label'] }}
                    <span class="boleto-count">{{ $tab['count'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="boleto-control boleto-date-range">
            <i class="far fa-calendar" style="color: var(--ds-text-muted); font-size: 0.76rem;"></i>
            <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="this.form.submit()">
            <i class="fas fa-arrow-right" style="color: var(--ds-text-muted); font-size: 0.58rem;"></i>
            <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="this.form.submit()">
            @if($activeStatus !== 'all')
                <input type="hidden" name="status" value="{{ $activeStatus }}">
            @endif
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
        </div>

        <button type="submit" class="v2-btn-secondary" style="height: 38px; padding: 0 16px; font-size: 0.8rem; gap: 8px;">
            <i class="fas fa-filter" style="font-size: 0.72rem;"></i> Filtros
        </button>
    </form>

    <div class="boleto-panel">
        <div class="boleto-table-wrap">
            <table class="boleto-table">
                <thead>
                    <tr>
                        <th style="width: 28%;">Boleto</th>
                        <th style="width: 20%;">Sacado / Referencia</th>
                        <th style="width: 11%;">Vencimento</th>
                        <th style="width: 10%;">Valor</th>
                        <th style="width: 11%;">Status</th>
                        <th style="width: 11%;">Pagamento</th>
                        <th style="width: 9%; text-align: right;">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($charges as $charge)
                        @php
                            $status = $charge->status?->value ?? (string) $charge->status;
                            $isPaid = $status === ChargeStatus::PAID->value;
                            $isExpired = $status === ChargeStatus::EXPIRED->value || ($charge->expires_at && now()->greaterThan($charge->expires_at) && ! $isPaid);
                            $statusClass = $isExpired ? 'expired' : $status;
                            $statusLabel = $isExpired ? 'Vencido' : ($statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)));
                        @endphp
                        <tr>
                            <td>
                                <div class="boleto-id-cell">
                                    <div class="boleto-row-icon" style="background: {{ $isPaid ? 'rgba(34,197,94,0.16)' : ($isExpired ? 'rgba(239,68,68,0.16)' : 'rgba(124,58,237,0.16)') }}; color: {{ $isPaid ? '#22c55e' : ($isExpired ? '#ef4444' : '#a78bfa') }};">
                                        <i class="fas {{ $isPaid ? 'fa-check' : ($isExpired ? 'fa-barcode' : 'fa-barcode') }}"></i>
                                    </div>
                                    <div style="min-width: 0;">
                                        <div class="boleto-main-text">{{ $charge->gateway_charge_id ?: $charge->uuid }}</div>
                                        <div class="boleto-sub-text">Linha: {{ $charge->digitable_line ?: 'Nao informada' }}</div>
                                        <div class="boleto-sub-text">Barcode: {{ $charge->barcode ?: 'Nao informado' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="boleto-main-text">{{ $charge->customer_name ?: 'Cliente nao informado' }}</div>
                                <div class="boleto-sub-text">{{ $charge->customer_document ?: $charge->customer_email ?: 'Documento nao informado' }}</div>
                            </td>
                            <td>
                                <div class="boleto-main-text">{{ $charge->expires_at ? $charge->expires_at->format('d/m/Y') : '-' }}</div>
                                <div class="boleto-sub-text">
                                    @if($charge->expires_at)
                                        {{ $isExpired ? 'Vencido' : $charge->expires_at->diffForHumans(null, true) }}
                                    @else
                                        Sem vencimento
                                    @endif
                                </div>
                            </td>
                            <td><span class="boleto-money">{{ $money($charge->amount) }}</span></td>
                            <td><span class="boleto-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                @if($isPaid)
                                    <div class="boleto-main-text">{{ $charge->updated_at?->format('d/m/Y') }}</div>
                                    <div class="boleto-sub-text">{{ $charge->updated_at?->format('H:i') }}</div>
                                @else
                                    <span class="boleto-sub-text">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="boleto-action-group">
                                    @if(Route::has('user.charge.show'))
                                        <a class="boleto-icon-btn" href="{{ route('user.charge.show', $charge->id) }}" title="Visualizar"><i class="far fa-eye"></i></a>
                                    @endif
                                    @if($charge->boleto_pdf_url)
                                        <a class="boleto-icon-btn" href="{{ $charge->boleto_pdf_url }}" target="_blank" rel="noopener" title="Abrir PDF"><i class="fas fa-download"></i></a>
                                    @endif
                                    <form action="{{ route('user.boleto.second-copy', $charge->id) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button class="boleto-icon-btn" type="submit" title="Emitir segunda via"><i class="fas fa-redo-alt"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 0; border-bottom: 0;">
                                <div class="boleto-empty">
                                    <i class="fas fa-barcode"></i>
                                    <strong style="color: var(--ds-text-secondary);">Nenhum boleto encontrado</strong>
                                    <span style="margin-top: 6px;">Quando houver cobranças por boleto, elas aparecerão aqui.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="boleto-footer">
            <span>Mostrando {{ $charges->firstItem() ?? 0 }} a {{ $charges->lastItem() ?? 0 }} de {{ $charges->total() }} boletos</span>
            @if($charges->hasPages())
                <div class="boleto-pages">
                    @if($charges->onFirstPage())
                        <span class="boleto-page-btn"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a class="boleto-page-btn" href="{{ $charges->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>
                    @endif

                    @foreach(range(1, $charges->lastPage()) as $page)
                        @if($page <= 3 || $page === $charges->lastPage() || abs($page - $charges->currentPage()) <= 1)
                            <a class="boleto-page-btn {{ $charges->currentPage() === $page ? 'active' : '' }}" href="{{ $charges->url($page) }}">{{ $page }}</a>
                        @elseif($page === 4)
                            <span class="boleto-page-btn">...</span>
                        @endif
                    @endforeach

                    @if($charges->hasMorePages())
                        <a class="boleto-page-btn" href="{{ $charges->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="boleto-page-btn"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="boleto-help">
        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
            <div class="boleto-row-icon" style="background: rgba(124,58,237,0.18); color: #a78bfa;"><i class="fas fa-info"></i></div>
            <div style="min-width: 0;">
                <div class="boleto-main-text">Sobre os boletos</div>
                <div class="boleto-sub-text">Boletos podem levar ate 3 dias uteis para confirmacao do pagamento apos o vencimento.</div>
            </div>
        </div>
        <a href="#" class="v2-btn-secondary" style="height: 34px; padding: 0 14px; font-size: 0.78rem; gap: 8px; color: #a78bfa;">
            Central de Ajuda <i class="fas fa-external-link-alt" style="font-size: 0.68rem;"></i>
        </a>
    </div>
</div>
@endsection
