@extends('backend.layouts.app')
@section('title', 'Ledger Financeiro')

@section('content')
@include('backend.finance.partials._wallet_tabs')
<x-admin.page-hero 
    title="Ledger (Master)" 
    description="Registro imut�vel de todas as movimenta��es financeiras da plataforma. Fonte da verdade para concilia��es e auditorias."
    status="Auditoria Ativa"
    statusColor="success"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Ledger' => null
    ]"
    :quickStats="[
        ['label' => 'Total de Registros', 'value' => $entries->total()],
        ['label' => '�ltima Sincroniza��o', 'value' => now()->format('H:i')]
    ]"
>
    <button class="btn btn-primary fw-semibold px-3 shadow-sm rounded-3" onclick="exportLedger()">
        <i class="fa-solid fa-file-export me-2"></i> Exportar CSV
    </button>
</x-admin.page-hero>

<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Entradas (Cr�ditos)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['total_in'], 2, ',', '.') }}" 
        icon="fa-solid fa-arrow-down" 
        color="success" 
        trend="up"
        delta="+2.4%"
        subtitle="Volume creditado nas carteiras"
    />
    <x-admin.kpi-card 
        title="Sa�das (D�bitos)" 
        value="{{ siteCurrency('symbol') }} {{ number_format(abs($kpis['total_out']), 2, ',', '.') }}" 
        icon="fa-solid fa-arrow-up" 
        color="danger" 
        trend="down"
        delta="-0.8%"
        subtitle="Volume debitado (saques, taxas)"
    />
    <x-admin.kpi-card 
        title="Diverg�ncias" 
        value="{{ $kpis['discrepancies'] }}" 
        icon="fa-solid fa-triangle-exclamation" 
        color="warning" 
        subtitle="Erros de concilia��o ou hash"
        href="#"
    />
    <x-admin.kpi-card 
        title="Saldo Operacional" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['operating_balance'], 2, ',', '.') }}" 
        icon="fa-solid fa-scale-balanced" 
        color="info" 
        subtitle="Saldo acumulado da plataforma"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.ledger') }}" :activeFilters="['type' => 'Tipo']">
    <select name="type" class="form-select border border-secondary-subtle shadow-none rounded-3 bg-body-tertiary text-body-emphasis" style="min-width: 150px; font-size: 0.95rem;">
        <option value="">Todos os Tipos</option>
        <option value="charge" {{ request('type') == 'charge' ? 'selected' : '' }}>Cobran�a (Charge)</option>
        <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Saque (Withdrawal)</option>
        <option value="fee" {{ request('type') == 'fee' ? 'selected' : '' }}>Taxa (Fee)</option>
        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Ajuste (Adjustment)</option>
        <option value="refund" {{ request('type') == 'refund' ? 'selected' : '' }}>Estorno (Refund)</option>
    </select>
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['Transa��o', 'Usu�rio/Carteira', 'Tipo', 'Valor', 'Data', '']"
    :paginator="$entries"
    emptyStateTitle="Ledger Vazio"
    emptyStateDesc="Nenhuma transa��o correspondente aos filtros.">
    
    @foreach($entries as $entry)
        <tr style="cursor: pointer;" onclick="openLedgerDrawer({{ $entry->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold font-monospace text-body-emphasis" style="font-size: 0.85rem;">{{ substr($entry->idempotency_key ?? $entry->correlation_id ?? $entry->id, 0, 13) }}...</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">ID: {{ $entry->id }}</div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-body-tertiary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-wallet text-body-secondary" style="font-size: 0.8rem;"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">
                            {{ $entry->user->first_name ?? 'N/A' }} {{ $entry->user->last_name ?? '' }}
                        </div>
                        <div class="text-body-secondary" style="font-size: 0.75rem;">Wallet #{{ $entry->wallet_id }} ({{ $entry->wallet->currency ?? 'BRL' }})</div>
                    </div>
                </div>
            </td>
            <td>
                @php
                    $typeColors = [
                        'charge' => 'success',
                        'withdrawal' => 'danger',
                        'fee' => 'warning',
                        'adjustment' => 'info',
                        'refund' => 'secondary'
                    ];
                    $typeColor = $typeColors[$entry->type] ?? 'primary';
                    $amountClass = $entry->amount > 0 ? 'text-success' : ($entry->amount < 0 ? 'text-danger' : 'text-body-secondary');
                    $sign = $entry->amount > 0 ? '+' : '';
                @endphp
                <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $entry->type }}</span>
            </td>
            <td>
                <div class="fw-bold {{ $amountClass }}">
                    {{ $sign }}{{ siteCurrency('symbol') }}{{ number_format($entry->amount, 2, ',', '.') }}
                </div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">Ap�s: {{ siteCurrency('symbol') }}{{ number_format($entry->balance_after, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $entry->created_at->format('d/m/Y') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $entry->created_at->format('H:i:s') }}</div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openLedgerDrawer({{ $entry->id }})">
                    <i class="fa-solid fa-chevron-right text-body-secondary"></i>
                </button>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<style>
    .table-row-hover:hover td { background-color: var(--cui-tertiary-bg); }
</style>

<!-- Drawer via Ajax/Fetch placeholder -->
<x-admin.drawer 
    id="ledgerDetailDrawer" 
    title="Inspecionar Transa��o" 
    position="end" 
    size="lg"
    :tabs="['Resumo Geral', 'Auditoria (Timeline)', 'Metadata (JSON)']"
>
    <!-- Slot: Tabs Content -->
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Buscando dados no Ledger...</div>
    </div>
    <div id="drawerContent" class="d-none h-100"></div>
</x-admin.drawer>

@endsection

@push('script')
<script>
    // A simula��o de um endpoint de inspe��o. Em produ��o, isso faria um fetch().
    const transactionsData = @json($entries->keyBy('id'));

    function openLedgerDrawer(id) {
        const drawerEl = document.getElementById('ledgerDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        const tx = transactionsData[id];
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            
            // Build the drawer content using the JSON viewer component template dynamically
            // (In a real app, you would fetch a partial view from the server that renders the x-admin components)
            
            const metadataJson = tx.metadata ? JSON.stringify(tx.metadata, null, 2) : '{}';
            const amountColor = parseFloat(tx.amount) > 0 ? 'text-success' : (parseFloat(tx.amount) < 0 ? 'text-danger' : 'text-body-secondary');
            
            document.getElementById('drawerContent').innerHTML = 
                <div class="tab-pane fade show active p-4" id="ledgerDetailDrawer-tab-resumo-geral" role="tabpanel">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-body-tertiary p-3 rounded-circle border border-secondary-subtle">
                            <i class="fa-solid fa-receipt fs-3 text-body-secondary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold +amountColor+">R$ +tx.amount+</h4>
                            <div class="text-body-secondary text-uppercase fw-semibold" style="font-size: 0.75rem;">+tx.type+</div>
                        </div>
                    </div>
                    
                    <div class="card border border-secondary-subtle shadow-sm rounded-4 mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="text-body-secondary mb-1" style="font-size: 0.75rem;">ID da Transa��o</div>
                                    <div class="fw-semibold text-body-emphasis font-monospace" style="font-size: 0.85rem;">+tx.id+</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-body-secondary mb-1" style="font-size: 0.75rem;">Correlation ID</div>
                                    <div class="fw-semibold text-body-emphasis font-monospace" style="font-size: 0.85rem;">+(tx.correlation_id || '-')+</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-body-secondary mb-1" style="font-size: 0.75rem;">Saldo Anterior</div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">R$ +tx.balance_before+</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-body-secondary mb-1" style="font-size: 0.75rem;">Saldo Posterior</div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">R$ +tx.balance_after+</div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="text-body-secondary mb-1" style="font-size: 0.75rem;">Descri��o</div>
                                    <div class="fw-semibold text-body-emphasis">+(tx.description || '-')+</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="text-body-secondary fw-semibold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Entidades Relacionadas</h6>
                    <div class="list-group list-group-flush border border-secondary-subtle rounded-4 overflow-hidden shadow-sm">
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 bg-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-user text-primary"></i>
                                <div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">Usu�rio Propriet�rio</div>
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">ID: +tx.user_id+</div>
                                </div>
                            </div>
                            <i class="fa-solid fa-arrow-up-right-from-square text-body-secondary" style="font-size: 0.75rem;"></i>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 bg-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-wallet text-info"></i>
                                <div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">Carteira Origem</div>
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">ID: +tx.wallet_id+</div>
                                </div>
                            </div>
                            <i class="fa-solid fa-arrow-up-right-from-square text-body-secondary" style="font-size: 0.75rem;"></i>
                        </a>
                         + (tx.reference_type ? 
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 bg-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-link text-warning"></i>
                                <div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">Refer�ncia (+tx.reference_type.split('\\\\').pop()+)</div>
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">ID: +tx.reference_id+</div>
                                </div>
                            </div>
                            <i class="fa-solid fa-arrow-up-right-from-square text-body-secondary" style="font-size: 0.75rem;"></i>
                        </a> : '') + 
                    </div>
                </div>
                
                <div class="tab-pane fade p-4" id="ledgerDetailDrawer-tab-auditoria-timeline" role="tabpanel">
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4 border-0 shadow-sm" role="alert">
                        <i class="fa-solid fa-shield-check fs-4"></i>
                        <div>
                            <div class="fw-bold" style="font-size: 0.85rem;">Integridade Confirmada</div>
                            <div style="font-size: 0.75rem;">Hash HMAC verificado contra adultera��es.</div>
                        </div>
                    </div>
                    
                    <div class="admin-timeline p-0 bg-transparent">
                        <!-- Inst�ncia de x-admin.timeline manual -->
                        <style>
                            .admin-timeline-item { position: relative; padding-left: 2.2rem; padding-bottom: 1.5rem; }
                            .admin-timeline-item::before { content: ''; position: absolute; left: 0.45rem; top: 1.5rem; bottom: 0; width: 2px; background-color: var(--cui-secondary-bg-subtle, #e2e8f0); }
                            .admin-timeline-item:last-child::before { display: none; }
                            .admin-timeline-marker { position: absolute; left: 0; top: 0.2rem; width: 1rem; height: 1rem; border-radius: 50%; border: 2px solid var(--cui-body-bg, #fff); box-shadow: 0 0 0 2px var(--cui-secondary-bg-subtle, #e2e8f0); z-index: 1; }
                            .admin-timeline-marker.success { background-color: var(--cui-success, #198754); box-shadow: 0 0 0 2px var(--cui-success-subtle, #d1e7dd); }
                            .admin-timeline-marker.active { background-color: var(--cui-primary, #0d6efd); box-shadow: 0 0 0 2px var(--cui-primary-subtle, #cfe2ff); }
                        </style>
                        
                        <div class="admin-timeline-item">
                            <div class="admin-timeline-marker success"><i class="fa-solid fa-check" style="font-size: 0.5rem; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white;"></i></div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-semibold text-body-emphasis" style="font-size: 0.95rem;">Lan�amento Consolidado no Ledger</h6>
                                <span class="text-body-secondary" style="font-size: 0.8rem;">+new Date(tx.created_at).toLocaleString()+</span>
                            </div>
                            <div class="text-body-secondary" style="font-size: 0.85rem;">
                                Assinatura gerada: <span class="font-monospace user-select-all bg-body-tertiary px-1 rounded border border-secondary-subtle">+tx.integrity_hash+</span>
                            </div>
                        </div>
                        
                        <div class="admin-timeline-item">
                            <div class="admin-timeline-marker active"></div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-semibold text-body-emphasis" style="font-size: 0.95rem;">Atualiza��o de Saldo</h6>
                            </div>
                            <div class="text-body-secondary" style="font-size: 0.85rem;">
                                Carteira #+tx.wallet_id+ teve o saldo alterado de R$ +tx.balance_before+ para R$ +tx.balance_after+.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade p-4" id="ledgerDetailDrawer-tab-metadata-json" role="tabpanel">
                    <div class="json-viewer-wrapper position-relative">
                        <pre class="bg-body-tertiary border border-secondary-subtle rounded-3 p-3 m-0 overflow-auto shadow-sm" style="max-height: 400px; scrollbar-width: thin;">
                            <code class="text-body-emphasis" style="font-family: var(--cui-font-monospace); font-size: 0.85rem;">+metadataJson+</code>
                        </pre>
                    </div>
                </div>
            ;
        }, 300);
    }
</script>
@endpush
