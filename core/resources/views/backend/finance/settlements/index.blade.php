@extends('backend.layouts.app')
@section('title', $pageTitle)

@php
    use App\Services\Finance\Formatting\MoneyFormatter;
    use App\Services\Finance\Formatting\TransactionBadgeService;
    use App\Services\Finance\Formatting\TimelineBuilder;
    use App\Enums\Finance\TransactionStatus;
@endphp

@push('style')
<link rel="stylesheet" href="{{ asset('assets/css/admin-enterprise.css') }}">
@endpush

@section('content')

<!-- Hero Section -->
<x-admin.page-hero title="Repasses (Settlements)" description="Liquidação financeira, retenções em carteira e conciliação de pagamentos aos usuários.">
    <button class="btn btn-outline-light" onclick="window.location.reload()">
        <i class="fas fa-sync-alt me-1"></i> Atualizar
    </button>
</x-admin.page-hero>

<!-- KPIs -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <x-admin.kpi-card title="Volume Líquido Processado" value="{!! MoneyFormatter::formatAbsolute($dashboardData->kpis['total_net']) !!}" colorClass="text-primary" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Repasses Pendentes (Volume)" value="{!! MoneyFormatter::formatAbsolute($dashboardData->kpis['total_pending']) !!}" colorClass="text-warning" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Repasses Pagos (Volume)" value="{!! MoneyFormatter::formatAbsolute($dashboardData->kpis['total_paid']) !!}" colorClass="text-success" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Agendamentos Pendentes" value="{{ number_format($dashboardData->kpis['count_pending']) }}" colorClass="{{ $dashboardData->kpis['count_pending'] > 0 ? 'text-warning' : 'text-success' }}" />
    </div>
</div>

<!-- Smart Filters -->
<x-admin.smart-filter action="{{ route('admin.finance.settlements.index') }}">
    <div class="col-md-3">
        <label class="form-label">Status do Repasse</label>
        <select name="status" class="form-select">
            <option value="">Todos</option>
            <option value="{{ TransactionStatus::PENDING->value }}" {{ ($dashboardData->filters['status'] ?? '') == TransactionStatus::PENDING->value ? 'selected' : '' }}>Agendado (Pendente)</option>
            <option value="{{ TransactionStatus::SUCCEEDED->value }}" {{ ($dashboardData->filters['status'] ?? '') == TransactionStatus::SUCCEEDED->value ? 'selected' : '' }}>Liquidado (Pago)</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Data Inicial</label>
        <input type="date" name="start_date" class="form-control" value="{{ $dashboardData->filters['start_date'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Data Final</label>
        <input type="date" name="end_date" class="form-control" value="{{ $dashboardData->filters['end_date'] ?? '' }}">
    </div>
</x-admin.smart-filter>

<!-- DataTable -->
@php
    $hasData = $dashboardData->settlements->count() > 0;
    $headers = ['ID', 'Data Criação', 'Usuário', 'Gateway Destino', 'Valor Liquido', 'Status'];
@endphp
<x-admin.data-table :headers="$headers" :hasData="$hasData" :paginator="$dashboardData->settlements" emptyMessage="Nenhum repasse" emptySubmessage="As regras de negócio aplicadas não retornaram resultados.">
    @foreach($dashboardData->settlements as $settlement)
        <tr class="admin-table-row" data-trx="{{ json_encode([
            'id' => $settlement->id,
            'date' => $settlement->created_at->format('d/m/Y H:i:s'),
            'user' => $settlement->user->username ?? 'Desconhecido',
            'gateway' => $settlement->gateway->name ?? 'N/A',
            'net_amount' => $settlement->net_amount,
            'status' => $settlement->status,
            'badge' => TransactionBadgeService::getBadge($settlement->status),
            'timeline' => TimelineBuilder::buildForSettlement($settlement)->toArray(),
            'pay_route' => route('admin.finance.settlements.pay', $settlement->id)
        ]) }}" onclick="handleDrawerOpen(this)">
            <td>
                <span class="fw-bold">REP-{{ $settlement->id }}</span>
            </td>
            <td>
                <span class="fw-medium">{{ $settlement->created_at->format('d/m/Y H:i') }}</span>
            </td>
            <td>
                <div class="fw-medium">{{ $settlement->user->username ?? 'N/A' }}</div>
            </td>
            <td>
                <div><span class="badge bg-primary text-uppercase">{{ $settlement->gateway->name ?? 'N/A' }}</span></div>
            </td>
            <td>
                {!! MoneyFormatter::formatAbsolute($settlement->net_amount) !!}
            </td>
            <td>
                {!! TransactionBadgeService::render($settlement->status) !!}
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<!-- Offcanvas Drawer -->
<x-admin.drawer id="settlementDrawer" title="Gestão de Liquidação (Repasse)">
    
    <!-- Quick Context -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background:#f8fafc;">
        <div>
            <div class="text-muted small mb-1">Valor a Liquidar</div>
            <h3 id="drawerAmount" class="mb-0 text-dark fw-bold"></h3>
        </div>
        <div class="text-end">
            <span id="drawerStatus"></span>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Dados do Repasse</h6>
    <table class="table table-sm table-borderless mb-4">
        <tr>
            <td class="text-muted w-50">Repasse ID</td>
            <td id="drawerTrxId" class="text-end fw-bold"></td>
        </tr>
        <tr>
            <td class="text-muted">Usuário Beneficiário</td>
            <td id="drawerUser" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Gateway de Origem</td>
            <td id="drawerGateway" class="text-end fw-medium text-uppercase"></td>
        </tr>
    </table>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Linha do Tempo (Auditoria)</h6>
    <div id="drawerTimelineContainer" class="mb-4"></div>

    <!-- Quick Links (Ações) -->
    <div class="mt-4 pt-3 border-top" id="drawerQuickLinks"></div>
</x-admin.drawer>

@endsection

@push('script')
<script src="{{ asset('assets/js/admin-drawer.js') }}"></script>
<script>
    function handleDrawerOpen(row) {
        const raw = JSON.parse(row.getAttribute('data-trx'));
        
        let timelineHtml = '<x-admin.timeline>';
        raw.timeline.forEach(item => {
            timelineHtml += `<x-admin.timeline-item title="${item.title}" subtitle="${item.subtitle}"></x-admin.timeline-item>`;
        });
        timelineHtml += '</x-admin.timeline>';

        let actions = '';
        if (raw.status === 'pending') {
            actions = `
                <form action="${raw.pay_route}" method="POST" class="d-inline w-100" onsubmit="return confirm('Deseja realmente liquidar este repasse agora? Esta ação debitará o saldo da carteira em lock-for-update e registrará no Ledger.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="fas fa-check-circle me-1"></i> Liquidar Repasse Agora
                    </button>
                </form>
            `;
        } else {
            actions = `<div class="alert alert-secondary text-center small border-0 p-2"><i class="fas fa-info-circle me-1"></i> Repasse já liquidado. Nenhuma ação necessária.</div>`;
        }

        const data = {
            drawerTrxId: 'REP-' + raw.id,
            drawerUser: raw.user,
            drawerGateway: raw.gateway,
            drawerAmount: {
                text: 'R$ ' + parseFloat(raw.net_amount).toFixed(2),
                type: null
            },
            drawerStatus: {
                html: `<span class="${raw.badge.class}"><i class="${raw.badge.icon} me-1"></i> ${raw.badge.label}</span>`
            },
            drawerTimelineContainer: timelineHtml,
            drawerQuickLinks: actions
        };

        openAdminDrawer('settlementDrawer', data);
    }
</script>
@endpush
