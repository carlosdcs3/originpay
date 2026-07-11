@extends('backend.operations.index')

@section('operations_title', 'Centro de Operações')
@section('operations_desc', 'Visão operacional dos serviços, filas e alertas da OriginPay.')

@section('operations_content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold" style="color: var(--ds-text);">Centro de Operações</h4>
            <div class="text-muted small">Horário do servidor: {{ now()->format('d/m/Y H:i:s T') }}</div>
        </div>
        <a href="{{ route('admin.alerts.index') }}" class="btn btn-outline-primary btn-sm">Ver alertas</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-ds.dev-stat-card title="Incidentes ativos" :value="$activeIncidents" icon="warning" />
        </div>
        <div class="col-md-3">
            <x-ds.dev-stat-card title="Jobs em fila" :value="$jobsCount" icon="list" />
        </div>
        <div class="col-md-3">
            <x-ds.dev-stat-card title="Falhas de fila" :value="$failedJobsCount" icon="error" />
        </div>
        <div class="col-md-3">
            <x-ds.dev-stat-card title="Volume na última hora" :value="$trxCountHour" icon="currency" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-ds.card class="border-0 shadow-sm h-100">
                <div class="card-header border-0 pt-4 pb-2" style="background-color: transparent !important;">
                    <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.05em; font-size: 0.75rem;">Gateways monitorados</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Gateway</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                    <th>Telemetria</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gatewaysHealth as $gw)
                                    <tr>
                                        <td class="fw-semibold">{{ $gw->name }}</td>
                                        <td>
                                            @if($gw->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Ativo</span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Inativo</span>
                                            @endif
                                        </td>
                                        <td class="font-monospace text-muted">
                                            {{ $gw->health_score !== null ? $gw->health_score : 'Não calculado' }}
                                        </td>
                                        <td class="text-muted">
                                            @if($gw->latency || $gw->success_rate)
                                                Latência: {{ $gw->latency ?? '—' }} | Sucesso: {{ $gw->success_rate ?? '—' }}
                                            @else
                                                Monitoramento não configurado
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Nenhum gateway configurado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-ds.card>
        </div>

        <div class="col-lg-4">
            <x-ds.card class="border-0 shadow-sm h-100">
                <div class="card-header border-0 pt-4 pb-2" style="background-color: transparent !important;">
                    <h6 class="text-uppercase text-muted fw-bold mb-0" style="letter-spacing: 0.05em; font-size: 0.75rem;">Últimos alertas</h6>
                </div>
                <div class="card-body pt-0">
                    @forelse($recentAlerts as $alert)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="fw-semibold">{{ $alert->title ?? $alert->message ?? 'Alerta operacional' }}</div>
                            <div class="text-muted small">{{ optional($alert->created_at)->diffForHumans() }}</div>
                        </div>
                    @empty
                        <x-ds.empty-state title="Nenhum alerta recente" desc="Quando um evento crítico ocorrer, ele aparecerá aqui." />
                    @endforelse
                </div>
            </x-ds.card>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <x-ds.card class="border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing: 0.05em; font-size: 0.75rem;">Liquidação e conciliação</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Falhas de reconciliação</span>
                        <span class="fw-semibold">{{ $failedReconciliations ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-muted">Eventos não processados</span>
                        <span class="fw-semibold">{{ $dlqCount }}</span>
                    </div>
                </div>
            </x-ds.card>
        </div>

        <div class="col-lg-6">
            <x-ds.card class="border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing: 0.05em; font-size: 0.75rem;">Saúde geral</h6>
                    <div class="fw-semibold mb-2">
                        @if($platformStatus === 'critical')
                            Incidente crítico em andamento
                        @elseif($platformStatus === 'degraded')
                            Serviços operando com degradação
                        @else
                            Todos os serviços principais estão estáveis
                        @endif
                    </div>
                    <div class="text-muted small">O painel exibe somente sinais reais detectados no ambiente atual.</div>
                </div>
            </x-ds.card>
        </div>
    </div>
@endsection
