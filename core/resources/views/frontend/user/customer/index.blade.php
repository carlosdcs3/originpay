@php use Illuminate\Support\Str; @endphp
@extends('frontend.layouts.user-v2')
@section('title', 'Clientes')

@section('styles')
<style>
    html, body.v2-dashboard { overflow: hidden !important; scrollbar-width: none !important; -ms-overflow-style: none !important; }
    html::-webkit-scrollbar, body.v2-dashboard::-webkit-scrollbar, .cx-shell::-webkit-scrollbar, .cx-shell *::-webkit-scrollbar { width: 0 !important; height: 0 !important; display: none !important; }
    body.v2-dashboard .v2-main, body.v2-dashboard .v2-content { overflow: hidden !important; }
    .cx-shell { display:flex; flex-direction:column; flex:1; min-height:0; gap:12px; }
    .cx-actions { display:flex; gap:10px; align-items:center; flex-shrink:0; }
    .cx-kpis { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; flex-shrink:0; }
    .cx-kpi { background:var(--ds-bg-card); border:1px solid var(--ds-border-light); border-radius:10px; padding:14px 16px; min-height:94px; display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto 1fr auto; column-gap:12px; align-items:center; }
    .cx-kpi-icon { width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; grid-row:1 / span 2; font-size:.95rem; }
    .cx-kpi-label { color:var(--ds-text-muted); font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; white-space:nowrap; }
    .cx-kpi-value { color:var(--ds-text-main); font-size:1.2rem; line-height:1.15; font-weight:800; margin-top:4px; }
    .cx-kpi-foot { grid-column:1/-1; display:flex; justify-content:space-between; align-items:center; margin-top:12px; color:var(--ds-text-muted); font-size:.72rem; }
    .cx-filter { display:grid; grid-template-columns:1fr 310px auto; gap:12px; align-items:center; flex-shrink:0; }
    .cx-filter select[name="status"] { display:none!important; }
    .cx-control { height:38px; border-radius:9px; border:1px solid var(--ds-border-medium); background:rgba(255,255,255,.03); color:var(--ds-text-secondary); font-size:.8rem; outline:none; }
    .cx-search { position:relative; min-width:0; }
    .cx-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--ds-text-muted); font-size:.8rem; }
    .cx-search input { width:100%; padding:0 14px 0 38px; }
    .cx-control option { background:#11151e; color:#e2e8f0; }
    .cx-date { display:flex; align-items:center; gap:8px; padding:0 12px; }
    .cx-date input { width:116px; border:0; background:transparent; color:var(--ds-text-secondary); outline:none; font-size:.78rem; }
    .cx-panel { background:var(--ds-bg-card); border:1px solid var(--ds-border-light); border-radius:10px; overflow:hidden; display:flex; flex-direction:column; flex:1; min-height:0; }
    .cx-tabs { display:flex; align-items:center; gap:6px; padding:4px 12px 0; border-bottom:1px solid var(--ds-border-light); flex-shrink:0; }
    .cx-tab { display:inline-flex; align-items:center; gap:8px; padding:12px 14px; color:var(--ds-text-muted); text-decoration:none; font-size:.78rem; font-weight:700; border-bottom:2px solid transparent; white-space:nowrap; }
    .cx-tab.active { color:var(--ds-text-main); border-color:var(--ds-primary); }
    .cx-count { min-width:22px; padding:1px 7px; border-radius:999px; text-align:center; color:#fff; background:rgba(255,255,255,.1); font-size:.68rem; font-weight:800; }
    .cx-tab.active .cx-count { background:var(--ds-primary); }
    .cx-table-wrap { overflow:hidden; flex:1; min-height:0; }
    .cx-table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .cx-table th { color:var(--ds-text-muted); background:rgba(255,255,255,.015); border-bottom:1px solid var(--ds-border-light); padding:13px 14px; font-size:.68rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; text-align:left; white-space:nowrap; }
    .cx-table td { border-bottom:1px solid var(--ds-border-light); padding:12px 14px; color:var(--ds-text-secondary); font-size:.8rem; vertical-align:middle; }
    .cx-table tr:hover { background:rgba(255,255,255,.018); }
    .cx-client { display:flex; align-items:center; gap:12px; min-width:0; }
    .cx-avatar { width:34px; height:34px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; color:#fff; font-size:.75rem; font-weight:900; }
    .cx-main { color:var(--ds-text-main); font-weight:750; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .cx-sub { color:var(--ds-text-muted); font-size:.72rem; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .cx-money { color:var(--ds-text-main); font-weight:800; white-space:nowrap; }
    .cx-status { display:inline-flex; align-items:center; gap:6px; justify-content:center; min-width:82px; padding:5px 10px; border-radius:999px; font-size:.7rem; font-weight:800; }
    .cx-status.active { color:#22c55e; background:rgba(34,197,94,.12); }
    .cx-status.inactive { color:#94a3b8; background:rgba(148,163,184,.12); }
    .cx-status.blocked, .cx-status.risk { color:#ef4444; background:rgba(239,68,68,.12); }
    .cx-dot { width:7px; height:7px; border-radius:50%; background:currentColor; }
    .cx-actions-row { display:flex; justify-content:flex-end; gap:7px; }
    .cx-icon-btn { width:32px; height:32px; border-radius:8px; border:1px solid var(--ds-border-medium); background:rgba(255,255,255,.02); color:var(--ds-text-muted); display:inline-flex; align-items:center; justify-content:center; text-decoration:none; }
    .cx-icon-btn:hover { color:var(--ds-text-main); border-color:rgba(124,58,237,.35); }
    .cx-empty { height:100%; min-height:320px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--ds-text-muted); text-align:center; }
    .cx-empty i { font-size:2rem; color:rgba(148,163,184,.35); margin-bottom:12px; }
    .cx-footer { display:flex; justify-content:space-between; align-items:center; min-height:48px; padding:10px 14px; color:var(--ds-text-muted); border-top:1px solid var(--ds-border-light); font-size:.78rem; flex-shrink:0; }
    .cx-pages { display:flex; align-items:center; gap:7px; }
    .cx-page { min-width:34px; height:32px; border-radius:8px; border:1px solid var(--ds-border-medium); background:rgba(255,255,255,.02); color:var(--ds-text-muted); display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; font-size:.76rem; }
    .cx-page.active { background:var(--ds-primary); color:#fff; border-color:var(--ds-primary); }
    .cx-help { display:flex; align-items:center; justify-content:space-between; gap:16px; min-height:54px; padding:12px 16px; border:1px solid var(--ds-border-light); border-radius:10px; background:var(--ds-bg-card); flex-shrink:0; }
    @media (max-width:1400px) { .cx-kpi-value{font-size:1.02rem}.cx-kpi{padding:12px}.cx-table th,.cx-table td{padding-left:10px;padding-right:10px}.cx-filter{grid-template-columns:1fr 290px auto} }
    @media (max-width:768px) {
        .cx-actions {
            display:grid!important;
            grid-template-columns:1fr 1fr!important;
            gap:10px!important;
            width:100%!important;
        }
        .cx-actions .v2-btn-primary,
        .cx-actions .v2-btn-secondary {
            width:100%!important;
            min-width:0!important;
            min-height:42px!important;
            height:auto!important;
            padding:8px 10px!important;
            line-height:1.2!important;
            justify-content:center!important;
            text-align:center!important;
        }
        .cx-kpi {
            min-height:82px!important;
            padding:10px 12px!important;
            border-radius:10px!important;
        }
        .cx-kpi-icon {
            width:30px!important;
            height:30px!important;
            border-radius:8px!important;
            font-size:.82rem!important;
        }
        .cx-kpi-label {
            font-size:.61rem!important;
            line-height:1.2!important;
            white-space:normal!important;
            letter-spacing:.04em!important;
        }
        .cx-kpi-value {
            font-size:1rem!important;
            margin-top:2px!important;
        }
        .cx-kpi-foot {
            display:grid!important;
            grid-template-columns:minmax(0,1fr) auto!important;
            column-gap:10px!important;
            align-items:end!important;
            margin-top:8px!important;
            font-size:.68rem!important;
        }
        .cx-kpi-foot span {
            min-width:0!important;
            line-height:1.25!important;
        }
        .cx-kpi-foot strong {
            justify-self:end!important;
            margin-left:0!important;
            padding:0 4px 0 0!important;
            line-height:1.2!important;
        }
        .cx-filter {
            gap:8px!important;
        }
        .cx-control {
            height:38px!important;
            border-radius:8px!important;
            font-size:.78rem!important;
        }
        .cx-date {
            padding:0 10px!important;
            gap:6px!important;
        }
        .cx-tabs {
            padding:3px 8px 0!important;
            overflow-x:auto!important;
            scrollbar-width:none!important;
        }
        .cx-tabs::-webkit-scrollbar {
            display:none!important;
        }
        .cx-tab {
            padding:10px 12px!important;
            font-size:.72rem!important;
            flex:0 0 auto!important;
        }
        .cx-empty {
            min-height:190px!important;
            padding:24px 14px!important;
        }
        .cx-help {
            align-items:stretch!important;
            padding:14px!important;
            overflow:hidden!important;
        }
        .cx-help .cx-client {
            align-items:flex-start!important;
            width:100%!important;
        }
        .cx-help .cx-client > div:last-child {
            min-width:0!important;
            width:100%!important;
        }
        .cx-help .cx-main,
        .cx-help .cx-sub {
            white-space:normal!important;
            overflow:visible!important;
            text-overflow:clip!important;
            line-height:1.4!important;
        }
        .cx-help .v2-btn-secondary {
            width:100%!important;
            min-height:40px!important;
            justify-content:center!important;
        }
    }
</style>
@endsection

@section('content')
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $tabs = [
        'all' => ['label' => 'Todos', 'count' => $statusCounts['all'] ?? 0],
        'active' => ['label' => 'Ativos', 'count' => $statusCounts['active'] ?? 0],
        'inactive' => ['label' => 'Inativos', 'count' => $statusCounts['inactive'] ?? 0],
        'blocked' => ['label' => 'Bloqueados', 'count' => $statusCounts['blocked'] ?? 0],
        'risk' => ['label' => 'Risco alto', 'count' => $statusCounts['risk'] ?? 0],
    ];
    $activeStatus = request('status', 'all');
    $statusLabel = ['active' => 'Ativo', 'inactive' => 'Inativo', 'blocked' => 'Bloqueado', 'risk' => 'Risco alto'];
    $avatarColors = ['#7c3aed', '#16a34a', '#2563eb', '#dc2626', '#f59e0b', '#4f46e5', '#475569'];
@endphp

<div class="cx-shell">
    <div class="v2-page-header" style="flex-shrink:0;margin:0;justify-content:space-between;align-items:center;">
        <div>
            <h1 class="v2-page-title" style="margin-bottom:2px;">Clientes</h1>
            <p class="v2-page-subtitle" style="margin:0;">Gerencie seus clientes, visualize historico e acompanhe suas operacoes.</p>
        </div>
        <div class="cx-actions">
            <a href="{{ route('user.customer.index', request()->query()) }}" class="v2-btn-secondary" style="height:36px;padding:0 14px;font-size:.8125rem;gap:7px;text-decoration:none;"><i class="fas fa-download" style="font-size:.75rem;"></i> Exportar</a>
            <a href="#" class="v2-btn-primary" style="height:36px;padding:0 16px;font-size:.8125rem;gap:7px;text-decoration:none;"><i class="fas fa-plus" style="font-size:.75rem;"></i> Novo Cliente</a>
        </div>
    </div>

    <div class="cx-kpis">
        <div class="cx-kpi" style="border-color:rgba(124,58,237,.14);"><div class="cx-kpi-icon" style="background:rgba(124,58,237,.16);color:#a78bfa;"><i class="fas fa-users"></i></div><div class="cx-kpi-label">Total de clientes</div><div class="cx-kpi-value">{{ number_format((int)($stats['total'] ?? 0), 0, ',', '.') }}</div><div class="cx-kpi-foot"><span>Ultimos 7 dias</span><strong style="color:var(--ds-success);">+0,0%</strong></div></div>
        <div class="cx-kpi" style="border-color:rgba(34,197,94,.14);"><div class="cx-kpi-icon" style="background:rgba(34,197,94,.14);color:#22c55e;"><i class="far fa-user"></i></div><div class="cx-kpi-label">Clientes ativos</div><div class="cx-kpi-value">{{ number_format((int)($stats['active'] ?? 0), 0, ',', '.') }}</div><div class="cx-kpi-foot"><span>Ultimos 7 dias</span><strong style="color:var(--ds-success);">+0,0%</strong></div></div>
        <div class="cx-kpi" style="border-color:rgba(59,130,246,.14);"><div class="cx-kpi-icon" style="background:rgba(59,130,246,.14);color:#60a5fa;"><i class="far fa-user-circle"></i></div><div class="cx-kpi-label">Novos clientes</div><div class="cx-kpi-value">{{ number_format((int)($stats['new'] ?? 0), 0, ',', '.') }}</div><div class="cx-kpi-foot"><span>Ultimos 7 dias</span><strong style="color:var(--ds-success);">+0</strong></div></div>
        <div class="cx-kpi" style="border-color:rgba(245,158,11,.14);"><div class="cx-kpi-icon" style="background:rgba(245,158,11,.14);color:#f59e0b;"><i class="fas fa-dollar-sign"></i></div><div class="cx-kpi-label">Volume total</div><div class="cx-kpi-value">{{ $money($stats['volume'] ?? 0) }}</div><div class="cx-kpi-foot"><span>Periodo total</span><strong style="color:var(--ds-success);">+0,0%</strong></div></div>
        <div class="cx-kpi" style="border-color:rgba(124,58,237,.14);"><div class="cx-kpi-icon" style="background:rgba(124,58,237,.16);color:#a78bfa;"><i class="fas fa-chart-line"></i></div><div class="cx-kpi-label">Ticket medio</div><div class="cx-kpi-value">{{ $money($stats['ticket'] ?? 0) }}</div><div class="cx-kpi-foot"><span>Periodo total</span><strong style="color:var(--ds-success);">+0,0%</strong></div></div>
    </div>

    <form action="{{ route('user.customer.index') }}" method="GET" class="cx-filter">
        <div class="cx-search"><i class="fas fa-search"></i><input class="cx-control" type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, e-mail, CPF ou ID..."></div>
        <select class="cx-control" name="status" onchange="this.form.submit()">
            <option value="all" @selected($activeStatus === 'all')>Todos os status</option>
            @foreach($tabs as $key => $tab)
                @if($key !== 'all')<option value="{{ $key }}" @selected($activeStatus === $key)>{{ $tab['label'] }}</option>@endif
            @endforeach
        </select>
        <div class="cx-control cx-date"><i class="far fa-calendar" style="color:var(--ds-text-muted);font-size:.76rem;"></i><input type="date" name="date_from" value="{{ request('date_from') }}"><i class="fas fa-arrow-right" style="color:var(--ds-text-muted);font-size:.58rem;"></i><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <button type="submit" class="v2-btn-secondary" style="height:38px;padding:0 16px;font-size:.8rem;gap:8px;"><i class="fas fa-filter" style="font-size:.72rem;"></i> Filtros</button>
    </form>

    <div class="cx-panel">
        <div class="cx-tabs">
            @foreach($tabs as $key => $tab)
                <a href="{{ route('user.customer.index', array_merge(request()->except('status', 'page'), ['status' => $key])) }}" class="cx-tab {{ $activeStatus === $key ? 'active' : '' }}">{{ $tab['label'] }} <span class="cx-count">{{ $tab['count'] }}</span></a>
            @endforeach
        </div>
        <div class="cx-table-wrap">
            <table class="cx-table">
                <thead><tr><th style="width:22%;">Cliente</th><th style="width:25%;">Contato</th><th style="width:20%;">Total transacionado</th><th style="width:15%;">Ultima transacao</th><th style="width:10%;">Status</th><th style="width:8%;text-align:right;">Acoes</th></tr></thead>
                <tbody>
                @forelse($customers as $customer)
                    @php
                        $initials = collect(explode(' ', $customer['name']))->filter()->take(2)->map(fn($part) => mb_substr($part, 0, 1))->implode('');
                        $color = $avatarColors[$loop->index % count($avatarColors)];
                    @endphp
                    <tr>
                        <td><div class="cx-client"><div class="cx-avatar" style="background:{{ $color }};">{{ mb_strtoupper($initials ?: 'CL') }}</div><div style="min-width:0;"><div class="cx-main">{{ $customer['name'] }}</div><div class="cx-sub">ID: {{ Str::limit((string)$customer['id'], 18) }}</div></div></div></td>
                        <td><div class="cx-main">{{ $customer['email'] }}</div><div class="cx-sub">{{ $customer['document'] }}</div></td>
                        <td><div class="cx-money">{{ $money($customer['total_amount']) }}</div><div class="cx-sub">{{ $customer['total_charges'] }} transacoes</div></td>
                        <td><div class="cx-main">{{ $customer['last_charge_at'] ? $customer['last_charge_at']->format('d/m/Y') : '-' }}</div><div class="cx-sub">{{ $customer['last_charge_at'] ? $customer['last_charge_at']->diffForHumans() : 'Sem historico' }}</div></td>
                        <td><span class="cx-status {{ $customer['status'] }}"><span class="cx-dot"></span>{{ $statusLabel[$customer['status']] ?? 'Ativo' }}</span></td>
                        <td><div class="cx-actions-row"><a class="cx-icon-btn" href="#" title="Visualizar"><i class="far fa-eye"></i></a><a class="cx-icon-btn" href="#" title="Mais opcoes"><i class="fas fa-ellipsis-h"></i></a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:0;border-bottom:0;"><div class="cx-empty"><i class="fas fa-users"></i><strong style="color:var(--ds-text-secondary);">Nenhum cliente encontrado</strong><span style="margin-top:6px;">Clientes associados a cobrancas aparecerao aqui.</span></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="cx-footer">
            <span>Mostrando {{ $customers->firstItem() ?? 0 }} a {{ $customers->lastItem() ?? 0 }} de {{ $customers->total() }} clientes</span>
            @if($customers->hasPages())
                <div class="cx-pages">
                    @if($customers->onFirstPage())<span class="cx-page"><i class="fas fa-chevron-left"></i></span>@else<a class="cx-page" href="{{ $customers->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>@endif
                    @foreach(range(1, $customers->lastPage()) as $page)
                        @if($page <= 3 || $page === $customers->lastPage() || abs($page - $customers->currentPage()) <= 1)<a class="cx-page {{ $customers->currentPage() === $page ? 'active' : '' }}" href="{{ $customers->url($page) }}">{{ $page }}</a>@elseif($page === 4)<span class="cx-page">...</span>@endif
                    @endforeach
                    @if($customers->hasMorePages())<a class="cx-page" href="{{ $customers->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>@else<span class="cx-page"><i class="fas fa-chevron-right"></i></span>@endif
                </div>
            @endif
        </div>
    </div>

    <div class="cx-help">
        <div class="cx-client"><div class="cx-avatar" style="background:var(--ds-primary);"><i class="fas fa-info"></i></div><div><div class="cx-main">Sobre os clientes</div><div class="cx-sub">Gerencie seus clientes e acompanhe o desempenho de suas operacoes.</div></div></div>
        <a href="#" class="v2-btn-secondary" style="height:34px;padding:0 14px;font-size:.78rem;gap:8px;color:#a78bfa;">Central de Ajuda <i class="fas fa-external-link-alt" style="font-size:.68rem;"></i></a>
    </div>
</div>
@endsection
