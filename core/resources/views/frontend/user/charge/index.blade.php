@extends('frontend.layouts.user-v2')
@section('title', 'Cobranças')

@section('content')

<style>
/* ── Tokens ──────────────────────────────────────── */
:root {
    --ch-teal:   #00e5c8;
    --ch-purple: #7c3aed;
    --ch-amber:  #fbbf24;
    --ch-red:    #f43f5e;
    --ch-surf:   rgba(255,255,255,0.025);
    --ch-bord:   rgba(255,255,255,0.07);
    --ch-bord-h: rgba(124,58,237,0.22);
}

/* ── Page Header ─────────────────────────────────── */
.ch-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px;
}
.ch-page-title { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 2px; }
.ch-page-sub   { font-size: 0.78rem; color: rgba(255,255,255,0.38); }
.ch-new-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 10px; font-size: 0.84rem; font-weight: 700;
    background: linear-gradient(135deg, var(--ch-purple), #5b21b6); color: #fff; text-decoration: none;
    transition: opacity 0.2s, transform 0.15s;
    box-shadow: 0 4px 20px rgba(124,58,237,0.3);
}
.ch-new-btn:hover { opacity: 0.88; transform: translateY(-1px); color: #fff; box-shadow: 0 6px 24px rgba(124,58,237,0.4); }

.ch-choice-overlay[hidden] { display: none; }
.ch-choice-overlay {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(3, 7, 18, 0.76);
    backdrop-filter: blur(10px);
}
.ch-choice-modal {
    width: min(780px, 100%);
    background: #10131b;
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 16px;
    box-shadow: 0 24px 70px rgba(0,0,0,0.45);
    padding: 24px;
    position: relative;
}
.ch-choice-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.04);
    color: rgba(255,255,255,0.72);
    cursor: pointer;
}
.ch-choice-title { margin: 0; color: #fff; font-size: 1.15rem; font-weight: 800; }
.ch-choice-subtitle { margin: 6px 0 20px; color: rgba(255,255,255,0.48); font-size: 0.86rem; }
.ch-choice-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.ch-choice-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.025);
}
.ch-choice-card:hover { border-color: rgba(124,58,237,0.35); }
.ch-choice-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(124,58,237,0.14);
    color: #a78bfa;
}
.ch-choice-card h3 { margin: 0; color: #fff; font-size: 0.98rem; font-weight: 800; }
.ch-choice-card p { margin: 0; color: rgba(255,255,255,0.62); font-size: 0.82rem; line-height: 1.5; }
.ch-choice-card small { color: rgba(255,255,255,0.38); font-size: 0.74rem; line-height: 1.45; }
.ch-choice-actions { margin-top: auto; padding-top: 4px; }
.ch-empty-action {
    border: 0;
    background: transparent;
    color: var(--ch-purple);
    font-size: 0.8rem;
    margin-top: 8px;
    display: inline-block;
    cursor: pointer;
}

/* ── Filter Bar ──────────────────────────────────── */
.ch-filters {
    background: var(--ch-surf);
    border: 1px solid var(--ch-bord);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;
}
.ch-filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px; }
.ch-filter-label { font-size: 0.69rem; color: rgba(255,255,255,0.38); text-transform: uppercase; letter-spacing: 0.06em; }
.ch-filter-input, .ch-filter-select {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: #fff;
    padding: 8px 12px;
    font-size: 0.84rem;
    outline: none;
    transition: border-color 0.2s;
    width: 100%;
}
.ch-filter-input::placeholder { color: rgba(255,255,255,0.25); }
.ch-filter-input:focus, .ch-filter-select:focus { border-color: var(--ch-teal); }
.ch-filter-select option { background: #1a1b24; color: #fff; }
.ch-filter-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    background: var(--ch-surf); border: 1px solid var(--ch-bord);
    color: rgba(255,255,255,0.7); font-size: 0.82rem; font-weight: 600;
    cursor: pointer; transition: border-color 0.2s, color 0.2s;
    text-decoration: none; white-space: nowrap; flex-shrink: 0;
}
.ch-filter-btn:hover { border-color: var(--ch-teal); color: var(--ch-teal); }
.ch-filter-btn.submit {
    background: var(--ch-purple); border-color: var(--ch-purple); color: #fff;
    font-weight: 700;
}
.ch-filter-btn.submit:hover { opacity: 0.88; color: #fff; }
.ch-method-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin: -4px 0 18px;
}
.ch-method-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 0 13px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.025);
    color: rgba(255,255,255,0.58);
    text-decoration: none;
    font-size: 0.78rem;
    font-weight: 700;
}
.ch-method-tab:hover,
.ch-method-tab.active {
    color: #fff;
    border-color: rgba(124,58,237,0.45);
    background: rgba(124,58,237,0.16);
}
.ch-method-tab span {
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.68);
    font-size: 0.68rem;
}

/* ── Table Wrapper ───────────────────────────────── */
.ch-table-wrap {
    background: var(--ch-surf);
    border: 1px solid var(--ch-bord);
    border-radius: 12px;
    overflow: hidden;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.ch-table { width: 100%; border-collapse: collapse; }
.ch-table thead tr {
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.ch-table thead th {
    font-size: 0.68rem; font-weight: 700; color: rgba(255,255,255,0.38);
    text-transform: uppercase; letter-spacing: 0.08em;
    padding: 12px 16px; text-align: left; white-space: nowrap;
}
.ch-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.15s;
}
.ch-table tbody tr:last-child { border-bottom: none; }
.ch-table tbody tr:hover { background: rgba(255,255,255,0.025); }
.ch-table td { padding: 14px 16px; vertical-align: middle; }

/* ── Charge ID cell ──────────────────────────────── */
.ch-id-main { font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.75); font-family: monospace; }
.ch-id-date { font-size: 0.7rem; color: rgba(255,255,255,0.3); margin-top: 2px; }

/* ── Customer cell ───────────────────────────────── */
.ch-cust-name { font-size: 0.84rem; font-weight: 600; color: rgba(255,255,255,0.8); }
.ch-cust-doc  { font-size: 0.7rem; color: rgba(255,255,255,0.32); margin-top: 2px; }
.ch-cust-none { font-size: 0.8rem; color: rgba(255,255,255,0.25); font-style: italic; }

/* ── Amount cells ────────────────────────────────── */
.ch-amount-gross { font-size: 0.92rem; font-weight: 700; color: #fff; }
.ch-amount-net   { font-size: 0.88rem; font-weight: 700; color: var(--ch-teal); }
.ch-amount-fee   { font-size: 0.68rem; color: rgba(239,68,68,0.7); margin-top: 2px; }

/* ── Method Badge ────────────────────────────────── */
.ch-method {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 50px; font-size: 0.69rem; font-weight: 700;
    letter-spacing: 0.04em;
}
.ch-method.pix  { background: rgba(0,212,170,0.1); color: var(--ch-teal); }
.ch-method.card { background: rgba(99,102,241,0.1); color: #6366f1; }
.ch-method.boleto { background: rgba(251,191,36,0.12); color: var(--ch-amber); }

/* ── Status Badge ────────────────────────────────── */
.ch-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 700;
}
.ch-status.paid     { background: rgba(0,212,170,0.1);  color: var(--ch-teal); }
.ch-status.waiting  { background: rgba(245,158,11,0.1); color: var(--ch-amber); }
.ch-status.expired  { background: rgba(239,68,68,0.1);  color: var(--ch-red); }
.ch-status.cancelled{ background: rgba(239,68,68,0.1);  color: var(--ch-red); }
.ch-status.pending  { background: rgba(139,92,246,0.1); color: var(--ch-purple); }
.ch-status.refunded { background: rgba(99,102,241,0.1); color: #6366f1; }

/* ── Actions ─────────────────────────────────────── */
.ch-action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 8px; font-size: 0.76rem; font-weight: 600;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.65); text-decoration: none;
    transition: border-color 0.2s, color 0.2s;
}
.ch-action-btn:hover { border-color: var(--ch-teal); color: var(--ch-teal); }

/* ── Empty State ─────────────────────────────────── */
.ch-empty {
    text-align: center; padding: 64px 24px;
    color: rgba(255,255,255,0.25);
}
.ch-empty i { font-size: 2.8rem; margin-bottom: 14px; display: block; }
.ch-empty p { font-size: 0.85rem; margin: 0; }

/* ── Pagination wrapper ──────────────────────────── */
.ch-pagination { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.05); }

@media (max-width: 768px) {
    .ch-choice-grid { grid-template-columns: 1fr; }
    .ch-table thead th:nth-child(2),
    .ch-table td:nth-child(2) { display: none; }
    .ch-filters { flex-direction: column; }
    .ch-filter-group { min-width: 100%; }
}

/* 📊 KPIs */
.pix-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.pix-kpi-card {
    background: var(--ch-surf);
    border: 1px solid var(--ch-bord);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pix-kpi-label {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.pix-kpi-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
}
.pix-kpi-icon {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 1.5rem;
    color: rgba(255,255,255,0.05);
}

</style>

@php
    $activeMethod = $activeMethod ?? request('method', 'all');
    $methodTabs = collect([['code' => 'all', 'label' => 'Todas', 'icon_class' => 'fas fa-layer-group']])
        ->merge(($availablePaymentMethods ?? collect())->map(fn ($method) => [
            'code' => $method['code'],
            'label' => $method['label'],
            'icon_class' => $method['icon_class'],
        ]));
    $methodLabels = $methodTabs->pluck('label', 'code')->all() + ['all' => 'Totais'];
    $kpiSuffix = $methodLabels[$activeMethod] ?? 'Totais';
@endphp

{{-- ── PAGE HEADER ─────────────────────────────────── --}}
<div class="v2-page-header" style="flex-shrink: 0; margin: 0; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h1 class="v2-page-title" style="margin-bottom: 2px;">Cobranças</h1>
        <p class="v2-page-subtitle" style="margin: 0;">Gerencie todas as cobranças da sua conta.</p>
    </div>
    <div style="display:flex; gap:12px; align-items: center; flex-shrink: 0;">
        <button type="button" class="v2-btn-primary" onclick="openChargeChoiceModal()" style="height: 36px; padding: 0 16px; font-size: 0.8125rem; gap: 7px; border: 0;">
            <i class="fas fa-plus" style="font-size:0.75rem;"></i> Nova cobrança
        </button>
    </div>
</div>

{{-- ── KPI Cards ─────────────────────────────────────────────────────────── --}}
<div class="ch-method-tabs">
    @foreach($methodTabs as $tab)
        <a href="{{ route('user.charge.index', array_merge(request()->except('method', 'page'), ['method' => $tab['code']])) }}" class="ch-method-tab {{ $activeMethod === $tab['code'] ? 'active' : '' }}">
            <i class="{{ $tab['icon_class'] }}"></i>
            {{ $tab['label'] }}
            <span>{{ number_format((int)($methodCounts[$tab['code']] ?? 0), 0, ',', '.') }}</span>
        </a>
    @endforeach
</div>

<div class="v2-kpi-grid" style="flex-shrink: 0; margin: 0; margin-bottom: 20px;">
    
    <div class="v2-kpi-card" style="border-color: rgba(16,185,129,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(16,185,129,0.1); color: #34d399;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Recebidos {{ $kpiSuffix }}</span>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span>{{ number_format((int)($stats['paid_count'] ?? 0), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="v2-kpi-card" style="border-color: rgba(245,158,11,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(245,158,11,0.1); color: #fbbf24;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Pendentes {{ $kpiSuffix }}</span>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span>{{ number_format((int)($stats['pending_count'] ?? 0), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="v2-kpi-card" style="border-color: rgba(124,58,237,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(124,58,237,0.1); color: #a78bfa;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Volume {{ $kpiSuffix }}</span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">{{ siteCurrency() }} {{ number_format((float)($stats['paid_volume'] ?? 0), 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
    </div>

    <div class="v2-kpi-card" style="border-color: rgba(59,130,246,0.12);">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(59,130,246,0.1); color: #60a5fa;">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Cobranças {{ $kpiSuffix }}</span>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span>{{ number_format((int)($stats['total_count'] ?? 0), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>



{{-- ── FILTERS ──────────────────────────────────────── --}}
<form action="{{ route('user.charge.index') }}" method="get">
<div class="ch-filters">
    <div class="ch-filter-group" style="flex:2;min-width:200px;">
        <label class="ch-filter-label">Busca</label>
        <input type="text" name="search" class="ch-filter-input" placeholder="ID, nome, e-mail, documento..." value="{{ request('search') }}">
    </div>
    <div class="ch-filter-group">
        <label class="ch-filter-label">Status</label>
        <select name="status" class="ch-filter-select">
            <option value="">Todos</option>
            <option value="pending"         {{ request('status') == 'pending'         ? 'selected' : '' }}>Pendente</option>
            <option value="waiting_payment" {{ request('status') == 'waiting_payment' ? 'selected' : '' }}>Aguardando</option>
            <option value="paid"            {{ request('status') == 'paid'            ? 'selected' : '' }}>Pago</option>
            <option value="expired"         {{ request('status') == 'expired'         ? 'selected' : '' }}>Expirado</option>
            <option value="cancelled"       {{ request('status') == 'cancelled'       ? 'selected' : '' }}>Cancelado</option>
            <option value="refunded"        {{ request('status') == 'refunded'        ? 'selected' : '' }}>Reembolsado</option>
        </select>
    </div>
    <div class="ch-filter-group">
        <label class="ch-filter-label">Método</label>
        <select name="method" class="ch-filter-select">
            <option value="all" {{ $activeMethod === 'all' ? 'selected' : '' }}>Todos</option>
            <option value="pix"  {{ $activeMethod === 'pix'  ? 'selected' : '' }}>PIX</option>
            <option value="card" {{ $activeMethod === 'card' ? 'selected' : '' }}>Cartão</option>
            <option value="boleto" {{ $activeMethod === 'boleto' ? 'selected' : '' }}>Boleto</option>
        </select>
    </div>
    <button type="submit" class="ch-filter-btn submit"><i class="fas fa-search"></i> Filtrar</button>
    @if(request()->anyFilled(['search','status','method']))
        <a href="{{ route('user.charge.index') }}" class="ch-filter-btn"><i class="fas fa-times"></i> Limpar</a>
    @endif
</div>
</form>

{{-- ── TABLE ────────────────────────────────────────── --}}
<div class="ch-table-wrap">
    <table class="ch-table">
        <thead>
            <tr>
                <th>ID / Data</th>
                <th>Cliente</th>
                <th>Bruto</th>
                <th>Líquido</th>
                <th>Método</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($charges as $charge)
            <tr>
                {{-- ID / Data --}}
                <td>
                    <div class="ch-id-main">{{ Str::limit($charge->gateway_charge_id ?? $charge->uuid, 20) }}</div>
                    <div class="ch-id-date">{{ $charge->created_at->format('d/m/Y H:i') }}</div>
                </td>

                {{-- Cliente --}}
                <td>
                    @if($charge->customer_name)
                        <div class="ch-cust-name">{{ $charge->customer_name }}</div>
                        @if($charge->customer_document)
                            <div class="ch-cust-doc">{{ $charge->customer_document }}</div>
                        @endif
                    @else
                        <span class="ch-cust-none">Não informado</span>
                    @endif
                </td>

                {{-- Bruto --}}
                <td>
                    <div class="ch-amount-gross">{{ siteCurrency() }} {{ number_format($charge->amount, 2, ',', '.') }}</div>
                </td>

                {{-- Líquido --}}
                <td>
                    <div class="ch-amount-net">{{ siteCurrency() }} {{ number_format($charge->net_amount, 2, ',', '.') }}</div>
                    <div class="ch-amount-fee">-{{ siteCurrency() }} {{ number_format($charge->platform_fee, 2, ',', '.') }} taxa</div>
                </td>

                {{-- Método --}}
                <td>
                    @php($paymentMethod = $charge->payment_method->value ?? $charge->payment_method)
                    @if($paymentMethod === 'pix')
                        <span class="ch-method pix"><i class="fas fa-bolt"></i> PIX</span>
                    @elseif($paymentMethod === 'boleto')
                        <span class="ch-method boleto"><i class="fas fa-barcode"></i> Boleto</span>
                    @else
                        <span class="ch-method card"><i class="fas fa-credit-card"></i> Cartão</span>
                    @endif
                </td>

                {{-- Status --}}
                <td>
                    @switch($charge->status->value)
                        @case('paid')
                            <span class="ch-status paid"><i class="fas fa-check-circle" style="font-size:0.65rem;"></i> Pago</span>
                            @break
                        @case('waiting_payment')
                            <span class="ch-status waiting"><i class="fas fa-clock" style="font-size:0.65rem;"></i> Aguardando</span>
                            @break
                        @case('expired')
                            <span class="ch-status expired"><i class="fas fa-times-circle" style="font-size:0.65rem;"></i> Expirado</span>
                            @break
                        @case('cancelled')
                            <span class="ch-status cancelled"><i class="fas fa-ban" style="font-size:0.65rem;"></i> Cancelado</span>
                            @break
                        @case('refunded')
                            <span class="ch-status refunded"><i class="fas fa-undo" style="font-size:0.65rem;"></i> Reembolsado</span>
                            @break
                        @default
                            <span class="ch-status pending"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Pendente</span>
                    @endswitch
                </td>

                {{-- Ações --}}
                <td>
                    <a href="{{ route('user.charge.show', $charge->id) }}" class="ch-action-btn">
                        <i class="fas fa-eye" style="font-size:0.75rem;"></i> Ver
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="ch-empty">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <p>Nenhuma cobrança encontrada.</p>
                        @if(request()->anyFilled(['search','status','method']))
                            <a href="{{ route('user.charge.index') }}" style="color:var(--ch-purple);font-size:0.8rem;">Limpar filtros</a>
                        @else
                            <button type="button" onclick="openChargeChoiceModal()" class="ch-empty-action">Criar primeira cobrança →</button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($charges->hasPages())
        <div class="ch-pagination">{{ $charges->withQueryString()->links() }}</div>
    @endif
</div>

<div id="charge-choice-modal" class="ch-choice-overlay" hidden>
    <div class="ch-choice-modal" role="dialog" aria-modal="true" aria-labelledby="charge-choice-title">
        <button type="button" class="ch-choice-close" onclick="closeChargeChoiceModal()" aria-label="Fechar">
            <i class="fas fa-times"></i>
        </button>

        <h2 id="charge-choice-title" class="ch-choice-title">Como deseja cobrar?</h2>
        <p class="ch-choice-subtitle">Escolha o fluxo ideal para este recebimento.</p>

        <div class="ch-choice-grid">
            <div class="ch-choice-card">
                <span class="ch-choice-icon"><i class="fas fa-user-check"></i></span>
                <h3>Cobrança manual</h3>
                <p>Cobre um cliente já cadastrado ou cadastre um novo cliente e gere a cobrança imediatamente.</p>
                <small>Para clientes que você já conhece.</small>
                <div class="ch-choice-actions">
                    <a href="{{ route('user.charge.create') }}" class="v2-btn-primary" style="height:36px;padding:0 16px;text-decoration:none;">Continuar</a>
                </div>
            </div>

            <div class="ch-choice-card">
                <span class="ch-choice-icon"><i class="fas fa-link"></i></span>
                <h3>Link de pagamento</h3>
                <p>Crie um link para compartilhar. O cliente preencherá seus próprios dados antes de realizar o pagamento.</p>
                <small>Para compartilhar por WhatsApp, Instagram, e-mail ou qualquer outro canal.</small>
                <div class="ch-choice-actions">
                    <a href="{{ route('user.payment-links.create') }}" class="v2-btn-secondary" style="height:36px;padding:0 16px;text-decoration:none;">Continuar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openChargeChoiceModal() {
    const modal = document.getElementById('charge-choice-modal');
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
}

function closeChargeChoiceModal() {
    const modal = document.getElementById('charge-choice-modal');
    if (!modal) return;
    modal.hidden = true;
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeChargeChoiceModal();
    }
});

document.getElementById('charge-choice-modal')?.addEventListener('click', function (event) {
    if (event.target === this) {
        closeChargeChoiceModal();
    }
});
</script>

@endsection
