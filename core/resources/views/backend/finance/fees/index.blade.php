@extends('backend.layouts.app')
@section('title', $pageTitle)

@php
    use App\Services\Finance\Formatting\MoneyFormatter;
    use App\Services\Finance\Formatting\TransactionBadgeService;
    use App\Services\Finance\Formatting\TimelineBuilder;
    use App\Enums\Finance\FeeStatus;
@endphp

@push('style')
<link rel="stylesheet" href="{{ asset('assets/css/admin-enterprise.css') }}">
@endpush

@section('content')

<!-- Hero Section -->
<x-admin.page-hero title="Taxas & Margens (Fees)" description="Controle de rentabilidade, divergências de custos e lucro líquido operacional da plataforma.">
    <button class="btn btn-outline-light" onclick="window.location.reload()">
        <i class="fas fa-sync-alt me-1"></i> Atualizar
    </button>
</x-admin.page-hero>

<!-- KPIs -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <x-admin.kpi-card title="Receita (Merchant Fee)" value="{!! MoneyFormatter::formatAbsolute($dashboardData->kpis['total_merchant_fee']) !!}" colorClass="text-success" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Custos (Gateway)" value="{!! MoneyFormatter::formatAbsolute($dashboardData->kpis['total_gateway_cost']) !!}" colorClass="text-danger" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Lucro Líquido (Margin)" value="{!! MoneyFormatter::formatAbsolute($dashboardData->kpis['total_margin']) !!}" colorClass="text-primary" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Divergências de Custo" value="{{ number_format($dashboardData->kpis['count_divergent']) }}" colorClass="{{ $dashboardData->kpis['count_divergent'] > 0 ? 'text-danger' : 'text-success' }}" />
    </div>
</div>

<!-- Smart Filters -->
<x-admin.smart-filter action="{{ route('admin.finance.fees.index') }}">
    <div class="col-md-3">
        <label class="form-label">Status da Taxa</label>
        <select name="status" class="form-select">
            <option value="">Todos</option>
            <option value="{{ FeeStatus::EXPECTED->value }}" {{ ($dashboardData->filters['status'] ?? '') == FeeStatus::EXPECTED->value ? 'selected' : '' }}>Esperada</option>
            <option value="{{ FeeStatus::CONFIRMED->value }}" {{ ($dashboardData->filters['status'] ?? '') == FeeStatus::CONFIRMED->value ? 'selected' : '' }}>Confirmada</option>
            <option value="{{ FeeStatus::DIVERGENT->value }}" {{ ($dashboardData->filters['status'] ?? '') == FeeStatus::DIVERGENT->value ? 'selected' : '' }}>Divergente</option>
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
    $hasData = $dashboardData->fees->count() > 0;
    $headers = ['Data', 'Gateway / Op', 'Volume Bruto', 'Custo (Gateway)', 'Receita (Cobrada)', 'Lucro Líquido', 'Status'];
@endphp
<x-admin.data-table :headers="$headers" :hasData="$hasData" :paginator="$dashboardData->fees" emptyMessage="Nenhum registro de taxa" emptySubmessage="As regras de negócio aplicadas não retornaram resultados.">
    @foreach($dashboardData->fees as $fee)
        <tr class="admin-table-row" data-trx="{{ json_encode([
            'id' => $fee->id,
            'date' => $fee->created_at->format('d/m/Y H:i:s'),
            'user' => $fee->user->username ?? 'Desconhecido',
            'gateway' => $fee->gateway->name ?? 'N/A',
            'operation' => $fee->operation_type ?? 'N/A',
            'ref' => $fee->reference_id ?? 'N/A',
            'gross' => $fee->gross_amount,
            'cost' => $fee->gateway_cost,
            'merchant_fee' => $fee->merchant_fee,
            'margin' => $fee->margin,
            'status' => $fee->status,
            'badge' => TransactionBadgeService::getBadge($fee->status),
            'timeline' => TimelineBuilder::buildForFee($fee)->toArray()
        ]) }}" onclick="handleDrawerOpen(this)">
            <td>
                <span class="fw-bold">{{ $fee->created_at->format('d/m/Y') }}</span><br>
                <small class="text-muted">{{ $fee->created_at->format('H:i') }}</small>
            </td>
            <td>
                <div><span class="badge bg-primary text-uppercase">{{ $fee->gateway->name ?? 'N/A' }}</span></div>
                <small class="text-muted fw-bold">{{ $fee->operation_type }}</small>
            </td>
            <td>
                {!! MoneyFormatter::formatAbsolute($fee->gross_amount) !!}
            </td>
            <td>
                {!! MoneyFormatter::format(-$fee->gateway_cost, null, true) !!}
            </td>
            <td>
                {!! MoneyFormatter::format($fee->merchant_fee, null, true) !!}
            </td>
            <td>
                <span class="fw-bold {{ $fee->margin >= 0 ? 'text-success' : 'text-danger' }}">
                    {!! MoneyFormatter::formatAbsolute($fee->margin) !!}
                </span>
            </td>
            <td>
                {!! TransactionBadgeService::render($fee->status) !!}
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<!-- Offcanvas Drawer -->
<x-admin.drawer id="feeDrawer" title="Demonstrativo DRE (Operação)">
    
    <!-- Quick Context -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background:#f8fafc;">
        <div>
            <div class="text-muted small mb-1">Lucro Líquido Calculado</div>
            <h3 id="drawerAmount" class="mb-0 text-dark fw-bold"></h3>
        </div>
        <div class="text-end">
            <span id="drawerStatus"></span>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Contexto Operacional</h6>
    <table class="table table-sm table-borderless mb-4">
        <tr>
            <td class="text-muted w-50">Ref ID</td>
            <td id="drawerTrxId" class="text-end fw-bold"></td>
        </tr>
        <tr>
            <td class="text-muted">Usuário Alvo</td>
            <td id="drawerUser" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Gateway</td>
            <td id="drawerGateway" class="text-end fw-medium text-uppercase"></td>
        </tr>
        <tr>
            <td class="text-muted">Operação</td>
            <td id="drawerOp" class="text-end fw-medium"></td>
        </tr>
    </table>

    <div class="alert alert-light border shadow-sm mb-4">
        <h6 class="alert-heading fw-bold mb-3 border-bottom pb-2"><i class="fas fa-calculator me-2"></i>DRE (Visão Microscópica)</h6>
        
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Volume Processado</span>
            <span id="dreGross" class="fw-bold"></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Taxa Cobrada do Cliente</span>
            <span id="dreFee" class="fw-bold text-success"></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Custo Real do Provedor</span>
            <span id="dreCost" class="fw-bold text-danger"></span>
        </div>
        <hr>
        <div class="d-flex justify-content-between">
            <strong class="text-dark">Margem de Lucro</strong>
            <strong id="dreMargin"></strong>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Linha do Tempo</h6>
    <div id="drawerTimelineContainer" class="mb-4"></div>

    <!-- Quick Links (Ações) -->
    <div class="mt-4 pt-3 border-top" id="drawerQuickLinks">
        <!-- Ações futuras passariam pelo FeeActionService e chamadas POST -->
        <button class="btn btn-sm btn-outline-secondary w-100 mb-2 disabled"><i class="fas fa-sync me-1"></i> Recalcular Custos (Em Breve)</button>
        <button class="btn btn-sm btn-outline-success w-100 disabled"><i class="fas fa-check me-1"></i> Aceitar Divergência (Em Breve)</button>
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
            drawerTrxId: raw.ref,
            drawerUser: raw.user,
            drawerGateway: raw.gateway,
            drawerOp: raw.operation,
            drawerAmount: {
                text: 'R$ ' + parseFloat(raw.margin).toFixed(2),
                type: null
            },
            dreGross: 'R$ ' + parseFloat(raw.gross).toFixed(2),
            dreFee: '+ R$ ' + parseFloat(raw.merchant_fee).toFixed(2),
            dreCost: '- R$ ' + parseFloat(raw.cost).toFixed(2),
            dreMargin: 'R$ ' + parseFloat(raw.margin).toFixed(2),
            drawerStatus: {
                html: `<span class="${raw.badge.class}"><i class="${raw.badge.icon} me-1"></i> ${raw.badge.label}</span>`
            },
            drawerTimelineContainer: timelineHtml
        };

        if (parseFloat(raw.margin) >= 0) {
            document.getElementById('dreMargin').className = 'text-success fw-bold';
        } else {
            document.getElementById('dreMargin').className = 'text-danger fw-bold';
        }

        openAdminDrawer('feeDrawer', data);
    }
</script>
@endpush
