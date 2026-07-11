@extends('backend.finance.index')
@section('finance_title', 'Conciliações Financeiras')
@section('finance_desc', 'Visão unificada das divergências entre saldos dos Provedores e o Ledger interno.')

@section('finance_action')
    <button class="btn btn-primary" onclick="window.location.reload()">
        <i class="fas fa-sync-alt me-1"></i> Sincronizar
    </button>
@endsection

@push('style')
<link rel="stylesheet" href="{{ asset('assets/css/admin-enterprise.css') }}">
@endpush

@section('finance_content')

<!-- KPIs -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <x-admin.kpi-card title="Saldo Esperado (Provedor)" value="{{ siteCurrency('symbol') }} {{ number_format($dashboardData->kpis['total_expected'], 2) }}" colorClass="text-dark" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Saldo Atual (Ledger)" value="{{ siteCurrency('symbol') }} {{ number_format($dashboardData->kpis['total_actual'], 2) }}" colorClass="text-dark" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Diferença Acumulada" value="{{ siteCurrency('symbol') }} {{ number_format($dashboardData->kpis['total_difference'], 2) }}" colorClass="{{ $dashboardData->kpis['total_difference'] == 0 ? 'text-success' : 'text-danger' }}" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Discrepâncias Críticas" value="{{ number_format($dashboardData->kpis['count_critical']) }}" colorClass="text-danger" />
    </div>
</div>

<!-- Smart Filters -->
<x-admin.smart-filter action="{{ route('admin.finance.reconciliation') }}">
    <div class="col-md-3">
        <label class="form-label">Gateway / Provedor</label>
        <select name="provider" class="form-select">
            <option value="">Todos</option>
            @foreach($dashboardData->activeGateways as $gw)
                <option value="{{ $gw['code'] }}" {{ ($dashboardData->filters['provider'] ?? '') == $gw['code'] ? 'selected' : '' }}>
                    {{ $gw['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Status de Risco</label>
        <select name="status" class="form-select">
            <option value="">Todos</option>
            <option value="GREEN" {{ ($dashboardData->filters['status'] ?? '') == 'GREEN' ? 'selected' : '' }}>Saudável (GREEN)</option>
            <option value="LOW" {{ ($dashboardData->filters['status'] ?? '') == 'LOW' ? 'selected' : '' }}>Risco Baixo (LOW)</option>
            <option value="MEDIUM" {{ ($dashboardData->filters['status'] ?? '') == 'MEDIUM' ? 'selected' : '' }}>Risco Médio (MEDIUM)</option>
            <option value="HIGH" {{ ($dashboardData->filters['status'] ?? '') == 'HIGH' ? 'selected' : '' }}>Risco Alto (HIGH)</option>
            <option value="CRITICAL" {{ ($dashboardData->filters['status'] ?? '') == 'CRITICAL' ? 'selected' : '' }}>Crítico (CRITICAL)</option>
        </select>
    </div>
    <div class="col-md-2">
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
    use App\Services\Finance\Formatting\MoneyFormatter;
    use App\Services\Finance\Formatting\TransactionBadgeService;
    $hasData = $dashboardData->reconciliations->count() > 0;
    $headers = ['Provedor', 'Data Auditoria', 'Saldo Esperado (Prov)', 'Saldo Atual (Int)', 'Diferença', 'Status'];
@endphp
<x-admin.data-table :headers="$headers" :hasData="$hasData" :paginator="$dashboardData->reconciliations" emptyMessage="Nenhuma conciliação encontrada" emptySubmessage="As finanças estão equalizadas ou os filtros não retornaram dados.">
    @foreach($dashboardData->reconciliations as $recon)
        <tr class="admin-table-row" data-trx="{{ json_encode([
            'id' => $recon->id,
            'provider' => $recon->provider,
            'date' => $recon->created_at->format('d/m/Y H:i:s'),
            'expected' => $recon->expected_balance,
            'actual' => $recon->actual_balance,
            'diff' => $recon->difference,
            'status' => $recon->status,
            'badge' => TransactionBadgeService::getBadge($recon->status),
            'metadata' => json_encode($recon->metadata, JSON_PRETTY_PRINT)
        ]) }}" onclick="handleDrawerOpen(this)">
            <td><span class="fw-bold text-uppercase">{{ $recon->provider }}</span></td>
            <td>{{ $recon->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ MoneyFormatter::formatAbsolute($recon->expected_balance) }}</td>
            <td>{{ MoneyFormatter::formatAbsolute($recon->actual_balance) }}</td>
            <td>
                {!! MoneyFormatter::format($recon->difference, null, true) !!}
            </td>
            <td>
                {!! TransactionBadgeService::render($recon->status) !!}
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<!-- Offcanvas Drawer -->
<x-admin.drawer id="reconciliationDrawer" title="Detalhes da Conciliação">
    
    <!-- Quick Context -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background:#f8fafc;">
        <div>
            <div class="text-muted small mb-1">Divergência Detectada</div>
            <h3 id="drawerAmount" class="mb-0"></h3>
        </div>
        <div class="text-end">
            <span id="drawerStatus"></span>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Fotografia do Provedor</h6>
    <table class="table table-sm table-borderless mb-4">
        <tr>
            <td class="text-muted w-50">Gateway</td>
            <td id="drawerGateway" class="text-end fw-bold text-uppercase"></td>
        </tr>
        <tr>
            <td class="text-muted">Momento da Auditoria</td>
            <td id="drawerDate" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Esperado (Gateway)</td>
            <td id="drawerExpected" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Atual (Ledger Interno)</td>
            <td id="drawerActual" class="text-end fw-medium"></td>
        </tr>
    </table>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Metadados da API (Gateway)</h6>
    <!-- Assuming we have a json viewer component, otherwise just a styled pre -->
    <pre id="drawerMetadata" class="bg-light p-3 rounded text-muted small" style="max-height: 250px; overflow-y: auto;"></pre>

    <div class="mt-4 pt-3 border-top" id="drawerQuickLinks">
        <button class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-hammer me-2"></i> Forçar Sincronização de Saldo</button>
    </div>
</x-admin.drawer>

@endsection

@push('script')
<script src="{{ asset('assets/js/admin-drawer.js') }}"></script>
<script>
    function handleDrawerOpen(row) {
        const raw = JSON.parse(row.getAttribute('data-trx'));
        
        const data = {
            drawerGateway: raw.provider,
            drawerDate: raw.date,
            drawerExpected: 'R$ ' + parseFloat(raw.expected).toFixed(2),
            drawerActual: 'R$ ' + parseFloat(raw.actual).toFixed(2),
            drawerAmount: {
                text: 'R$ ' + parseFloat(raw.diff).toFixed(2),
                type: raw.diff == 0 ? '+' : '-'
            },
            drawerStatus: {
                html: `<span class="${raw.badge.class}"><i class="${raw.badge.icon} me-1"></i> ${raw.badge.label}</span>`
            },
            drawerMetadata: raw.metadata || 'Nenhum metadado retornado.'
        };

        openAdminDrawer('reconciliationDrawer', data);
    }
</script>
@endpush
