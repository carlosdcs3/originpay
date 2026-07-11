@extends('backend.layouts.app')
@section('title', $pageTitle)

@push('style')
<link rel="stylesheet" href="{{ asset('assets/css/admin-enterprise.css') }}">
@endpush

@section('content')

<!-- 1. Alerts -->
@if($activeAlerts ?? false)
    @foreach($activeAlerts as $alert)
        <div class="alert alert-{{ $alert->severity === 'CRITICAL' ? 'danger' : 'warning' }} alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <div>
                <strong>{{ $alert->category }} Alert:</strong> {{ $alert->message }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endforeach
@endif

<!-- 2. Hero Section -->
<x-admin.page-hero title="Ledger (Histórico Imutável)" description="Rastreabilidade completa de todas as movimentações financeiras da plataforma.">
    <button class="btn btn-primary" onclick="window.location.reload()">
        <i class="fas fa-sync-alt me-1"></i> Refresh
    </button>
</x-admin.page-hero>

<!-- 3. KPIs -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <x-admin.kpi-card title="Volume Total de Entradas" value="{{ site_currency() }} {{ number_format($dashboardData->kpis['total_in'], 2) }}" colorClass="amount-in" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Volume Total de Saídas" value="{{ site_currency() }} {{ number_format($dashboardData->kpis['total_out'], 2) }}" colorClass="amount-out" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Volume Líquido" value="{{ site_currency() }} {{ number_format($dashboardData->kpis['net_volume'], 2) }}" colorClass="{{ $dashboardData->kpis['net_volume'] >= 0 ? 'text-primary' : 'text-danger' }}" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Qtd Transações" value="{{ number_format($dashboardData->kpis['count']) }}" colorClass="text-dark" />
    </div>
</div>

<!-- 4. Smart Filters -->
<x-admin.smart-filter action="{{ route('admin.finance.ledger') }}">
    <div class="col-md-3">
        <label class="form-label">Gateway</label>
        <select name="gateway_id" class="form-select">
            <option value="">Todos</option>
            @foreach($dashboardData->activeGateways as $gw)
                <option value="{{ $gw['id'] }}" {{ ($dashboardData->filters['gateway_id'] ?? '') == $gw['id'] ? 'selected' : '' }}>
                    {{ $gw['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Operação</label>
        <input type="text" name="operation" class="form-control" placeholder="Ex: PIX_CHARGE" value="{{ $dashboardData->filters['operation'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Reference ID (Gateway)</label>
        <input type="text" name="provider_reference" class="form-control" placeholder="Provider ID" value="{{ $dashboardData->filters['provider_reference'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Data Inicial</label>
        <input type="date" name="start_date" class="form-control" value="{{ $dashboardData->filters['start_date'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Data Final</label>
        <input type="date" name="end_date" class="form-control" value="{{ $dashboardData->filters['end_date'] ?? '' }}">
    </div>
</x-admin.smart-filter>

<!-- 5. DataTable -->
@php
    use App\Services\Finance\Formatting\MoneyFormatter;
    use App\Services\Finance\Formatting\TransactionBadgeService;
    use App\Services\Finance\Formatting\TimelineBuilder;
    $hasData = $dashboardData->transactions->count() > 0;
    $headers = ['TRX ID', 'Data', 'Usuário', 'Gateway / Op', 'Valor Liquido', 'Status'];
@endphp
<x-admin.data-table :headers="$headers" :hasData="$hasData" :paginator="$dashboardData->transactions" emptyMessage="Nenhuma transação encontrada" emptySubmessage="Ajuste os filtros acima para encontrar registros no Ledger.">
    @foreach($dashboardData->transactions as $trx)
        <tr class="admin-table-row" data-trx="{{ json_encode([
            'id' => $trx->trx_id,
            'date' => $trx->created_at->format('d/m/Y H:i:s'),
            'user' => $trx->user->username ?? 'N/A',
            'wallet_id' => $trx->wallet_id,
            'gateway' => $trx->gateway->name ?? 'N/A',
            'operation' => $trx->operation ?? 'N/A',
            'provider_ref' => $trx->provider_reference ?? 'N/A',
            'amount' => $trx->amount,
            'fee' => $trx->charge ?? 0,
            'net_amount' => $trx->net_amount,
            'type' => $trx->trx_type,
            'status' => $trx->status,
            'badge' => TransactionBadgeService::getBadge($trx->status),
            'current_gw_avail' => $trx->current_gateway_balance->available ?? '0.00',
            'charge_id' => $trx->charge_id,
            'withdraw_id' => $trx->withdraw_id,
            'timeline' => TimelineBuilder::buildForTransaction($trx)->toArray()
        ]) }}" onclick="handleDrawerOpen(this)">
            <td>
                <span class="fw-bold">{{ $trx->trx_id }}</span>
            </td>
            <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $trx->user->username ?? 'Sistema' }}</td>
            <td>
                <div><span class="badge bg-primary">{{ $trx->gateway->name ?? 'N/A' }}</span></div>
                <small class="text-muted">{{ $trx->operation ?? 'N/A' }}</small>
            </td>
            <td>
                {!! MoneyFormatter::format($trx->trx_type == '+' ? $trx->net_amount : -$trx->net_amount, null, true) !!}
            </td>
            <td>
                {!! TransactionBadgeService::render($trx->status) !!}
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<!-- 6. Offcanvas Drawer -->
<x-admin.drawer id="ledgerDrawer" title="Detalhes da Transação">
    
    <!-- Quick Context -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background:#f8fafc;">
        <div>
            <h3 id="drawerAmount" class="mb-0"></h3>
            <span id="drawerStatus"></span>
        </div>
        <div class="text-end">
            <div class="text-muted small">TRX ID</div>
            <strong id="drawerTrxId"></strong>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Metadados Imutáveis</h6>
    <table class="table table-sm table-borderless mb-4">
        <tr>
            <td class="text-muted w-50">Data/Hora</td>
            <td id="drawerDate" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Usuário / Wallet</td>
            <td id="drawerUser" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Gateway</td>
            <td id="drawerGateway" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Operação</td>
            <td id="drawerOp" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Provider Reference</td>
            <td id="drawerProvRef" class="text-end fw-medium text-break"></td>
        </tr>
        <tr>
            <td class="text-muted">Taxa (Fee)</td>
            <td id="drawerFee" class="text-end fw-medium"></td>
        </tr>
    </table>

    <!-- Current Context (Wallet Balance) -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-wallet me-2"></i>Contexto Atual (Wallet Balance)</h6>
        <p class="mb-0 small">
            Neste exato momento, o saldo alocado neste gateway 
            (<strong><span id="ctxGatewayName"></span></strong>) 
            para esta carteira é de:
            <strong class="d-block mt-1 fs-5 text-dark" id="ctxGatewayAvail"></strong>
        </p>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Timeline & Auditoria</h6>
    <div id="drawerTimelineContainer"></div>

    <!-- Quick Links -->
    <div class="mt-4 pt-3 border-top" id="drawerQuickLinks"></div>
</x-admin.drawer>

@endsection

@push('script')
<script src="{{ asset('assets/js/admin-drawer.js') }}"></script>
<script>
    function handleDrawerOpen(row) {
        const raw = JSON.parse(row.getAttribute('data-trx'));
        
        let links = '';
        if (raw.charge_id) {
            links += `<a href="/admin/deposit/details/${raw.charge_id}" class="btn btn-sm btn-outline-primary me-2"><i class="fas fa-eye"></i> Ver Charge</a>`;
        }
        if (raw.withdraw_id) {
            links += `<a href="/admin/withdraw/details/${raw.withdraw_id}" class="btn btn-sm btn-outline-primary me-2"><i class="fas fa-eye"></i> Ver Saque</a>`;
        }
        
        let timelineHtml = '<x-admin.timeline>';
        raw.timeline.forEach(item => {
            timelineHtml += `<x-admin.timeline-item title="${item.title}" subtitle="${item.subtitle}"></x-admin.timeline-item>`;
        });
        timelineHtml += '</x-admin.timeline>';

        const data = {
            drawerTrxId: raw.id,
            drawerDate: raw.date,
            drawerUser: raw.user + ' (W: ' + raw.wallet_id + ')',
            drawerGateway: raw.gateway,
            drawerOp: raw.operation,
            drawerProvRef: raw.provider_ref,
            drawerFee: raw.fee,
            ctxGatewayName: raw.gateway,
            ctxGatewayAvail: 'R$ ' + parseFloat(raw.current_gw_avail).toFixed(2),
            drawerAmount: {
                text: raw.type + ' R$ ' + parseFloat(raw.net_amount).toFixed(2),
                type: raw.type
            },
            drawerStatus: {
                html: `<span class="${raw.badge.class}"><i class="${raw.badge.icon} me-1"></i> ${raw.badge.label}</span>`
            },
            drawerTimelineContainer: timelineHtml,
            drawerQuickLinks: links
        };

        openAdminDrawer('ledgerDrawer', data);
    }
</script>
@endpush
