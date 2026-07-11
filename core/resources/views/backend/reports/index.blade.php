@extends('backend.layouts.app')
@section('title', 'Central de Relatórios')

@section('content')
<x-ds.page
    title="Central de Relatórios"
    desc="Acesse os módulos com dados exportáveis e acompanhe os conjuntos disponíveis no ambiente atual."
    :breadcrumb="[
        ['title' => 'Relatórios']
    ]">

    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-semibold">Período</label>
                    <div class="form-control bg-transparent">Definido no módulo de origem</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-semibold">Gateway</label>
                    <div class="form-control bg-transparent">Filtrar na tela operacional correspondente</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-semibold">Usuário</label>
                    <div class="form-control bg-transparent">Disponível em cobranças, saques e transações</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-semibold">Exportação</label>
                    <div class="form-control bg-transparent">Somente quando o backend do relatório estiver disponível</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @php
            $reports = [
                ['name' => 'Cobranças', 'desc' => 'Lista operacional de cobranças com filtros por status, período e usuário.', 'route' => route('admin.gateway.charges.index')],
                ['name' => 'Saques', 'desc' => 'Fila unificada de saques com análise operacional e acompanhamento por gateway.', 'route' => route('admin.gateway.withdrawals.index')],
                ['name' => 'Transações', 'desc' => 'Ledger e transações consolidadas da plataforma.', 'route' => route('admin.transaction')],
                ['name' => 'Tarifas', 'desc' => 'Estrutura de tarifas e regras financeiras vigentes.', 'route' => route('admin.finance.tariffs')],
                ['name' => 'Chargebacks', 'desc' => 'Disputas, reversões e ocorrências financeiras vinculadas a cobranças.', 'route' => route('admin.finance.chargebacks')],
                ['name' => 'Conciliações', 'desc' => 'Diferenças, batimentos e status dos fechamentos financeiros.', 'route' => route('admin.finance.reconciliation')],
                ['name' => 'Clientes', 'desc' => 'Base de clientes com situação cadastral e KYC.', 'route' => route('admin.user.index')],
                ['name' => 'Lojistas', 'desc' => 'Fila e carteira de merchants monitorados pela operação.', 'route' => route('admin.merchant.index')],
                ['name' => 'Webhooks', 'desc' => 'Eventos processados e filas de reprocessamento.', 'route' => route('admin.webhooks.index')],
            ];
        @endphp

        @foreach($reports as $report)
            <div class="col-md-6 col-xl-4">
                <a href="{{ $report['route'] }}" class="card border-0 shadow-sm rounded-3 text-decoration-none h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2 text-dark">{{ $report['name'] }}</h6>
                        <p class="text-muted small mb-0">{{ $report['desc'] }}</p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-3 mt-4">
        <div class="card-body py-4 text-center">
            <h5 class="fw-bold mb-2">Exportações centralizadas</h5>
            <p class="text-muted mb-0">Os módulos abaixo exibem dados reais imediatamente. Exportações em CSV, Excel ou PDF só aparecem quando a rotina correspondente existir no backend.</p>
        </div>
    </div>
</x-ds.page>
@endsection
