@extends('backend.finance.index')
@section('finance_title', 'Wallets (Saldos)')
@section('finance_desc', 'PosiÃ§Ã£o financeira em tempo real.')

@section('finance_content')
@include('backend.finance.partials._wallet_tabs')
<x-admin.page-hero 
    title="Dashboard de Wallets" 
    description="Posiï¿½ï¿½o financeira em tempo real. Visï¿½o consolidada para os usuï¿½rios e detalhamento operacional por gateway para a administraï¿½ï¿½o."
    status="Online"
    statusColor="success"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Wallets' => null
    ]"
    :quickStats="[
        ['label' => 'Ativas', 'value' => $kpis['active_count']],
        ['label' => 'Divergï¿½ncias', 'value' => $kpis['divergent_count']]
    ]"
/>

<x-admin.alerts-area :alerts="$alerts" />

<!-- KPI Grid -->
<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Saldo Total (Custï¿½dia)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['total'], 2, ',', '.') }}" 
        icon="fa-solid fa-wallet" 
        color="primary" 
        subtitle="Soma de todos os saldos"
    />
    <x-admin.kpi-card 
        title="Disponï¿½vel (Liquidez)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['available'], 2, ',', '.') }}" 
        icon="fa-solid fa-money-bill-wave" 
        color="success" 
        subtitle="Livre para saque"
    />
    <x-admin.kpi-card 
        title="Pendente (D+N)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['pending'], 2, ',', '.') }}" 
        icon="fa-solid fa-clock" 
        color="secondary" 
        subtitle="Aguardando liquidaï¿½ï¿½o"
    />
    <x-admin.kpi-card 
        title="Bloqueado" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['blocked'], 2, ',', '.') }}" 
        icon="fa-solid fa-lock" 
        color="warning" 
        subtitle="Retido por seguranï¿½a/disputa"
    />
</x-admin.kpi-grid>

<!-- Gateway Distribution (Native HTML/CSS) -->
@if(count($distributionData) > 0)
<div class="card border border-secondary-subtle shadow-sm rounded-4 mb-4 bg-body">
    <div class="card-body p-4">
        <h6 class="fw-bold text-body-emphasis mb-3" style="font-size: 0.95rem;">Distribuiï¿½ï¿½o de Custï¿½dia por Gateway</h6>
        
        <div class="progress rounded-pill mb-3" style="height: 12px; background-color: var(--cui-secondary-bg-subtle);">
            @foreach($distributionData as $dist)
                <div class="progress-bar {{ $dist['colorClass'] }}" role="progressbar" style="width: {{ $dist['percentage'] }}%;" aria-valuenow="{{ $dist['percentage'] }}" aria-valuemin="0" aria-valuemax="100" title="{{ $dist['name'] }}: {{ $dist['percentage'] }}%"></div>
            @endforeach
        </div>
        
        <div class="d-flex flex-wrap gap-4 align-items-center">
            @foreach($distributionData as $dist)
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle {{ $dist['colorClass'] }}" style="width: 10px; height: 10px;"></div>
                    <div>
                        <div class="text-body-emphasis fw-semibold" style="font-size: 0.85rem;">{{ $dist['name'] }}</div>
                        <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $dist['percentage'] }}% ({{ siteCurrency('symbol') }} {{ number_format($dist['volume'], 2, ',', '.') }})</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<x-admin.smart-filter action="{{ route('admin.finance.balances') }}">
    <!-- Pode adicionar filtros avanï¿½ados (status, currency) aqui -->
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['Merchant (Usuï¿½rio)', 'Saldo Consolidado', 'Breakdown', 'Gateway Principal', 'Status', 'Atualizaï¿½ï¿½o', '']"
    :paginator="$wallets"
    emptyStateTitle="Nenhuma Wallet Encontrada"
    emptyStateDesc="Verifique os filtros e tente novamente.">
    
    @foreach($wallets as $wallet)
        <tr style="cursor: pointer;" onclick="openWalletDrawer({{ $wallet->id }})" class="table-row-hover">
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">
                        {{ substr($wallet->user->first_name ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">
                            {{ $wallet->user->first_name ?? 'N/A' }} {{ $wallet->user->last_name ?? '' }}
                        </div>
                        <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $wallet->user->email ?? 'N/A' }}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="fw-bold text-body-emphasis fs-6">{{ siteCurrency('symbol') }} {{ number_format($wallet->balance, 2, ',', '.') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">Carteira ID: {{ $wallet->id }}</div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-3" style="font-size: 0.75rem;">
                    <div class="text-success fw-semibold" title="Disponï¿½vel"><i class="fa-solid fa-circle me-1" style="font-size: 0.4rem;"></i>{{ number_format($wallet->available_balance, 2, ',', '.') }}</div>
                    <div class="text-secondary fw-semibold" title="Pendente"><i class="fa-solid fa-circle me-1" style="font-size: 0.4rem;"></i>{{ number_format($wallet->pending_balance, 2, ',', '.') }}</div>
                    <div class="text-warning fw-semibold" title="Bloqueado"><i class="fa-solid fa-circle me-1" style="font-size: 0.4rem;"></i>{{ number_format($wallet->blocked_balance ?? 0, 2, ',', '.') }}</div>
                </div>
            </td>
            <td>
                @php
                    // Identificar onde estï¿½ o maior dinheiro
                    $topBal = $wallet->balances->sortByDesc('available')->first();
                    $topGatewayName = $topBal ? ($topBal->gateway->name ?? 'N/A') : 'Local';
                @endphp
                <span class="badge bg-body-tertiary text-body-emphasis border border-secondary-subtle px-2 py-1 rounded-pill">{{ $topGatewayName }}</span>
            </td>
            <td>
                @if($wallet->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Ativa</span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">Bloqueada</span>
                @endif
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $wallet->updated_at->format('d/m/Y') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $wallet->updated_at->format('H:i') }}</div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openWalletDrawer({{ $wallet->id }})">
                    <i class="fa-solid fa-chevron-right text-body-secondary"></i>
                </button>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<style>
    .table-row-hover:hover td { background-color: var(--cui-tertiary-bg); }
</style>

<!-- Drawer -->
<x-admin.drawer 
    id="walletDetailDrawer" 
    title="Inspeï¿½ï¿½o de Wallet (Admin View)" 
    position="end" 
    size="xl"
    :tabs="['Resumo Geral', 'Saldos por Gateway', 'Transaï¿½ï¿½es (Ledger)', 'Saques', 'Auditoria', 'Metadata']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Sincronizando custï¿½dia...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
    
    <x-slot name="footerActions">
        <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeWalletDrawer()">Voltar</button>
        <button class="btn btn-danger fw-semibold shadow-sm text-white"><i class="fa-solid fa-snowflake me-2"></i> Congelar</button>
        <button class="btn btn-primary fw-semibold shadow-sm" onclick="openAdjustmentModal()"><i class="fa-solid fa-scale-balanced me-2"></i> Criar Ajuste (Manual)</button>
    </x-slot>
</x-admin.drawer>

<!-- Modal de Ajuste Manual Seguro -->
<div class="modal fade" id="adjustmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-body border-secondary-subtle">
      <div class="modal-header border-bottom border-secondary-subtle">
        <h5 class="modal-title fw-bold text-body-emphasis"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Lanï¿½amento de Ajuste Manual</h5>
        <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning border-warning-subtle text-dark" style="font-size: 0.85rem;">
            <strong>Atenï¿½ï¿½o:</strong> Este ajuste gerarï¿½ um evento de auditoria no Ledger. O saldo nunca ï¿½ alterado via UPDATE direto.
        </div>
        <form id="adjustmentForm">
            <div class="mb-3">
                <label class="form-label text-body-secondary fw-semibold" style="font-size: 0.85rem;">Tipo de Ajuste</label>
                <select class="form-select border-secondary-subtle bg-body-tertiary">
                    <option value="credit">Crï¿½dito (+)</option>
                    <option value="debit">Dï¿½bito (-)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label text-body-secondary fw-semibold" style="font-size: 0.85rem;">Valor</label>
                <div class="input-group">
                    <span class="input-group-text border-secondary-subtle bg-body-tertiary">{{ siteCurrency('symbol') }}</span>
                    <input type="number" step="0.01" class="form-control border-secondary-subtle" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label text-body-secondary fw-semibold" style="font-size: 0.85rem;">Provedor (Gateway)</label>
                <select class="form-select border-secondary-subtle bg-body-tertiary">
                    <!-- Populated dynamically via JS -->
                    <option value="">Balanceador Principal</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label text-body-secondary fw-semibold" style="font-size: 0.85rem;">Motivo da Auditoria (Obrigatï¿½rio)</label>
                <textarea class="form-control border-secondary-subtle" rows="3" required placeholder="Ex: Compensaï¿½ï¿½o de disputa offline..."></textarea>
            </div>
        </form>
      </div>
      <div class="modal-footer border-top border-secondary-subtle d-flex justify-content-between">
        <span class="text-muted small">Os ajustes manuais de ledger não estão disponíveis neste painel.</span>
        <button type="button" class="btn btn-light" data-coreui-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('script')
<script>
    const walletsData = @json($wallets->keyBy('id'));
    let currentWallet = null;

    function openWalletDrawer(id) {
        currentWallet = walletsData[id];
        const tx = currentWallet;
        const drawerEl = document.getElementById('walletDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');
            
            const metadataJson = tx ? JSON.stringify(tx, null, 2) : '{}';
            
            let gatewayBreakdownsHtml = '';
            if(tx.balances && tx.balances.length > 0) {
                tx.balances.forEach(bal => {
                    const gName = bal.gateway ? bal.gateway.name : 'Unknown';
                    gatewayBreakdownsHtml += 
                    <div class="card border border-secondary-subtle mb-3 bg-body shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary-subtle pb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary-subtle text-primary p-2 rounded"><i class="fa-solid fa-building-columns"></i></div>
                                    <h6 class="fw-bold mb-0">+gName+</h6>
                                </div>
                                <span class="badge bg-success-subtle text-success">Online</span>
                            </div>
                            <div class="row text-center g-2">
                                <div class="col-4">
                                    <div class="text-body-secondary" style="font-size: 0.7rem;">DISPONï¿½VEL</div>
                                    <div class="fw-bold text-success" style="font-size: 0.85rem;">R$ +bal.available+</div>
                                </div>
                                <div class="col-4 border-start border-end border-secondary-subtle">
                                    <div class="text-body-secondary" style="font-size: 0.7rem;">PENDENTE</div>
                                    <div class="fw-bold text-secondary" style="font-size: 0.85rem;">R$ +bal.pending+</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-body-secondary" style="font-size: 0.7rem;">BLOQUEADO</div>
                                    <div class="fw-bold text-warning" style="font-size: 0.85rem;">R$ +bal.blocked+</div>
                                </div>
                            </div>
                        </div>
                    </div>;
                });
            } else {
                gatewayBreakdownsHtml = '<div class="alert alert-secondary border-secondary-subtle">Nenhum gateway alocado para esta carteira.</div>';
            }
            
            document.getElementById('drawerContent').innerHTML = 
                <!-- TAB 1: Resumo -->
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="walletDetailDrawer-tab-resumo-geral" role="tabpanel">
                    <div class="card border border-secondary-subtle shadow-sm rounded-4 bg-primary text-white mb-4">
                        <div class="card-body p-4 text-center">
                            <div class="text-white-50 text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Saldo Consolidado Global</div>
                            <h2 class="fw-bold mb-3">R$ +tx.balance+</h2>
                            
                            <div class="d-flex justify-content-center gap-4 text-start mt-4 pt-3 border-top border-white-50">
                                <div>
                                    <div class="text-white-50" style="font-size: 0.7rem;">DISPONï¿½VEL</div>
                                    <div class="fw-bold text-white" style="font-size: 0.9rem;">R$ +tx.available_balance+</div>
                                </div>
                                <div>
                                    <div class="text-white-50" style="font-size: 0.7rem;">PENDENTE</div>
                                    <div class="fw-bold text-white" style="font-size: 0.9rem;">R$ +tx.pending_balance+</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="text-body-secondary fw-semibold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Proprietï¿½rio</h6>
                    <div class="list-group list-group-flush border border-secondary-subtle rounded-4 overflow-hidden shadow-sm">
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 bg-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-tertiary p-2 rounded-circle"><i class="fa-solid fa-user text-primary"></i></div>
                                <div>
                                    <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">+(tx.user ? tx.user.first_name + ' ' + (tx.user.last_name || '') : 'N/A')+</div>
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">+(tx.user ? tx.user.email : '')+</div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-body-secondary" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                </div>
                
                <!-- TAB 2: Breakdown por Gateway -->
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="walletDetailDrawer-tab-saldos-por-gateway" role="tabpanel">
                    <h5 class="fw-bold text-body-emphasis mb-1">Custï¿½dia Descentralizada</h5>
                    <p class="text-body-secondary mb-4" style="font-size: 0.85rem;">Visï¿½o exclusivamente operacional. O Merchant nï¿½o tem acesso a esta fragmentaï¿½ï¿½o.</p>
                    
                    +gatewayBreakdownsHtml+
                </div>
                
                <!-- Outras Abas Placeholder -->
                <div class="tab-pane fade p-4 h-100 overflow-auto" id="walletDetailDrawer-tab-transacoes--ledger-" role="tabpanel">
                    <div class="text-center py-5 text-body-secondary">
                        <i class="fa-solid fa-book-journal-whills fs-1 mb-3"></i>
                        <p>Integraï¿½ï¿½o com o Ledger via API (Lazy Load) em desenvolvimento...</p>
                        <a href="{{ route('admin.finance.ledger') }}" class="btn btn-outline-primary btn-sm mt-2">Ir para Ledger</a>
                    </div>
                </div>
                
                <div class="tab-pane fade p-4 h-100 overflow-auto" id="walletDetailDrawer-tab-saques" role="tabpanel">
                    <div class="text-center py-5 text-body-secondary">Consultas de Saque atreladas a esta carteira ficarï¿½o aqui.</div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto" id="walletDetailDrawer-tab-auditoria" role="tabpanel">
                    <div class="text-center py-5 text-body-secondary">Logs de modificaï¿½ï¿½o e status da Wallet.</div>
                </div>
                
                <!-- TAB 6: JSON -->
                <div class="tab-pane fade p-4 h-100 overflow-auto" id="walletDetailDrawer-tab-metadata" role="tabpanel">
                    <div class="json-viewer-wrapper position-relative">
                        <pre class="bg-body-tertiary border border-secondary-subtle rounded-3 p-3 m-0 overflow-auto shadow-sm" style="max-height: 400px; scrollbar-width: thin;">
                            <code class="text-body-emphasis" style="font-family: var(--cui-font-monospace); font-size: 0.85rem;">+metadataJson+</code>
                        </pre>
                    </div>
                </div>
            ;
        }, 300);
    }
    
    function closeWalletDrawer() {
        const drawerEl = document.getElementById('walletDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
    
    function openAdjustmentModal() {
        if(!currentWallet) return;
        const modal = new coreui.Modal(document.getElementById('adjustmentModal'));
        modal.show();
    }
</script>
@endpush

