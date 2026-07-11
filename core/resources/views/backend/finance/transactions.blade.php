@extends('backend.layouts.app')
@section('title', 'Transações Consolidadas')

@section('content')
<x-admin.page-hero 
    title="Dashboard de Transações" 
    description="Visão operacional consolidada. Centraliza cobranças, saques, taxas, estornos e ajustes em uma única timeline analítica. Não cria nova verdade financeira; apenas espelha o Ledger, Wallets e Gateways."
    status="Consolidado"
    statusColor="secondary"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Transações' => null
    ]"
/>

<x-admin.alerts-area :alerts="$alerts" />

<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Volume Total" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['total_volume'], 2, ',', '.') }}" 
        icon="fa-solid fa-arrows-spin" 
        color="primary" 
        subtitle="Movimentação global"
    />
    <x-admin.kpi-card 
        title="Entradas (Cash In)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['in'], 2, ',', '.') }}" 
        icon="fa-solid fa-arrow-turn-down" 
        color="success" 
        subtitle="Soma de créditos (+)"
    />
    <x-admin.kpi-card 
        title="Saídas (Cash Out)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['out'], 2, ',', '.') }}" 
        icon="fa-solid fa-arrow-turn-up" 
        color="danger" 
        subtitle="Soma de débitos (-)"
    />
    <x-admin.kpi-card 
        title="Taxas & Custos" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['fees'], 2, ',', '.') }}" 
        icon="fa-solid fa-percent" 
        color="warning" 
        subtitle="Arrecadação operacional"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.transactions') }}">
    <!-- Filtros avançados consolidados -->
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['ID / Usuário', 'Movimentação', 'Gateway', 'Origem (Ref)', 'Data', '']"
    :paginator="$transactions"
    emptyStateTitle="Nenhuma movimentação"
    emptyStateDesc="Não há transações correspondentes aos filtros.">
    
    @foreach($transactions as $tx)
        <tr style="cursor: pointer;" onclick="openTransactionDrawer({{ $tx->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold text-body-emphasis font-monospace" style="font-size: 0.85rem;">{{ $tx->trx }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $tx->user->first_name ?? 'N/A' }} {{ $tx->user->last_name ?? '' }}</div>
            </td>
            <td>
                @if($tx->trx_type == '+')
                    <div class="fw-bold text-success" style="font-size: 0.85rem;">+{{ siteCurrency('symbol') }}{{ number_format($tx->amount, 2, ',', '.') }}</div>
                @else
                    <div class="fw-bold text-danger" style="font-size: 0.85rem;">-{{ siteCurrency('symbol') }}{{ number_format($tx->amount, 2, ',', '.') }}</div>
                @endif
                <div class="text-body-secondary" style="font-size: 0.75rem;">Taxa: {{ siteCurrency('symbol') }}{{ number_format($tx->charge, 2, ',', '.') }}</div>
            </td>
            <td>
                <span class="badge bg-body-tertiary border border-secondary-subtle text-body-emphasis px-2 py-1 rounded-pill text-uppercase">
                    {{ $tx->gateway ?? 'Interno' }}
                </span>
            </td>
            <td>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill" style="font-size: 0.7rem;">{{ $tx->details ?? 'Operação Geral' }}</span>
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $tx->created_at->format('d/m/Y') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $tx->created_at->format('H:i') }}</div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openTransactionDrawer({{ $tx->id }})">
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
    id="transactionDetailDrawer" 
    title="Inspeção Analítica (Transação)" 
    position="end" 
    size="xl"
    :tabs="['Resumo Consolidado', 'Entidade de Origem', 'Gateway', 'Ledger', 'Wallet Impactada', 'Timeline', 'Metadata']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Consolidando fontes de verdade...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
    
    <x-slot name="footerActions">
        <div class="w-100 d-flex justify-content-between">
            <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeTransactionDrawer()">Fechar</button>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.finance.ledger') }}" class="btn btn-primary fw-semibold shadow-sm text-white"><i class="fa-solid fa-book-journal-whills me-2"></i> Rastrear no Ledger</a>
            </div>
        </div>
    </x-slot>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const txData = @json($transactions->keyBy('id'));
    
    function openTransactionDrawer(id) {
        const drawerEl = document.getElementById('transactionDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        const tx = txData[id];
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');
            
            const colorClass = tx.trx_type === '+' ? 'success' : 'danger';
            const valFormatted = (tx.trx_type === '+' ? '+' : '-') + 'R$ ' + tx.amount;
            
            document.getElementById('drawerContent').innerHTML = 
                <!-- TAB 1: Resumo -->
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="transactionDetailDrawer-tab-resumo-consolidado" role="tabpanel">
                    <div class="card border border-secondary-subtle shadow-sm rounded-4 bg-body-tertiary mb-4">
                        <div class="card-body p-4 text-center">
                            <div class="text-body-secondary text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Movimentação Financeira</div>
                            <h2 class="fw-bold mb-0 text-+colorClass+">+valFormatted+</h2>
                            <div class="mt-2">
                                <span class="badge bg-warning-subtle text-warning">Taxa Cobrada: R$ +(tx.charge || 0)+</span>
                                <span class="badge bg-secondary-subtle text-secondary">Saldo Restante: R$ +(tx.post_balance || 'N/A')+</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info border-info-subtle d-flex align-items-center gap-3 mb-4">
                        <i class="fa-solid fa-circle-info fs-4"></i>
                        <div>
                            <strong>Natureza Operacional:</strong> +(tx.details || 'Transação Padrão')+<br>
                            <small>Esta tela não cria saldos. Ela consolida a visão para facilitar o suporte.</small>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder Tabs -->
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="transactionDetailDrawer-tab-entidade-de-origem" role="tabpanel">
                    <div class="alert alert-light border-secondary-subtle text-center py-5">
                        <i class="fa-solid fa-link fs-1 text-secondary mb-3"></i>
                        <p>Link para Cobrança ou Saque original.</p>
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="transactionDetailDrawer-tab-gateway" role="tabpanel">
                    <div class="text-center py-5">Gateway mapeado: +(tx.gateway || 'Interno')+</div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="transactionDetailDrawer-tab-ledger" role="tabpanel">
                    <div class="text-center py-5">Assinatura imutável atrelada: +(tx.trx)+</div>
                </div>
            ;
        }, 300);
    }
    
    function closeTransactionDrawer() {
        const drawerEl = document.getElementById('transactionDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
</script>
@endpush
