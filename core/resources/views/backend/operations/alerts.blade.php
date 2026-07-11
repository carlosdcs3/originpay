@extends('backend.operations.index')
@section('operations_title', 'Alertas')
@section('operations_desc', 'Visualize alertas críticos da plataforma e acompanhe eventos que exigem atenção operacional.')

@section('operations_content')
<div class="row mb-4">
    <div class="col-12">
        <x-ds.card class="border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: var(--ds-surface) !important; --bs-card-bg: var(--ds-surface); border-color: rgba(255,255,255,0.06);">
            <div class="card-body p-0" style="background-color: transparent !important;">
                <div class="table-responsive" style="background-color: transparent !important;">
                    <table class="table table-hover align-middle mb-0" style="background-color: transparent !important; color: var(--ds-text); --bs-table-bg: transparent; --bs-table-color: var(--ds-text); --bs-table-hover-bg: rgba(255,255,255,0.04);">
                        <thead class="border-bottom" style="background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                            <tr>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Nível</th>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Mensagem</th>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Módulo</th>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Data/Hora</th>
                                <th class="text-muted fw-semibold text-uppercase text-end" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Status</th>
                            </tr>
                        </thead>
                        <tbody style="background-color: transparent !important;">
                            @forelse($alerts as $alert)
                                <tr style="background-color: transparent !important;">
                                    <td class="px-4 py-3">
                                        <span class="badge {{ ($alert->severity ?? 'medium') === 'high' ? 'bg-danger' : 'bg-warning' }} rounded-pill px-3">
                                            {{ strtoupper($alert->severity ?? 'médio') }}
                                        </span>
                                    </td>
                                    <td class="py-3 fw-medium">{{ $alert->message ?? $alert->title ?? 'Alerta operacional' }}</td>
                                    <td class="py-3 text-muted">{{ $alert->module ?? 'Sistema' }}</td>
                                    <td class="py-3 text-muted small">{{ optional($alert->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <span class="badge bg-light text-dark border">{{ strtoupper($alert->status ?? 'ativo') }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr style="background-color: transparent !important;">
                                    <td colspan="5" class="text-center p-0">
                                        <div class="py-5 px-3" style="min-height: 240px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <div class="rounded-circle bg-light d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 64px; height: 64px;">
                                                <i class="la la-check-circle fs-2 text-success"></i>
                                            </div>
                                            <h5 class="fw-bold mb-2">Nenhum alerta ativo</h5>
                                            <p class="text-muted max-w-sm mb-3">Nenhum alerta ativo. Todos os serviços estão operando normalmente. Quando um evento crítico ocorrer, ele aparecerá aqui.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ds.card>
    </div>
</div>
@endsection
