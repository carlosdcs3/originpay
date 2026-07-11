@extends('backend.layouts.app')
@section('title', $pageTitle)

@push('style')
<link rel="stylesheet" href="{{ asset('assets/css/admin-enterprise.css') }}">
@endpush

@section('content')

<!-- Hero Section -->
<x-admin.page-hero title="Transações (Painel Operacional)" description="Visão gerencial de alto nível das transações, integrações e taxas de sucesso da plataforma.">
    <button class="btn btn-primary" onclick="window.location.reload()">
        <i class="fas fa-sync-alt me-1"></i> Atualizar
    </button>
</x-admin.page-hero>

<!-- KPIs -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <x-admin.kpi-card title="Volume Operacionado" value="{{ site_currency() }} {{ number_format($dashboardData->kpis['total_volume'], 2) }}" colorClass="text-dark" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Taxa de Sucesso" value="{{ $dashboardData->kpis['success_rate'] }}%" colorClass="{{ $dashboardData->kpis['success_rate'] >= 95 ? 'text-success' : 'text-warning' }}" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Falhas" value="{{ number_format($dashboardData->kpis['count_failed']) }}" colorClass="text-danger" />
    </div>
    <div class="col-md-3">
        <x-admin.kpi-card title="Chargebacks Envolvidos" value="{{ number_format($dashboardData->kpis['count_chargeback']) }}" colorClass="{{ $dashboardData->kpis['count_chargeback'] > 0 ? 'text-danger' : 'text-success' }}" />
    </div>
</div>

<!-- Smart Filters -->
<x-admin.smart-filter action="{{ route('admin.finance.transactions.index') }}">
    <div class="col-md-2">
        <label class="form-label">Status Operacional</label>
        <select name="status" class="form-select">
            <option value="">Todos</option>
            <option value="completed" {{ ($dashboardData->filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>Concluído</option>
            <option value="pending" {{ ($dashboardData->filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>Pendente</option>
            <option value="failed" {{ ($dashboardData->filters['status'] ?? '') == 'failed' ? 'selected' : '' }}>Falhou</option>
            <option value="chargeback" {{ ($dashboardData->filters['status'] ?? '') == 'chargeback' ? 'selected' : '' }}>Chargeback</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Tipo (Crédito/Débito)</label>
        <select name="trx_type" class="form-select">
            <option value="">Todos</option>
            <option value="+" {{ ($dashboardData->filters['trx_type'] ?? '') == '+' ? 'selected' : '' }}>Crédito (+)</option>
            <option value="-" {{ ($dashboardData->filters['trx_type'] ?? '') == '-' ? 'selected' : '' }}>Débito (-)</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Operação</label>
        <input type="text" name="operation" class="form-control" placeholder="Ex: PIX_CHARGE" value="{{ $dashboardData->filters['operation'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Data Inicial</label>
        <input type="date" name="start_date" class="form-control" value="{{ $dashboardData->filters['start_date'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Data Final</label>
        <input type="date" name="end_date" class="form-control" value="{{ $dashboardData->filters['end_date'] ?? '' }}">
    </div>
</x-admin.smart-filter>

<!-- DataTable -->
@php
    use App\Services\Finance\Formatting\MoneyFormatter;
    use App\Services\Finance\Formatting\TransactionBadgeService;
    $hasData = $dashboardData->transactions->count() > 0;
    $headers = ['TRX ID', 'Usuário / Conta', 'Gateway / Origem', 'Operação', 'Volume (Abs)', 'Status'];
@endphp
<x-admin.data-table :headers="$headers" :hasData="$hasData" :paginator="$dashboardData->transactions" emptyMessage="Nenhuma transação operacional" emptySubmessage="As regras de negócio aplicadas não retornaram resultados.">
    @foreach($dashboardData->transactions as $trx)
        <tr class="admin-table-row" data-trx="{{ json_encode([
            'id' => $trx->trx_id,
            'date' => $trx->created_at->format('d/m/Y H:i:s'),
            'user' => $trx->user->username ?? 'Desconhecido',
            'wallet_id' => $trx->wallet_id,
            'gateway' => $trx->gateway->name ?? 'N/A',
            'operation' => $trx->operation ?? 'N/A',
            'amount' => $trx->amount,
            'type' => $trx->trx_type,
            'status' => $trx->status,
            'badge' => TransactionBadgeService::getBadge($trx->status),
            'charge_trx' => $trx->charge->trx ?? null,
            'charge_id' => $trx->charge_id,
            'withdraw_trx' => $trx->withdraw->trx ?? null,
            'withdraw_id' => $trx->withdraw_id
        ]) }}" onclick="handleDrawerOpen(this)">
            <td>
                <span class="fw-bold">{{ $trx->trx_id }}</span>
                <br>
                <small class="text-muted">{{ $trx->created_at->format('d/m/Y H:i') }}</small>
            </td>
            <td>
                <div class="fw-medium">{{ $trx->user->username ?? 'N/A' }}</div>
                <small class="text-muted">Wallet: {{ $trx->wallet_id }}</small>
            </td>
            <td>
                <div><span class="badge bg-primary">{{ $trx->gateway->name ?? 'Interno' }}</span></div>
            </td>
            <td>
                <span class="fw-medium text-dark">{{ $trx->operation ?? 'N/A' }}</span>
                @if($trx->charge_id)
                    <br><small class="text-muted"><i class="fas fa-link"></i> Depósito</small>
                @endif
                @if($trx->withdraw_id)
                    <br><small class="text-muted"><i class="fas fa-link"></i> Saque</small>
                @endif
            </td>
            <td>
                {!! MoneyFormatter::format($trx->trx_type == '+' ? $trx->amount : -$trx->amount, null, true) !!}
            </td>
            <td>
                {!! TransactionBadgeService::render($trx->status) !!}
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<!-- Offcanvas Drawer -->
<x-admin.drawer id="transactionDrawer" title="Visão de Negócio da Transação">
    
    <!-- Quick Context -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background:#f8fafc;">
        <div>
            <div class="text-muted small mb-1">Volume Processado</div>
            <h3 id="drawerAmount" class="mb-0"></h3>
        </div>
        <div class="text-end">
            <span id="drawerStatus"></span>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Entidades Operacionais</h6>
    <table class="table table-sm table-borderless mb-4">
        <tr>
            <td class="text-muted w-50">Transação Origem (TRX)</td>
            <td id="drawerTrxId" class="text-end fw-bold"></td>
        </tr>
        <tr>
            <td class="text-muted">Usuário Alvo</td>
            <td id="drawerUser" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Operação Executada</td>
            <td id="drawerOp" class="text-end fw-medium"></td>
        </tr>
        <tr>
            <td class="text-muted">Roteamento (Gateway)</td>
            <td id="drawerGateway" class="text-end fw-medium text-uppercase"></td>
        </tr>
    </table>

    <!-- Quick Links (Relacionamentos) -->
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
            links += `<div class="alert alert-info border-0 p-3 mb-2">
                        <strong>Cobrança Vinculada:</strong> ${raw.charge_trx}
                        <br><a href="/admin/deposit/details/${raw.charge_id}" class="btn btn-sm btn-primary mt-2">Acessar Cobrança (Deposit)</a>
                      </div>`;
        }
        if (raw.withdraw_id) {
            links += `<div class="alert alert-warning border-0 p-3 mb-2">
                        <strong>Saque Vinculado:</strong> ${raw.withdraw_trx}
                        <br><a href="/admin/withdraw/details/${raw.withdraw_id}" class="btn btn-sm btn-primary mt-2">Acessar Saque</a>
                      </div>`;
        }
        
        if (links === '') {
            links = `<div class="text-muted small text-center py-2">Sem vínculos com Depósitos ou Saques (Movimentação Interna).</div>`;
        }

        const data = {
            drawerTrxId: raw.id,
            drawerUser: raw.user,
            drawerOp: raw.operation,
            drawerGateway: raw.gateway,
            drawerAmount: {
                text: raw.type + ' R$ ' + parseFloat(raw.amount).toFixed(2),
                type: raw.type
            },
            drawerStatus: {
                html: `<span class="${raw.badge.class}"><i class="${raw.badge.icon} me-1"></i> ${raw.badge.label}</span>`
            },
            drawerQuickLinks: links
        };

        openAdminDrawer('transactionDrawer', data);
    }
</script>
@endpush
