@extends('backend.layouts.app')
@section('title', 'Saques (Withdrawals)')

@section('content')
<x-admin.page-hero 
    title="Dashboard de Saques" 
    description="Fila de saídas (Cash Out). Monitore os volumes solicitados, compatibilidade do gateway e SLA de aprovação."
    status="Operacional"
    statusColor="success"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Saques' => null
    ]"
/>

<x-admin.alerts-area :alerts="$alerts" />

<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Volume Solicitado" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['volume_requested'], 2, ',', '.') }}" 
        icon="fa-solid fa-money-bill-transfer" 
        color="warning" 
        subtitle="Na fila (Pend/Proc)"
    />
    <x-admin.kpi-card 
        title="Pendentes" 
        value="{{ $kpis['pending'] }}" 
        icon="fa-solid fa-hourglass-half" 
        color="secondary" 
        subtitle="Aguardando ação"
    />
    <x-admin.kpi-card 
        title="Aprovados Hoje" 
        value="{{ $kpis['approved_today'] }}" 
        icon="fa-solid fa-check-double" 
        color="success" 
        subtitle="Finalizados no dia"
    />
    <x-admin.kpi-card 
        title="Bloqueados / Rejeitados" 
        value="{{ $kpis['blocked'] + $kpis['rejected'] }}" 
        icon="fa-solid fa-ban" 
        color="danger" 
        subtitle="Tentativas barradas"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.withdrawals') }}">
    <!-- Filtros avançados para Saques -->
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['Transação', 'Usuário/Valor', 'Gateway Executor', 'Data', 'Status', '']"
    :paginator="$withdrawals"
    emptyStateTitle="Fila Limpa"
    emptyStateDesc="Não há solicitações de saque correspondentes aos filtros.">
    
    @foreach($withdrawals as $req)
        <tr style="cursor: pointer;" onclick="openWithdrawalDrawer({{ $req->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold text-body-emphasis font-monospace" style="font-size: 0.85rem;">{{ substr($req->transaction_id, 0, 12) }}...</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">ID: {{ $req->id }}</div>
            </td>
            <td>
                <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">{{ $req->user->first_name ?? 'N/A' }}</div>
                <div class="fw-bold text-danger" style="font-size: 0.85rem;">-{{ siteCurrency('symbol') }}{{ number_format($req->amount, 2, ',', '.') }}</div>
            </td>
            <td>
                <span class="badge bg-body-tertiary border border-secondary-subtle text-body-emphasis px-2 py-1 rounded-pill text-uppercase">{{ $req->provider ?? 'A Definir' }}</span>
                <div class="mt-1 d-flex gap-1">
                    <span class="badge bg-success-subtle text-success" style="font-size: 0.6rem;">PIX_WITHDRAW OK</span>
                </div>
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $req->created_at->format('d/m/Y') }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $req->created_at->format('H:i') }}</div>
            </td>
            <td>
                @php
                    $statusColors = [
                        'pending' => 'secondary',
                        'processing' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'blocked' => 'dark'
                    ];
                    $color = $statusColors[$req->status] ?? 'primary';
                @endphp
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $req->status }}</span>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openWithdrawalDrawer({{ $req->id }})">
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
    id="withdrawDetailDrawer" 
    title="Inspeção de Saque" 
    position="end" 
    size="xl"
    :tabs="['Resumo Geral', 'Origem do Saldo', 'Gateway Executor', 'Auditoria e Aprovações']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Carregando dados estruturais...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
    
    <x-slot name="footerActions">
        <div class="w-100 d-flex justify-content-between">
            <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeWithdrawalDrawer()">Fechar</button>
            <div class="d-flex gap-2">
                <button class="btn btn-danger fw-semibold shadow-sm text-white"><i class="fa-solid fa-ban me-2"></i> Rejeitar</button>
                <button class="btn btn-success fw-semibold shadow-sm text-white"><i class="fa-solid fa-check me-2"></i> Aprovar Saque (PIX)</button>
            </div>
        </div>
    </x-slot>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const withdrawalsData = @json($withdrawals->keyBy('id'));
    
    function openWithdrawalDrawer(id) {
        const drawerEl = document.getElementById('withdrawDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        const tx = withdrawalsData[id];
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');
            
            document.getElementById('drawerContent').innerHTML = 
                <!-- TAB 1: Resumo -->
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="withdrawDetailDrawer-tab-resumo-geral" role="tabpanel">
                    <div class="card border border-secondary-subtle shadow-sm rounded-4 bg-body-tertiary mb-4">
                        <div class="card-body p-4 text-center">
                            <div class="text-body-secondary text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Valor Solicitado</div>
                            <h2 class="fw-bold mb-0 text-danger">-R$ +tx.amount+</h2>
                            <div class="mt-2">
                                <span class="badge bg-secondary-subtle text-secondary">Taxa: R$ +(tx.fee_amount || 0)+</span>
                                <span class="badge bg-success-subtle text-success">Líquido: R$ +(tx.net_amount || tx.amount)+</span>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="text-body-secondary fw-semibold text-uppercase mb-3" style="font-size: 0.75rem;">Destino (Chave PIX)</h6>
                    <div class="card border border-secondary-subtle shadow-sm mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">TIPO DE CHAVE</div>
                                    <div class="fw-bold">+(tx.pix_key_type || 'CPF/CNPJ')+</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">CHAVE</div>
                                    <div class="fw-bold font-monospace">+(tx.pix_key_snapshot || 'N/A')+</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-body-secondary" style="font-size: 0.75rem;">TITULAR (RECEBEDOR)</div>
                                    <div class="fw-bold">+(tx.pix_owner_name || tx.user.first_name)+</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder Tabs -->
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="withdrawDetailDrawer-tab-origem-do-saldo" role="tabpanel">
                    <div class="alert alert-info border-info-subtle">
                        Visualização de quais gateways sofrerão o débito real para honrar este saque.
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="withdrawDetailDrawer-tab-gateway-executor" role="tabpanel">
                    <div class="alert alert-success border-success-subtle">
                        Gateway Alocado: <strong>+(tx.provider || 'A Definir')+</strong><br>
                        Status de Saúde: Operacional<br>
                        Saldo no Provedor: Suficiente
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto" id="withdrawDetailDrawer-tab-auditoria-e-aprovacoes" role="tabpanel">
                    <div class="text-center py-5 text-body-secondary">Histórico de aprovação ou rejeição e motivos.</div>
                </div>
            ;
        }, 300);
    }
    
    function closeWithdrawalDrawer() {
        const drawerEl = document.getElementById('withdrawDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
</script>
@endpush
