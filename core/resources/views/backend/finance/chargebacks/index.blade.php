@extends('backend.finance.index')
@section('finance_title', 'Central de Disputas & Chargebacks')
@section('finance_desc', 'Gestão operacional de contestações, retenções de saldo e disputas nos gateways.')

@section('finance_action')
    <button class="btn btn-outline-primary" onclick="window.location.reload()">
        <i class="fas fa-sync-alt me-1"></i> Atualizar
    </button>
@endsection

@php
    use App\Services\Finance\Formatting\MoneyFormatter;
    use App\Services\Finance\Formatting\TransactionBadgeService;
    use App\Services\Finance\Formatting\TimelineBuilder;
    use App\Enums\Finance\TransactionStatus;
@endphp

@push('style')
<link rel="stylesheet" href="{{ asset('assets/css/admin-enterprise.css') }}">
@endpush

@section('finance_content')

<!-- KPIs -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <x-admin.kpi-card title="Volume em Disputa (Total)" value="{!! MoneyFormatter::formatAbsolute($dashboardData->kpis['total_disputed_volume']) !!}" colorClass="text-danger" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Disputas Abertas (Chargeback)" value="{{ number_format($dashboardData->kpis['count_active']) }}" colorClass="text-warning" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Disputas Ganhas (Won)" value="{{ number_format($dashboardData->kpis['count_won']) }}" colorClass="text-success" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Disputas Perdidas (Lost)" value="{{ number_format($dashboardData->kpis['count_lost']) }}" colorClass="text-danger" />
    </div>
</div>

<!-- Smart Filters -->
<x-admin.smart-filter action="{{ route('admin.finance.chargebacks') }}">
    <div class="col-md-3">
        <label class="form-label">Status da Disputa</label>
        <select name="status" class="form-select">
            <option value="">Todos</option>
            <option value="{{ TransactionStatus::CHARGEBACK->value }}" {{ ($dashboardData->filters['status'] ?? '') == TransactionStatus::CHARGEBACK->value ? 'selected' : '' }}>Chargeback Aberto</option>
            <option value="{{ TransactionStatus::DISPUTED->value }}" {{ ($dashboardData->filters['status'] ?? '') == TransactionStatus::DISPUTED->value ? 'selected' : '' }}>Em Defesa</option>
            <option value="{{ TransactionStatus::WON->value }}" {{ ($dashboardData->filters['status'] ?? '') == TransactionStatus::WON->value ? 'selected' : '' }}>Ganha (Won)</option>
            <option value="{{ TransactionStatus::LOST->value }}" {{ ($dashboardData->filters['status'] ?? '') == TransactionStatus::LOST->value ? 'selected' : '' }}>Perdida (Lost)</option>
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
    $hasData = $dashboardData->chargebacks->count() > 0;
    $headers = ['TRX ID', 'Origem', 'Gateway', 'Valor Retido', 'Status'];
@endphp
<x-admin.data-table :headers="$headers" :hasData="$hasData" :paginator="$dashboardData->chargebacks" emptyMessage="Nenhum chargeback ou disputa" emptySubmessage="As regras de negócio aplicadas não retornaram resultados.">
    @foreach($dashboardData->chargebacks as $trx)
        <tr class="admin-table-row" data-trx="{{ json_encode([
            'id' => $trx->trx_id,
            'date' => $trx->created_at->format('d/m/Y H:i:s'),
            'user' => $trx->user->username ?? 'Desconhecido',
            'gateway' => $trx->gateway->name ?? 'N/A',
            'amount' => $trx->amount,
            'status' => $trx->status,
            'badge' => TransactionBadgeService::getBadge($trx->status),
            'timeline' => TimelineBuilder::buildForTransaction($trx)->toArray()
        ]) }}" onclick="handleDrawerOpen(this)">
            <td>
                <span class="fw-bold">{{ $trx->trx_id }}</span>
                <br>
                <small class="text-muted">{{ $trx->created_at->format('d/m/Y H:i') }}</small>
            </td>
            <td>
                <div class="fw-medium">{{ $trx->user->username ?? 'N/A' }}</div>
            </td>
            <td>
                <div><span class="badge bg-primary text-uppercase">{{ $trx->gateway->name ?? 'N/A' }}</span></div>
            </td>
            <td>
                {!! MoneyFormatter::formatAbsolute($trx->amount) !!}
            </td>
            <td>
                {!! TransactionBadgeService::render($trx->status) !!}
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<!-- Offcanvas Drawer -->
<x-admin.drawer id="chargebackDrawer" title="Resolução de Disputa">
    
    <!-- Quick Context -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background:#f8fafc;">
        <div>
            <div class="text-muted small mb-1">Volume Retido</div>
            <h3 id="drawerAmount" class="mb-0 text-danger"></h3>
        </div>
        <div class="text-end">
            <span id="drawerStatus"></span>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Dados da Operação Contestada</h6>
    <table class="table table-sm table-borderless mb-4">
        <tr>
            <td class="text-muted w-50">TRX Contestada</td>
            <td id="drawerTrxId" class="text-end fw-bold"></td>
        </tr>
        <tr>
            <td class="text-muted">Usuário Alvo</td>
            <td id="drawerUser" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Gateway Origem</td>
            <td id="drawerGateway" class="text-end fw-medium text-uppercase"></td>
        </tr>
    </table>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Histórico da Disputa</h6>
    <div id="drawerTimelineContainer" class="mb-4"></div>

    <!-- Quick Links (Relacionamentos) -->
    <div class="mt-4 pt-3 border-top" id="drawerQuickLinks">
        <button class="btn btn-sm btn-success w-100 mb-2"><i class="fas fa-trophy me-1"></i> Marcar Disputa como GANHA (Release)</button>
        <button class="btn btn-sm btn-danger w-100"><i class="fas fa-times-circle me-1"></i> Marcar Disputa como PERDIDA (Debit)</button>
    </div>
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

        const data = {
            drawerTrxId: raw.id,
            drawerUser: raw.user,
            drawerGateway: raw.gateway,
            drawerAmount: {
                text: 'R$ ' + parseFloat(raw.amount).toFixed(2),
                type: null
            },
            drawerStatus: {
                html: `<span class="${raw.badge.class}"><i class="${raw.badge.icon} me-1"></i> ${raw.badge.label}</span>`
            },
            drawerTimelineContainer: timelineHtml
        };

        openAdminDrawer('chargebackDrawer', data);
    }
</script>
@endpush
