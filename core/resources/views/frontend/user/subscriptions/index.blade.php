@php use App\Enums\CustomerSubscriptionStatus; @endphp
@extends('frontend.layouts.user-v2')
@section('title', 'Assinaturas')

@section('styles')
<style>
    html, body.v2-dashboard { overflow:hidden!important; scrollbar-width:none!important; -ms-overflow-style:none!important; }
    html::-webkit-scrollbar, body.v2-dashboard::-webkit-scrollbar, .sub-shell::-webkit-scrollbar, .sub-shell *::-webkit-scrollbar { width:0!important; height:0!important; display:none!important; }
    body.v2-dashboard .v2-main, body.v2-dashboard .v2-content { overflow:hidden!important; }
    .sub-shell { display:flex; flex-direction:column; flex:1; min-height:0; gap:12px; }
    .sub-actions { display:flex; gap:10px; align-items:center; flex-shrink:0; }
    .sub-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:12px; flex-shrink:0; }
    .sub-kpi { background:var(--ds-bg-card); border:1px solid var(--ds-border-light); border-radius:10px; padding:12px; min-height:88px; display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto 1fr auto; column-gap:10px; align-items:center; }
    .sub-kpi-icon { width:32px; height:32px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; grid-row:1/span 2; font-size:.9rem; }
    .sub-kpi-label { color:var(--ds-text-muted); font-size:.64rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; white-space:nowrap; }
    .sub-kpi-value { color:var(--ds-text-main); font-size:1.05rem; line-height:1.15; font-weight:850; margin-top:4px; white-space:nowrap; }
    .sub-kpi-foot { grid-column:1/-1; margin-top:10px; color:var(--ds-text-muted); font-size:.7rem; }
    .sub-filter { display:grid; grid-template-columns:1fr 160px 150px 286px auto; gap:10px; align-items:center; flex-shrink:0; }
    .sub-control { height:38px; border-radius:9px; border:1px solid var(--ds-border-medium); background:rgba(255,255,255,.03); color:var(--ds-text-secondary); font-size:.8rem; outline:none; }
    .sub-control option { background:#11151e; color:#e2e8f0; }
    .sub-search { position:relative; min-width:0; }
    .sub-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--ds-text-muted); font-size:.8rem; }
    .sub-search input { width:100%; padding:0 14px 0 38px; }
    .sub-date { display:flex; align-items:center; gap:7px; padding:0 10px; }
    .sub-date input { width:108px; border:0; background:transparent; color:var(--ds-text-secondary); outline:none; font-size:.76rem; }
    .sub-panel { background:var(--ds-bg-card); border:1px solid var(--ds-border-light); border-radius:10px; overflow:hidden; display:flex; flex-direction:column; flex:1; min-height:0; }
    .sub-tabs { display:flex; align-items:center; gap:4px; padding:4px 12px 0; border-bottom:1px solid var(--ds-border-light); flex-shrink:0; }
    .sub-tab { display:inline-flex; align-items:center; gap:7px; padding:11px 12px; color:var(--ds-text-muted); text-decoration:none; font-size:.76rem; font-weight:750; border-bottom:2px solid transparent; white-space:nowrap; }
    .sub-tab.active { color:var(--ds-text-main); border-color:var(--ds-primary); }
    .sub-count { min-width:22px; padding:1px 7px; border-radius:999px; text-align:center; color:#fff; background:rgba(255,255,255,.1); font-size:.66rem; font-weight:850; }
    .sub-tab.active .sub-count { background:var(--ds-primary); }
    .sub-table-wrap { overflow:hidden; flex:1; min-height:0; }
    .sub-table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .sub-table th { color:var(--ds-text-muted); background:rgba(255,255,255,.015); border-bottom:1px solid var(--ds-border-light); padding:12px; font-size:.66rem; font-weight:850; letter-spacing:.07em; text-transform:uppercase; text-align:left; white-space:nowrap; }
    .sub-table td { border-bottom:1px solid var(--ds-border-light); padding:11px 12px; color:var(--ds-text-secondary); font-size:.78rem; vertical-align:middle; }
    .sub-table tr:hover { background:rgba(255,255,255,.018); }
    .sub-client { display:flex; align-items:center; gap:10px; min-width:0; }
    .sub-avatar { width:32px; height:32px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; color:#fff; background:rgba(124,58,237,.18); color:#a78bfa; }
    .sub-main { color:var(--ds-text-main); font-weight:760; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .sub-sub { color:var(--ds-text-muted); font-size:.7rem; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .sub-money { color:var(--ds-text-main); font-weight:850; white-space:nowrap; }
    .sub-status { display:inline-flex; align-items:center; justify-content:center; min-width:86px; padding:5px 10px; border-radius:999px; font-size:.68rem; font-weight:850; }
    .sub-status.pending { color:#fbbf24; background:rgba(245,158,11,.12); }
    .sub-status.active { color:#22c55e; background:rgba(34,197,94,.12); }
    .sub-status.past_due, .sub-status.incomplete { color:#ef4444; background:rgba(239,68,68,.12); }
    .sub-status.canceled { color:#94a3b8; background:rgba(148,163,184,.12); }
    .sub-actions-row { display:flex; justify-content:flex-end; gap:7px; }
    .sub-icon-btn { width:31px; height:31px; border-radius:8px; border:1px solid var(--ds-border-medium); background:rgba(255,255,255,.02); color:var(--ds-text-muted); display:inline-flex; align-items:center; justify-content:center; text-decoration:none; }
    .sub-icon-btn:hover { color:var(--ds-text-main); border-color:rgba(124,58,237,.35); }
    .sub-empty { height:100%; min-height:300px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--ds-text-muted); text-align:center; }
    .sub-empty i { font-size:2rem; color:rgba(148,163,184,.35); margin-bottom:12px; }
    .sub-footer { display:flex; justify-content:space-between; align-items:center; min-height:46px; padding:9px 14px; color:var(--ds-text-muted); border-top:1px solid var(--ds-border-light); font-size:.76rem; flex-shrink:0; }
    .sub-pages { display:flex; align-items:center; gap:7px; }
    .sub-page { min-width:32px; height:30px; border-radius:8px; border:1px solid var(--ds-border-medium); background:rgba(255,255,255,.02); color:var(--ds-text-muted); display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:750; font-size:.74rem; }
    .sub-page.active { background:var(--ds-primary); color:#fff; border-color:var(--ds-primary); }
    .sub-help { display:flex; align-items:center; justify-content:space-between; gap:16px; min-height:52px; padding:11px 14px; border:1px solid var(--ds-border-light); border-radius:10px; background:var(--ds-bg-card); flex-shrink:0; }
    .sub-danger-form { display:inline; }
    .sub-danger-form button { color:#f87171; }
    @media (max-width:1500px) { .sub-kpi-value{font-size:.94rem}.sub-kpi{padding:10px}.sub-table th,.sub-table td{padding-left:9px;padding-right:9px}.sub-filter{grid-template-columns:1fr 145px 135px 260px auto} }
    @media (max-width:768px) {
        .sub-actions { width:100%; display:grid!important; grid-template-columns:1fr!important; }
        .sub-actions .v2-btn-primary { width:100%!important; min-height:42px!important; height:auto!important; justify-content:center!important; }
        .sub-filter { grid-template-columns:1fr!important; gap:10px!important; }
        .sub-filter select[name="status"],
        .sub-filter select[name="payment_method"] { display:none!important; }
        .sub-kpis { grid-template-columns:repeat(2,minmax(0,1fr))!important; gap:10px!important; }
        .sub-kpi { min-height:96px!important; padding:12px!important; align-content:start!important; }
        .sub-kpi-label { white-space:normal!important; line-height:1.25!important; }
        .sub-kpi-foot { line-height:1.35!important; margin-top:8px!important; }
        .sub-tabs { overflow-x:auto!important; overflow-y:hidden!important; padding:4px 12px 0!important; flex-wrap:nowrap!important; scrollbar-width:none!important; }
        .sub-tabs::-webkit-scrollbar { display:none!important; }
        .sub-tab { flex:0 0 auto!important; }
        .sub-empty { min-height:260px!important; padding:28px 14px!important; }
        .sub-empty .v2-btn-primary { width:100%!important; max-width:260px!important; min-height:42px!important; }
        .sub-help { display:grid!important; grid-template-columns:1fr!important; align-items:stretch!important; padding:14px!important; overflow:hidden!important; }
        .sub-help .sub-client { align-items:flex-start!important; }
        .sub-help .sub-client > div:last-child { min-width:0!important; }
        .sub-help .sub-main,
        .sub-help .sub-sub { white-space:normal!important; overflow:visible!important; text-overflow:clip!important; line-height:1.4!important; }
        .sub-help .v2-btn-secondary { width:100%!important; justify-content:center!important; min-height:40px!important; }
    }
</style>
@endsection

@section('content')
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $developerDocsUrl = Route::has('user.developer.docs.index') ? route('user.developer.docs.index') : route('user.developer.api-keys.index');
    $activeStatus = request('status', 'all');
    $tabs = [
        'all' => ['label' => 'Todas', 'count' => $statusCounts['all'] ?? 0],
        CustomerSubscriptionStatus::PENDING->value => ['label' => 'Pendentes', 'count' => $statusCounts['pending'] ?? 0],
        CustomerSubscriptionStatus::ACTIVE->value => ['label' => 'Ativas', 'count' => $statusCounts['active'] ?? 0],
        CustomerSubscriptionStatus::PAST_DUE->value => ['label' => 'Inadimplentes', 'count' => $statusCounts['past_due'] ?? 0],
        CustomerSubscriptionStatus::CANCELED->value => ['label' => 'Canceladas', 'count' => $statusCounts['canceled'] ?? 0],
        CustomerSubscriptionStatus::INCOMPLETE->value => ['label' => 'Incompletas', 'count' => $statusCounts['incomplete'] ?? 0],
    ];
    $statusLabels = [
        'pending' => 'Pendente',
        'active' => 'Ativa',
        'past_due' => 'Inadimplente',
        'canceled' => 'Cancelada',
        'incomplete' => 'Incompleta',
    ];
    $methodLabels = ['pix' => 'Pix', 'card' => 'Cartao', 'boleto' => 'Boleto', 'crypto' => 'Crypto'];
@endphp

<div class="sub-shell">
    <div class="v2-page-header" style="flex-shrink:0;margin:0;justify-content:space-between;align-items:center;">
        <div>
            <h1 class="v2-page-title" style="margin-bottom:2px;">Assinaturas</h1>
            <p class="v2-page-subtitle" style="margin:0;">Acompanhe recorrencias, proximas cobrancas e inadimplencia dos seus clientes.</p>
        </div>
        <div class="sub-actions">
            <a href="{{ route('user.payment-links.subscriptions.create') }}" class="v2-btn-primary" style="height:36px;padding:0 16px;font-size:.8125rem;gap:7px;text-decoration:none;">
                <i class="fas fa-plus" style="font-size:.75rem;"></i> Nova assinatura
            </a>
        </div>
    </div>

    <div class="sub-kpis">
        <div class="sub-kpi" style="border-color:rgba(124,58,237,.14);"><div class="sub-kpi-icon" style="background:rgba(124,58,237,.16);"><i class="fas fa-sync-alt"></i></div><div class="sub-kpi-label">Receita recorrente</div><div class="sub-kpi-value">{{ $money($metrics['mrr'] ?? 0) }}</div><div class="sub-kpi-foot">Assinaturas ativas e em atraso</div></div>
        <div class="sub-kpi" style="border-color:rgba(34,197,94,.14);"><div class="sub-kpi-icon" style="background:rgba(34,197,94,.14);color:#22c55e;"><i class="fas fa-check"></i></div><div class="sub-kpi-label">Ativas</div><div class="sub-kpi-value">{{ number_format((int)($metrics['active'] ?? 0), 0, ',', '.') }}</div><div class="sub-kpi-foot">Clientes com assinatura ativa</div></div>
        <div class="sub-kpi" style="border-color:rgba(59,130,246,.14);"><div class="sub-kpi-icon" style="background:rgba(59,130,246,.14);color:#60a5fa;"><i class="far fa-calendar"></i></div><div class="sub-kpi-label">Renovações hoje</div><div class="sub-kpi-value">{{ number_format((int)($metrics['renewals_today'] ?? 0), 0, ',', '.') }}</div><div class="sub-kpi-foot">Cobranças previstas para hoje</div></div>
        <div class="sub-kpi" style="border-color:rgba(239,68,68,.14);"><div class="sub-kpi-icon" style="background:rgba(239,68,68,.14);color:#ef4444;"><i class="fas fa-exclamation"></i></div><div class="sub-kpi-label">Inadimplentes</div><div class="sub-kpi-value">{{ number_format((int)($metrics['past_due'] ?? 0), 0, ',', '.') }}</div><div class="sub-kpi-foot">Assinaturas com pagamento em atraso</div></div>
        <div class="sub-kpi" style="border-color:rgba(148,163,184,.14);"><div class="sub-kpi-icon" style="background:rgba(148,163,184,.14);color:#94a3b8;"><i class="fas fa-ban"></i></div><div class="sub-kpi-label">Canceladas</div><div class="sub-kpi-value">{{ number_format((int)($metrics['canceled'] ?? 0), 0, ',', '.') }}</div><div class="sub-kpi-foot">Histórico total</div></div>
        <div class="sub-kpi" style="border-color:rgba(245,158,11,.14);"><div class="sub-kpi-icon" style="background:rgba(245,158,11,.14);color:#f59e0b;"><i class="fas fa-ticket-alt"></i></div><div class="sub-kpi-label">Ticket médio</div><div class="sub-kpi-value">{{ $money($metrics['average_ticket'] ?? 0) }}</div><div class="sub-kpi-foot">Média das assinaturas ativas e em atraso</div></div>
    </div>

    <form action="{{ route('user.subscriptions.index') }}" method="GET" class="sub-filter">
        <div class="sub-search"><i class="fas fa-search"></i><input class="sub-control" type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por cliente, e-mail, documento ou ID..."></div>
        <select class="sub-control" name="status" onchange="this.form.submit()">
            <option value="all" @selected($activeStatus === 'all')>Todos os status</option>
            @foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected($activeStatus === $key)>{{ $label }}</option>@endforeach
        </select>
        <select class="sub-control" name="payment_method" onchange="this.form.submit()">
            <option value="all" @selected(request('payment_method', 'all') === 'all')>Todos metodos</option>
            @foreach($methodLabels as $key => $label)<option value="{{ $key }}" @selected(request('payment_method') === $key)>{{ $label }}</option>@endforeach
        </select>
        <div class="sub-control sub-date"><i class="far fa-calendar"></i><input type="date" name="date_from" value="{{ request('date_from') }}"><i class="fas fa-arrow-right" style="font-size:.58rem;"></i><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <button type="submit" class="v2-btn-secondary" style="height:38px;padding:0 16px;font-size:.8rem;gap:8px;"><i class="fas fa-filter" style="font-size:.72rem;"></i> Filtros</button>
    </form>

    <div class="sub-panel">
        <div class="sub-tabs">
            @foreach($tabs as $key => $tab)
                <a href="{{ route('user.subscriptions.index', array_merge(request()->except('status', 'page'), ['status' => $key])) }}" class="sub-tab {{ $activeStatus === $key ? 'active' : '' }}">{{ $tab['label'] }} <span class="sub-count">{{ $tab['count'] }}</span></a>
            @endforeach
        </div>
        <div class="sub-table-wrap">
            <table class="sub-table">
                <thead><tr><th style="width:23%;">Cliente</th><th style="width:19%;">Plano / descricao</th><th style="width:11%;">Valor</th><th style="width:10%;">Metodo</th><th style="width:15%;">Proxima cobranca</th><th style="width:11%;">Status</th><th style="width:11%;text-align:right;">Acoes</th></tr></thead>
                <tbody>
                @forelse($subscriptions as $subscription)
                    @php
                        $status = $subscription->status?->value ?? (string)$subscription->status;
                        $invoice = $subscription->latestInvoice;
                        $description = $subscription->items->first()?->description ?: $subscription->description ?: 'Assinatura recorrente';
                    @endphp
                    <tr>
                        <td><div class="sub-client"><div class="sub-avatar"><i class="fas fa-user"></i></div><div style="min-width:0;"><div class="sub-main">{{ $subscription->customer_name }}</div><div class="sub-sub">{{ $subscription->customer_email }} | {{ $subscription->customer_document ?: 'sem documento' }}</div></div></div></td>
                        <td><div class="sub-main">{{ $description }}</div><div class="sub-sub">{{ $subscription->uuid }}</div></td>
                        <td><div class="sub-money">{{ $money($subscription->amount) }}</div><div class="sub-sub">{{ $subscription->interval_count }}x {{ $subscription->interval?->value ?? $subscription->interval }}</div></td>
                        <td><div class="sub-main">{{ $methodLabels[$subscription->payment_method] ?? ucfirst($subscription->payment_method) }}</div><div class="sub-sub">{{ $subscription->currency }}</div></td>
                        <td><div class="sub-main">{{ $subscription->next_billing_at ? $subscription->next_billing_at->format('d/m/Y') : '-' }}</div><div class="sub-sub">{{ $invoice?->charge ? 'Charge ' . $invoice->charge->uuid : 'Sem charge vinculada' }}</div></td>
                        <td><span class="sub-status {{ $status }}">{{ uiStatusLabel($status) }}</span></td>
                        <td><div class="sub-actions-row">
                            <a class="sub-icon-btn" href="{{ route('user.subscriptions.show', $subscription->uuid) }}" title="Visualizar"><i class="far fa-eye"></i></a>
                            @if($invoice?->charge && Route::has('user.charge.show'))
                                <a class="sub-icon-btn" href="{{ route('user.charge.show', $invoice->charge->id) }}" title="Ver cobranca"><i class="fas fa-receipt"></i></a>
                            @else
                                <button type="button" class="sub-icon-btn" title="Sem cobranca vinculada" disabled style="opacity:.45;cursor:not-allowed;"><i class="fas fa-receipt"></i></button>
                            @endif
                            @if($status !== 'canceled')
                                <form class="sub-danger-form" method="POST" action="{{ route('user.subscriptions.cancel', $subscription->uuid) }}">@csrf<button class="sub-icon-btn" title="Cancelar agora" onclick="return confirm('Cancelar esta assinatura imediatamente?')"><i class="fas fa-ban"></i></button></form>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:0;border-bottom:0;"><div class="sub-empty"><i class="fas fa-sync-alt"></i><strong style="color:var(--ds-text-secondary);">Nenhuma assinatura encontrada</strong><span style="margin-top:6px;">Crie assinaturas recorrentes pela API para acompanhar os ciclos aqui.</span><a href="{{ $developerDocsUrl }}" class="v2-btn-primary" style="margin-top:14px;height:34px;padding:0 14px;text-decoration:none;">Ver documentacao da API</a></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="sub-footer">
            <span>Mostrando {{ $subscriptions->firstItem() ?? 0 }} a {{ $subscriptions->lastItem() ?? 0 }} de {{ $subscriptions->total() }} assinaturas</span>
            @if($subscriptions->hasPages())
                <div class="sub-pages">
                    @if($subscriptions->onFirstPage())<span class="sub-page"><i class="fas fa-chevron-left"></i></span>@else<a class="sub-page" href="{{ $subscriptions->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>@endif
                    @foreach(range(1, $subscriptions->lastPage()) as $page)
                        @if($page <= 3 || $page === $subscriptions->lastPage() || abs($page - $subscriptions->currentPage()) <= 1)<a class="sub-page {{ $subscriptions->currentPage() === $page ? 'active' : '' }}" href="{{ $subscriptions->url($page) }}">{{ $page }}</a>@elseif($page === 4)<span class="sub-page">...</span>@endif
                    @endforeach
                    @if($subscriptions->hasMorePages())<a class="sub-page" href="{{ $subscriptions->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>@else<span class="sub-page"><i class="fas fa-chevron-right"></i></span>@endif
                </div>
            @endif
        </div>
    </div>

    <div class="sub-help">
        <div class="sub-client"><div class="sub-avatar"><i class="fas fa-info"></i></div><div><div class="sub-main">Sobre assinaturas</div><div class="sub-sub">Assinaturas sao criadas via API e renovadas automaticamente pelo motor de recorrencia.</div></div></div>
        <a href="{{ $developerDocsUrl }}" class="v2-btn-secondary" style="height:34px;padding:0 14px;font-size:.78rem;gap:8px;color:#a78bfa;text-decoration:none;">Documentacao <i class="fas fa-external-link-alt" style="font-size:.68rem;"></i></a>
    </div>
</div>
@endsection
