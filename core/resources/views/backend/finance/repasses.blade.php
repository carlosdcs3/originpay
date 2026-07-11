@extends('backend.layouts.app')
@section('title', 'Repasses & Settlements')

@section('content')
<x-admin.page-hero 
    title="Dashboard de Repasses" 
    description="Motor de liquidação financeira (Settlements & Splits). Acompanhe o dinheiro fluindo dos gateways para as contas finais ou sub-contas do ecossistema."
    status="Operacional"
    statusColor="success"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Repasses' => null
    ]"
/>

<x-admin.alerts-area :alerts="$alerts" />

<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Repasses Pendentes" 
        value="{{ $kpis['pending'] }}" 
        icon="fa-solid fa-clock-rotate-left" 
        color="warning" 
        subtitle="Aguardando liquidação"
    />
    <x-admin.kpi-card 
        title="Volume Liquidado" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['volume_settled'], 2, ',', '.') }}" 
        icon="fa-solid fa-money-bill-trend-up" 
        color="success" 
        subtitle="Repasses finalizados"
    />
    <x-admin.kpi-card 
        title="Falhas de Liquidação" 
        value="{{ $kpis['failures'] }}" 
        icon="fa-solid fa-triangle-exclamation" 
        color="danger" 
        subtitle="Devoluções bancárias"
    />
    <x-admin.kpi-card 
        title="Em Processamento" 
        value="{{ $kpis['processing'] }}" 
        icon="fa-solid fa-spinner" 
        color="info" 
        subtitle="Enviado ao Gateway"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.repasses') }}">
    <!-- Filtros avançados para Repasses -->
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['Merchant', 'Valor (Líquido)', 'Origem / Destino', 'Status', 'Conciliação', 'Data Prevista', '']"
    :paginator="$settlements"
    emptyStateTitle="Nenhum repasse agendado"
    emptyStateDesc="Verifique os filtros aplicados.">
    
    @foreach($settlements as $st)
        <tr style="cursor: pointer;" onclick="openSettlementDrawer({{ $st->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">{{ $st->user->first_name ?? 'N/A' }} {{ $st->user->last_name ?? '' }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">UID: {{ $st->user_id }}</div>
            </td>
            <td>
                <div class="fw-bold text-success" style="font-size: 0.85rem;">{{ siteCurrency('symbol') }}{{ number_format($st->net_amount, 2, ',', '.') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">Bruto: {{ number_format($st->gross_amount, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-body-tertiary border border-secondary-subtle text-body-emphasis px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                        {{ $st->gateway->name ?? 'N/A' }}
                    </span>
                    <i class="fa-solid fa-arrow-right text-body-secondary" style="font-size: 0.7rem;"></i>
                    <span class="badge bg-primary-subtle border border-primary-subtle text-primary px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                        {{ substr($st->destination, 0, 15) }}...
                    </span>
                </div>
            </td>
            <td>
                @php
                    $statusColors = [
                        'pending' => 'secondary',
                        'processing' => 'warning',
                        'settled' => 'success',
                        'failed' => 'danger'
                    ];
                    $color = $statusColors[$st->status] ?? 'primary';
                @endphp
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $st->status }}</span>
            </td>
            <td>
                @if($st->status == 'settled')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">OK</span>
                @else
                    <span class="text-body-secondary" style="font-size: 0.75rem;">N/A</span>
                @endif
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $st->scheduled_date ? $st->scheduled_date->format('d/m/Y') : 'N/A' }}</div>
                @if($st->settled_date)
                    <div class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fa-solid fa-check me-1"></i> Liq: {{ $st->settled_date->format('d/m') }}</div>
                @endif
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openSettlementDrawer({{ $st->id }})">
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
    id="settlementDetailDrawer" 
    title="Auditoria de Liquidação (Repasse)" 
    position="end" 
    size="xl"
    :tabs="['Resumo Geral', 'Origem dos Fundos', 'Regra de Split', 'Gateway', 'Wallet Impactada', 'Ledger', 'Conciliação', 'Timeline', 'Metadata']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Traçando fluxo de fundos...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
    
    <x-slot name="footerActions">
        <div class="w-100 d-flex justify-content-between">
            <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeSettlementDrawer()">Fechar</button>
            <div class="d-flex gap-2">
                <button class="btn btn-warning fw-semibold shadow-sm text-dark"><i class="fa-solid fa-rotate me-2"></i> Reprocessar</button>
                <button class="btn btn-success fw-semibold shadow-sm text-white"><i class="fa-solid fa-bolt me-2"></i> Forçar Liquidação</button>
            </div>
        </div>
    </x-slot>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const stData = @json($settlements->keyBy('id'));
    
    function openSettlementDrawer(id) {
        const drawerEl = document.getElementById('settlementDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        const tx = stData[id];
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');
            
            document.getElementById('drawerContent').innerHTML = 
                <!-- TAB 1: Resumo -->
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="settlementDetailDrawer-tab-resumo-geral" role="tabpanel">
                    <div class="card border border-secondary-subtle shadow-sm rounded-4 bg-body-tertiary mb-4">
                        <div class="card-body p-4 text-center">
                            <div class="text-body-secondary text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Valor a Liquidar</div>
                            <h2 class="fw-bold mb-0 text-success">R$ +tx.net_amount+</h2>
                            <div class="mt-2">
                                <span class="badge bg-secondary-subtle text-secondary">Bruto: R$ +(tx.gross_amount || 0)+</span>
                                <span class="badge bg-warning-subtle text-warning">Custos/Taxas: R$ +(tx.fees || 0)+</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border border-secondary-subtle shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="text-body-secondary fw-semibold text-uppercase mb-3" style="font-size: 0.75rem;">Trajeto do Fundo</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-center">
                                    <div class="bg-primary-subtle text-primary rounded p-3 mb-2"><i class="fa-solid fa-server fs-4"></i></div>
                                    <div class="fw-bold text-body-emphasis" style="font-size: 0.85rem;">+(tx.gateway ? tx.gateway.name : 'Master')+</div>
                                </div>
                                <div class="flex-grow-1 px-4 text-center">
                                    <div class="progress" style="height: 2px;">
                                        <div class="progress-bar bg-success" style="width: 100%;"></div>
                                    </div>
                                    <i class="fa-solid fa-plane-departure text-success mt-2"></i>
                                </div>
                                <div class="text-center">
                                    <div class="bg-success-subtle text-success rounded p-3 mb-2"><i class="fa-solid fa-building-columns fs-4"></i></div>
                                    <div class="fw-bold text-body-emphasis" style="font-size: 0.85rem;">Banco Destino</div>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <span class="badge bg-body-tertiary text-body-emphasis border border-secondary-subtle px-3 py-2 rounded-pill font-monospace">
                                    Conta: +(tx.destination || 'N/A')+
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder Tabs -->
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="settlementDetailDrawer-tab-origem-dos-fundos" role="tabpanel">
                    <div class="text-center py-5">Quais transações compõem este lote.</div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="settlementDetailDrawer-tab-regra-de-split" role="tabpanel">
                    <div class="alert alert-info border-info-subtle">
                        Visualização de Split Ativo. +(tx.split_rule_id ? ('Regra ' + tx.split_rule_id) : 'Sem Split Aplicado.')+
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="settlementDetailDrawer-tab-wallet-impactada" role="tabpanel">
                    <div class="alert alert-success border-success-subtle">
                        Impacto projetado na conta primária. Se liquidado forçado, a baixa ocorrerá via Motor Financeiro.
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="settlementDetailDrawer-tab-ledger" role="tabpanel">
                    <div class="text-center py-5">Lançamento de 'Settlement Payout' no livro-caixa.</div>
                </div>
            ;
        }, 300);
    }
    
    function closeSettlementDrawer() {
        const drawerEl = document.getElementById('settlementDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
</script>
@endpush
