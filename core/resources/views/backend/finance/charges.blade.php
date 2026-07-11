@extends('backend.layouts.app')
@section('title', 'Cobranças (Charges)')

@section('content')
<x-admin.page-hero 
    title="Dashboard de Cobranças" 
    description="Funil de entrada de capital (Cash In). Acompanhe recebimentos via PIX, Cartão e Boleto em tempo real, monitorando conversão e tempo de liquidação."
    status="Operacional"
    statusColor="success"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Cobranças' => null
    ]"
/>

<x-admin.alerts-area :alerts="$alerts" />

<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Cobranças Emitidas" 
        value="{{ $kpis['issued'] }}" 
        icon="fa-solid fa-file-invoice" 
        color="primary" 
        subtitle="Total histórico"
    />
    <x-admin.kpi-card 
        title="Pagas Hoje" 
        value="{{ $kpis['paid_today'] }}" 
        icon="fa-solid fa-check-double" 
        color="success" 
        subtitle="Liquidadas no dia"
    />
    <x-admin.kpi-card 
        title="Volume (TPV)" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['tpv'], 2, ',', '.') }}" 
        icon="fa-solid fa-chart-line" 
        color="info" 
        subtitle="Total recebido"
    />
    <x-admin.kpi-card 
        title="Ticket Médio" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['ticket_avg'], 2, ',', '.') }}" 
        icon="fa-solid fa-ticket" 
        color="secondary" 
        subtitle="Valor médio por cobrança"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.charges') }}">
    <!-- Filtros avançados para Cobranças -->
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['Cobrança / Usuário', 'Valor Bruto', 'Método', 'Status', 'Conciliação', 'Data', '']"
    :paginator="$charges"
    emptyStateTitle="Nenhuma cobrança"
    emptyStateDesc="Não há solicitações correspondentes aos filtros.">
    
    @foreach($charges as $charge)
        <tr style="cursor: pointer;" onclick="openChargeDrawer({{ $charge->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold text-body-emphasis font-monospace" style="font-size: 0.85rem;">{{ substr($charge->correlation_id ?? $charge->id, 0, 12) }}...</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">Usuário: {{ $charge->user->first_name ?? 'N/A' }}</div>
            </td>
            <td>
                <div class="fw-bold text-success" style="font-size: 0.85rem;">+{{ siteCurrency('symbol') }}{{ number_format($charge->amount, 2, ',', '.') }}</div>
            </td>
            <td>
                @php
                    // Mocks para method format (if enum value not directly string)
                    $method = $charge->payment_method;
                    if (is_object($method) && enum_exists(get_class($method))) $method = $method->value ?? 'unknown';
                @endphp
                <span class="badge bg-body-tertiary border border-secondary-subtle text-body-emphasis px-2 py-1 rounded-pill text-uppercase">{{ $method }}</span>
            </td>
            <td>
                @php
                    $status = is_object($charge->status) ? $charge->status->value : $charge->status;
                    $statusColors = [
                        'pending' => 'secondary',
                        'paid' => 'success',
                        'canceled' => 'dark',
                        'expired' => 'warning',
                        'failed' => 'danger'
                    ];
                    $color = $statusColors[$status] ?? 'primary';
                @endphp
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $status }}</span>
            </td>
            <td>
                @if($status == 'paid')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">OK</span>
                @else
                    <span class="text-body-secondary" style="font-size: 0.75rem;">N/A</span>
                @endif
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $charge->created_at->format('d/m/Y') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $charge->created_at->format('H:i') }}</div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openChargeDrawer({{ $charge->id }})">
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
    id="chargeDetailDrawer" 
    title="Inspeção de Cobrança" 
    position="end" 
    size="xl"
    :tabs="['Resumo Geral', 'Pagamento', 'Gateway Adquirente', 'Ledger', 'Conciliação', 'Timeline', 'Metadata']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Carregando dados da cobrança...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
    
    <x-slot name="footerActions">
        <div class="w-100 d-flex justify-content-between">
            <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeChargeDrawer()">Fechar</button>
            <div class="d-flex gap-2">
                <button class="btn btn-warning fw-semibold shadow-sm text-dark"><i class="fa-solid fa-rotate me-2"></i> Reprocessar Webhook</button>
                <button class="btn btn-danger fw-semibold shadow-sm text-white"><i class="fa-solid fa-ban me-2"></i> Cancelar</button>
            </div>
        </div>
    </x-slot>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const chargesData = @json($charges->keyBy('id'));
    
    function openChargeDrawer(id) {
        const drawerEl = document.getElementById('chargeDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        const tx = chargesData[id];
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');
            
            document.getElementById('drawerContent').innerHTML = 
                <!-- TAB 1: Resumo -->
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="chargeDetailDrawer-tab-resumo-geral" role="tabpanel">
                    <div class="card border border-secondary-subtle shadow-sm rounded-4 bg-body-tertiary mb-4">
                        <div class="card-body p-4 text-center">
                            <div class="text-body-secondary text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Valor Bruto Arrecadado</div>
                            <h2 class="fw-bold mb-0 text-success">+R$ +tx.amount+</h2>
                            <div class="mt-2">
                                <span class="badge bg-secondary-subtle text-secondary">Taxa Plataforma: R$ +(tx.platform_fee || 0)+</span>
                                <span class="badge bg-success-subtle text-success">Líquido Creditado: R$ +(tx.net_amount || tx.amount)+</span>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="text-body-secondary fw-semibold text-uppercase mb-3" style="font-size: 0.75rem;">Dados do Cliente (Pagador)</h6>
                    <div class="card border border-secondary-subtle shadow-sm mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">NOME DO PAGADOR</div>
                                    <div class="fw-bold">+(tx.customer_name || 'N/A')+</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">DOCUMENTO</div>
                                    <div class="fw-bold font-monospace">+(tx.customer_document || 'N/A')+</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">EMAIL</div>
                                    <div class="fw-bold">+(tx.customer_email || 'N/A')+</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder Tabs -->
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="chargeDetailDrawer-tab-pagamento" role="tabpanel">
                    <div class="alert alert-info border-info-subtle">
                        QRCode PIX / Link de Boleto / Dados do Cartão.
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="chargeDetailDrawer-tab-gateway-adquirente" role="tabpanel">
                    <div class="alert alert-success border-success-subtle">
                        Gateway Adquirente: <strong>+(tx.gateway ? tx.gateway.name : 'A Definir')+</strong><br>
                        Ref do Gateway: +(tx.gateway_charge_id || 'N/A')+
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="chargeDetailDrawer-tab-ledger" role="tabpanel">
                    <div class="alert alert-warning border-warning-subtle">
                        Esta cobrança gerou impacto na Wallet do Merchant (via Ledger). 
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto" id="chargeDetailDrawer-tab-timeline" role="tabpanel">
                    <div class="text-center py-5 text-body-secondary">Eventos (Criado -> Aguardando Webhook -> Pago).</div>
                </div>
            ;
        }, 300);
    }
    
    function closeChargeDrawer() {
        const drawerEl = document.getElementById('chargeDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
</script>
@endpush
