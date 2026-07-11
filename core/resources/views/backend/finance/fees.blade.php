@extends('backend.layouts.app')
@section('title', 'Auditoria de Taxas e Custos')

@section('content')
<x-admin.page-hero 
    title="Dashboard de Taxas (Fees)" 
    description="Centro de Margem de Lucro. Analise a diferença entre o que os Gateways cobram da OriginPay e o que a OriginPay cobra dos Merchants."
    status="Lucratividade"
    statusColor="info"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Taxas' => null
    ]"
/>

<x-admin.alerts-area :alerts="$alerts" />

<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Margem Líquida" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['net_margin'], 2, ',', '.') }}" 
        icon="fa-solid fa-piggy-bank" 
        color="success" 
        subtitle="Lucro bruto retido"
    />
    <x-admin.kpi-card 
        title="Receita (Merchant Fees)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['ops_fees'], 2, ',', '.') }}" 
        icon="fa-solid fa-hand-holding-dollar" 
        color="primary" 
        subtitle="Taxas cobradas"
    />
    <x-admin.kpi-card 
        title="Custo (Gateway Fees)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['gateway_fees'], 2, ',', '.') }}" 
        icon="fa-solid fa-file-invoice-dollar" 
        color="warning" 
        subtitle="Fatura das adquirentes"
    />
    <x-admin.kpi-card 
        title="Divergências" 
        value="{{ $kpis['divergent'] }}" 
        icon="fa-solid fa-code-compare" 
        color="danger" 
        subtitle="Margem corrompida"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.fees') }}">
    <!-- Filtros avançados para Taxas -->
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['Merchant / Operação', 'Gateway', 'T. OriginPay', 'Custo Gateway', 'Margem', 'Status', '']"
    :paginator="$fees"
    emptyStateTitle="Nenhuma taxa registrada"
    emptyStateDesc="Verifique os filtros de operação.">
    
    @foreach($fees as $fee)
        <tr style="cursor: pointer;" onclick="openFeeDrawer({{ $fee->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">{{ $fee->user->first_name ?? 'N/A' }} {{ $fee->user->last_name ?? '' }}</div>
                <div class="text-body-secondary text-uppercase" style="font-size: 0.75rem;">{{ $fee->operation_type }}</div>
            </td>
            <td>
                <span class="badge bg-body-tertiary border border-secondary-subtle text-body-emphasis px-2 py-1 rounded-pill text-uppercase">
                    {{ $fee->gateway->name ?? 'N/A' }}
                </span>
            </td>
            <td>
                <div class="fw-bold text-success" style="font-size: 0.85rem;">{{ siteCurrency('symbol') }}{{ number_format($fee->merchant_fee, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="fw-bold text-danger" style="font-size: 0.85rem;">-{{ siteCurrency('symbol') }}{{ number_format($fee->gateway_cost, 2, ',', '.') }}</div>
            </td>
            <td>
                @php
                    $marginClass = $fee->margin < 0 ? 'text-danger' : ($fee->margin > 0 ? 'text-success' : 'text-body-secondary');
                @endphp
                <div class="fw-bold {{ $marginClass }}" style="font-size: 0.85rem;">{{ siteCurrency('symbol') }}{{ number_format($fee->margin, 2, ',', '.') }}</div>
            </td>
            <td>
                @php
                    $statusColors = [
                        'expected' => 'secondary',
                        'confirmed' => 'success',
                        'divergent' => 'danger'
                    ];
                    $color = $statusColors[$fee->status] ?? 'primary';
                @endphp
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $fee->status }}</span>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openFeeDrawer({{ $fee->id }})">
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
    id="feeDetailDrawer" 
    title="Auditoria de Taxas e Custos" 
    position="end" 
    size="xl"
    :tabs="['Resumo Financeiro', 'Regra Aplicada', 'Gateway Cost', 'Merchant Fee', 'Margem', 'Ledger', 'Wallet Impactada', 'Conciliação', 'Metadata']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Auditando custos cruzados...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
    
    <x-slot name="footerActions">
        <div class="w-100 d-flex justify-content-between">
            <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeFeeDrawer()">Fechar</button>
            <div class="d-flex gap-2">
                <button class="btn btn-warning fw-semibold shadow-sm text-dark"><i class="fa-solid fa-rotate-right me-2"></i> Recalcular Taxa</button>
                <button class="btn btn-primary fw-semibold shadow-sm text-white"><i class="fa-solid fa-check-double me-2"></i> Marcar como Revisado</button>
            </div>
        </div>
    </x-slot>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const feeData = @json($fees->keyBy('id'));
    
    function openFeeDrawer(id) {
        const drawerEl = document.getElementById('feeDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        const tx = feeData[id];
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');
            
            const isLoss = tx.margin < 0;
            const bgCard = isLoss ? 'bg-danger-subtle border-danger-subtle' : 'bg-success-subtle border-success-subtle';
            const textColor = isLoss ? 'text-danger' : 'text-success';
            
            document.getElementById('drawerContent').innerHTML = 
                <!-- TAB 1: Resumo -->
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="feeDetailDrawer-tab-resumo-financeiro" role="tabpanel">
                    <div class="card border shadow-sm rounded-4 +bgCard+ mb-4">
                        <div class="card-body p-4 text-center">
                            <div class="text-uppercase fw-semibold mb-2 +textColor+" style="font-size: 0.75rem;">+(isLoss ? 'Prejuízo Operacional (Subsídio)' : 'Margem Líquida Retida')+</div>
                            <h2 class="fw-bold mb-0 +textColor+">R$ +tx.margin+</h2>
                        </div>
                    </div>
                    
                    <div class="row gx-3 mb-4">
                        <div class="col-6">
                            <div class="card border border-secondary-subtle bg-body-tertiary">
                                <div class="card-body text-center">
                                    <div class="text-body-secondary mb-1" style="font-size: 0.75rem;">Cobrado do Lojista</div>
                                    <div class="fw-bold text-success">R$ +tx.merchant_fee+</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card border border-secondary-subtle bg-body-tertiary">
                                <div class="card-body text-center">
                                    <div class="text-body-secondary mb-1" style="font-size: 0.75rem;">Custo no Gateway</div>
                                    <div class="fw-bold text-danger">R$ +tx.gateway_cost+</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-info-subtle">
                        <strong>Lógica Defensiva de Saldo</strong><br>
                        A taxa do merchant é retida antes de cair na \WalletBalance\ (na operação original). O custo do gateway é descontado na Master Wallet.
                    </div>
                </div>
                
                <!-- Placeholder Tabs -->
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="feeDetailDrawer-tab-gateway-cost" role="tabpanel">
                    <div class="text-center py-5">Extrato da tabela de custos do +(tx.gateway ? tx.gateway.name : 'Provedor')+.</div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="feeDetailDrawer-tab-ledger" role="tabpanel">
                    <div class="text-center py-5">Lançamento contábil que prova o débito da taxa operacional.</div>
                </div>
            ;
        }, 300);
    }
    
    function closeFeeDrawer() {
        const drawerEl = document.getElementById('feeDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
</script>
@endpush
