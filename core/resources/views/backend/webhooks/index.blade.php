@extends('backend.layouts.app')
@section('title', 'Webhooks')

@push('style')
<style>
    :root {
        --ds-primary-rgb: 99, 102, 241;
        --ds-text-sm: 0.875rem;
        --ds-text-xs: 0.75rem;
        --ds-font-mono: 'JetBrains Mono', 'Fira Code', monospace;
    }
    .premium-empty-state {
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        border: 1px dashed #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h3 class="fw-bold mb-1">Webhooks</h3>
        <p class="text-muted mb-0">Gerencie endpoints para receber notifica√ß√µes em tempo real sobre eventos da sua conta.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <span class="text-muted small">O cadastro de novos endpoints n„o est· disponÌvel neste painel.</span>
    </div>
</div>

<!-- Webhook KPIs -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <x-ds.dev-stat-card title="Endpoints Ativos" value="0" icon="satellite-dish" />
    </div>
    <div class="col-md-3">
        <x-ds.dev-stat-card title="Entregas (Hoje)" value="0" icon="bolt" />
    </div>
    <div class="col-md-3">
        <x-ds.dev-stat-card title="Taxa de Sucesso" value="‚Äî" icon="check-circle" />
    </div>
    <div class="col-md-3">
        <x-ds.dev-stat-card title="Falhas de Entrega" value="0" icon="exclamation-circle" />
    </div>
</div>

<!-- Endpoints -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="fw-bold mb-3">Endpoints Configurados</h5>
        <div class="card border-0 shadow-sm rounded-3 premium-empty-state" style="min-height: 220px;">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center align-items-center">
                <div class="rounded-circle bg-light d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 56px; height: 56px;">
                    <i class="la la-code-branch fs-2 text-muted"></i>
                </div>
                <h5 class="fw-bold mb-2">Nenhum endpoint configurado</h5>
                <p class="text-muted max-w-sm mb-0">Os endpoints cadastrados aparecer„o aqui quando o provisionamento de webhooks estiver habilitado no ambiente atual.</p>
            </div>
        </div>
    </div>
</div>

<!-- Logs / Deliveries -->
<div class="row">
    <div class="col-12">
        <h5 class="fw-bold mb-3">Entregas Recentes</h5>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <ul class="nav nav-pills nav-sm">
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab', 'received') == 'received' ? 'active' : '' }}" href="{{ route('admin.webhooks.index', ['tab' => 'received']) }}">Recebidos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') == 'processed' ? 'active' : '' }}" href="{{ route('admin.webhooks.index', ['tab' => 'processed']) }}">Processados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') == 'failed' ? 'active bg-danger' : '' }}" href="{{ route('admin.webhooks.index', ['tab' => 'failed']) }}">Fila de Retentativas (Falhas)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') == 'dlq' ? 'active bg-dark' : '' }}" href="{{ route('admin.webhooks.index', ['tab' => 'dlq']) }}">Fila morta (DLQ)</a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs); padding: 1rem;">ID / Provider</th>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs); padding: 1rem;">Evento</th>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs); padding: 1rem;">Status</th>
                                <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs); padding: 1rem;">Hor√°rio</th>
                                <th class="text-muted fw-semibold text-uppercase text-end" style="font-size: var(--ds-text-xs); padding: 1rem;">A√ß√µes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold" style="font-size: var(--ds-text-sm);">#{{ $item->id }}</div>
                                    <div class="text-muted" style="font-size: var(--ds-text-xs);">{{ $item->provider }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $item->event_id ?? $item->original_dlq_id }}</span></td>
                                <td>
                                    @if(request('tab') == 'dlq')
                                        <span class="badge bg-dark">DLQ</span>
                                    @else
                                        <span class="badge bg-{{ $item->status == 'PROCESSED' || $item->status == 'manually_resolved' ? 'success' : ($item->status == 'FAILED' ? 'danger' : 'info') }}">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td class="text-muted" style="font-size: var(--ds-text-sm);">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end pe-3">
                                    @if(request('tab') == 'dlq')
                                        <a href="{{ route('admin.webhooks.showDlq', $item->id) }}" class="btn btn-sm btn-light border">Detalhes</a>
                                    @else
                                        <a href="{{ route('admin.webhooks.showEvent', $item->id) }}" class="btn btn-sm btn-light border">Detalhes</a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                                        <i class="la la-stream fs-1 text-light mb-2"></i>
                                        <span class="text-muted fw-semibold">Nenhuma entrega registrada nesta aba.</span>
                                        <span class="text-muted small mt-1">Quando houver atividade, os webhooks processados ou falhos aparecer√£o aqui.</span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($items->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $items->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection


