@extends('backend.layouts.app')
@section('title', 'Conciliações Financeiras')

@section('content')
@php
    $providerOptions = collect($entries ?? [])
        ->pluck('provider')
        ->filter()
        ->unique()
        ->values();
@endphp

<x-admin.page-hero
    title="Dashboard de Conciliações"
    description="Visão unificada das auditorias entre gateways de pagamento e o ledger interno. Monitore divergências e garanta a integridade financeira."
    status="Serviço Ativo"
    statusColor="success"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Conciliações' => null
    ]"
>
    <button class="btn btn-dark fw-semibold px-3 shadow-sm rounded-3" onclick="forceReconciliation()">
        <i class="fa-solid fa-rotate me-2"></i> Forçar Conciliação
    </button>
    <button class="btn btn-outline-secondary fw-semibold px-3 shadow-sm rounded-3 bg-body" onclick="exportDivergences()">
        <i class="fa-solid fa-file-export me-2"></i> Exportar Falhas
    </button>
</x-admin.page-hero>

<x-admin.alerts-area :alerts="$alerts ?? []" />

<x-admin.kpi-grid>
    <x-admin.kpi-card
        title="Total Conciliado"
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['total_conciliado'] ?? 0, 2, ',', '.') }}"
        icon="fa-solid fa-check-double"
        color="success"
        subtitle="Registros válidos"
    />
    <x-admin.kpi-card
        title="Pendentes"
        value="{{ $kpis['pendente'] ?? 0 }}"
        icon="fa-solid fa-hourglass-half"
        color="secondary"
        subtitle="Aguardando fechamento"
    />
    <x-admin.kpi-card
        title="Divergências"
        value="{{ $kpis['divergencias'] ?? 0 }}"
        icon="fa-solid fa-code-compare"
        color="warning"
        subtitle="Diferenças de saldo/hash"
    />
    <x-admin.kpi-card
        title="Falhas por gateway"
        value="{{ $kpis['falhas'] ?? 0 }}"
        icon="fa-solid fa-server"
        color="danger"
        subtitle="Erros de conexão"
    />
    <x-admin.kpi-card
        title="Última Sincronização"
        value="{{ $kpis['ultima'] ?? 'N/A' }}"
        icon="fa-solid fa-clock-rotate-left"
        color="info"
    />
    <x-admin.kpi-card
        title="SLA Médio"
        value="{{ $kpis['sla_medio'] ?? 'N/A' }}"
        icon="fa-solid fa-stopwatch"
        color="primary"
        subtitle="Tempo de conciliação"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.reconciliation') }}" :activeFilters="['status' => 'Status', 'provider' => 'Gateway']">
    <select name="status" class="form-select border border-secondary-subtle shadow-none rounded-3 bg-body-tertiary text-body-emphasis" style="min-width: 150px; font-size: 0.95rem;">
        <option value="">Status: Todos</option>
        <option value="reconciled" {{ request('status') == 'reconciled' ? 'selected' : '' }}>Conciliado</option>
        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendente</option>
        <option value="divergent" {{ request('status') == 'divergent' ? 'selected' : '' }}>Divergente</option>
        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Falha na API</option>
    </select>
    <select name="provider" class="form-select border border-secondary-subtle shadow-none rounded-3 bg-body-tertiary text-body-emphasis" style="min-width: 150px; font-size: 0.95rem;">
        <option value="">Gateway: Todos</option>
        @foreach($providerOptions as $provider)
            <option value="{{ $provider }}" {{ request('provider') == $provider ? 'selected' : '' }}>
                {{ ucfirst(str_replace(['_', '-'], ' ', $provider)) }}
            </option>
        @endforeach
    </select>
</x-admin.smart-filter>

<x-admin.data-table
    :headers="['ID', 'Gateway', 'Saldo Esperado', 'Saldo Recebido', 'Diferença', 'Status', 'Data', '']"
    :paginator="$entries"
    emptyStateTitle="Nenhuma conciliação encontrada"
    emptyStateDesc="Não existem registros de conciliação com os filtros atuais.">

    @foreach($entries as $entry)
        <tr style="cursor: pointer;" onclick="openReconDrawer({{ $entry->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold font-monospace text-body-emphasis" style="font-size: 0.85rem;">#{{ $entry->id }}</div>
            </td>
            <td>
                <span class="badge bg-body-tertiary text-body-emphasis border border-secondary-subtle px-2 py-1 rounded-pill text-uppercase">{{ $entry->provider }}</span>
            </td>
            <td><div class="fw-semibold text-body-emphasis">{{ siteCurrency('symbol') }}{{ number_format($entry->expected_balance, 2, ',', '.') }}</div></td>
            <td><div class="fw-semibold text-body-emphasis">{{ siteCurrency('symbol') }}{{ number_format($entry->actual_balance, 2, ',', '.') }}</div></td>
            <td>
                @php
                    $diff = $entry->difference;
                    $diffClass = $diff == 0 ? 'text-success' : 'text-danger';
                @endphp
                <div class="fw-bold {{ $diffClass }}">
                    {{ siteCurrency('symbol') }}{{ number_format($diff, 2, ',', '.') }}
                </div>
            </td>
            <td>
                @php
                    $statusColors = [
                        'reconciled' => 'success',
                        'pending' => 'secondary',
                        'divergent' => 'warning',
                        'failed' => 'danger'
                    ];
                    $statusColor = $statusColors[$entry->status] ?? 'primary';
                @endphp
                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $entry->status }}</span>
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $entry->created_at->format('d/m/Y') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $entry->created_at->format('H:i:s') }}</div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openReconDrawer({{ $entry->id }})">
                    <i class="fa-solid fa-chevron-right text-body-secondary"></i>
                </button>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<style>
    .table-row-hover:hover td { background-color: var(--cui-tertiary-bg); }
</style>

<x-admin.drawer
    id="reconDetailDrawer"
    title="Inspeção de Conciliação"
    position="end"
    size="xl"
    :tabs="['Resumo Geral', 'Auditoria (Timeline)', 'Metadata (JSON)']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Carregando dados da conciliação...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>

    <x-slot name="footerActions">
        <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeReconDrawer()">Cancelar</button>
        <button class="btn btn-warning fw-semibold shadow-sm text-dark"><i class="fa-solid fa-rotate-right me-2"></i> Reprocessar</button>
        <button class="btn btn-success fw-semibold shadow-sm text-white"><i class="fa-solid fa-check-double me-2"></i> Marcar Revisado</button>
    </x-slot>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const reconciliations = @json($entries->keyBy('id'));

    function openReconDrawer(id) {
        const drawerEl = document.getElementById('reconDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();

        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');

        const tx = reconciliations[id];

        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');

            const metadataJson = tx.metadata ? JSON.stringify(tx.metadata, null, 2) : '{}';
            const providerLabel = String(tx.provider || 'gateway').replace(/[_-]/g, ' ');
            const differenceClass = parseFloat(tx.difference) !== 0 ? 'warning' : 'success';

            document.getElementById('drawerContent').innerHTML = `
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="reconDetailDrawer-tab-resumo-geral" role="tabpanel">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card border border-secondary-subtle bg-body-tertiary shadow-sm h-100">
                                <div class="card-body text-center p-4">
                                    <div class="text-body-secondary text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Saldo Esperado (Ledger)</div>
                                    <h3 class="fw-bold text-body-emphasis mb-0">R$ ${tx.expected_balance}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-secondary-subtle bg-body-tertiary shadow-sm h-100">
                                <div class="card-body text-center p-4">
                                    <div class="text-body-secondary text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Saldo Recebido (Gateway)</div>
                                    <h3 class="fw-bold text-body-emphasis mb-0">R$ ${tx.actual_balance}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-secondary-subtle bg-${differenceClass}-subtle shadow-sm h-100">
                                <div class="card-body text-center p-4">
                                    <div class="text-${differenceClass} text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Diferença Encontrada</div>
                                    <h3 class="fw-bold text-${differenceClass} mb-0">R$ ${tx.difference}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-body-secondary fw-semibold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Entidades Relacionadas</h6>
                    <div class="list-group list-group-flush border border-secondary-subtle rounded-4 overflow-hidden shadow-sm">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 bg-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-plug fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">Gateway de Pagamento</div>
                                    <div class="text-body-secondary text-uppercase" style="font-size: 0.75rem;">${providerLabel}</div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('admin.finance.ledger') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 bg-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-book-journal-whills text-success"></i>
                                <div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">Inspecionar no Ledger</div>
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">Acessar registros originais</div>
                                </div>
                            </div>
                            <i class="fa-solid fa-arrow-up-right-from-square text-body-secondary" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                </div>

                <div class="tab-pane fade p-4 h-100 overflow-auto" id="reconDetailDrawer-tab-auditoria-timeline" role="tabpanel">
                    <div class="admin-timeline p-0 bg-transparent">
                        <style>
                            .admin-timeline-item { position: relative; padding-left: 2.2rem; padding-bottom: 1.5rem; }
                            .admin-timeline-item::before { content: ''; position: absolute; left: 0.45rem; top: 1.5rem; bottom: 0; width: 2px; background-color: var(--cui-secondary-bg-subtle, #e2e8f0); }
                            .admin-timeline-item:last-child::before { display: none; }
                            .admin-timeline-marker { position: absolute; left: 0; top: 0.2rem; width: 1rem; height: 1rem; border-radius: 50%; border: 2px solid var(--cui-body-bg, #fff); box-shadow: 0 0 0 2px var(--cui-secondary-bg-subtle, #e2e8f0); z-index: 1; }
                            .admin-timeline-marker.success { background-color: var(--cui-success, #198754); box-shadow: 0 0 0 2px var(--cui-success-subtle, #d1e7dd); }
                            .admin-timeline-marker.warning { background-color: var(--cui-warning, #ffc107); box-shadow: 0 0 0 2px var(--cui-warning-subtle, #fff3cd); }
                            .admin-timeline-marker.active { background-color: var(--cui-primary, #0d6efd); box-shadow: 0 0 0 2px var(--cui-primary-subtle, #cfe2ff); }
                        </style>
                        <div class="admin-timeline-item">
                            <div class="admin-timeline-marker active"></div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-semibold text-body-emphasis" style="font-size: 0.95rem;">Conciliação Iniciada</h6>
                            </div>
                            <div class="text-body-secondary" style="font-size: 0.85rem;">Job enfileirado no worker.</div>
                        </div>
                        <div class="admin-timeline-item">
                            <div class="admin-timeline-marker ${differenceClass}"></div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-semibold text-body-emphasis" style="font-size: 0.95rem;">Resultado Retornado (${tx.status})</h6>
                                <span class="text-body-secondary" style="font-size: 0.8rem;">${new Date(tx.created_at).toLocaleString()}</span>
                            </div>
                            <div class="text-body-secondary" style="font-size: 0.85rem;">Gateway retornou os saldos para comparação.</div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade p-4 h-100 overflow-auto" id="reconDetailDrawer-tab-metadata-json" role="tabpanel">
                    <div class="json-viewer-wrapper position-relative">
                        <pre class="bg-body-tertiary border border-secondary-subtle rounded-3 p-3 m-0 overflow-auto shadow-sm" style="max-height: 400px; scrollbar-width: thin;"><code class="text-body-emphasis" style="font-family: var(--cui-font-monospace); font-size: 0.85rem;">${metadataJson}</code></pre>
                    </div>
                </div>
            `;
        }, 300);
    }

    function closeReconDrawer() {
        const drawerEl = document.getElementById('reconDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
</script>
@endpush
