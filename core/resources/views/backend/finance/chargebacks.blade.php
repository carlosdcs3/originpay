@extends('backend.layouts.app')
@section('title', 'Chargebacks & Disputas')

@section('content')
<x-admin.page-hero 
    title="Dashboard de Chargebacks" 
    description="Motor de resolução de disputas. Rastreie contestações de cartões, avalie o risco da carteira e gerencie retenções de saldo impostas aos Merchants."
    status="Defensivo"
    statusColor="danger"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Chargebacks' => null
    ]"
    :quickStats="[
        ['label' => 'Abertos', 'value' => $kpis['open']],
        ['label' => 'Taxa de CBK', 'value' => $kpis['cbk_rate']]
    ]"
/>

<x-admin.alerts-area :alerts="$alerts" />

<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Valor em Risco" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['value_at_risk'], 2, ',', '.') }}" 
        icon="fa-solid fa-triangle-exclamation" 
        color="danger" 
        subtitle="Disputas em andamento"
    />
    <x-admin.kpi-card 
        title="Disputas Abertas" 
        value="{{ $kpis['disputed'] }}" 
        icon="fa-solid fa-gavel" 
        color="warning" 
        subtitle="Aguardando defesa"
    />
    <x-admin.kpi-card 
        title="Casos Perdidos" 
        value="{{ $kpis['lost'] }}" 
        icon="fa-solid fa-scale-unbalanced" 
        color="dark" 
        subtitle="Prejuízo consolidado"
    />
    <x-admin.kpi-card 
        title="Bloqueio Aplicado" 
        value="{{ siteCurrency('symbol') }} {{ number_format($kpis['blocked_value'], 2, ',', '.') }}" 
        icon="fa-solid fa-lock" 
        color="info" 
        subtitle="Saldo retido preventivamente"
    />
</x-admin.kpi-grid>

<x-admin.smart-filter action="{{ route('admin.finance.chargebacks') }}">
    <!-- Filtros avançados para Chargebacks -->
</x-admin.smart-filter>

<x-admin.data-table 
    :headers="['Merchant', 'Ref Original (Adquirente)', 'Gateway', 'Motivo / Prazo', 'Valor (Risco)', 'Status', '']"
    :paginator="$chargebacks"
    emptyStateTitle="Nenhuma disputa"
    emptyStateDesc="Não existem chargebacks abertos ou correspondentes aos filtros.">
    
    @foreach($chargebacks as $cbk)
        <tr style="cursor: pointer;" onclick="openChargebackDrawer({{ $cbk->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">{{ $cbk->user->first_name ?? 'N/A' }} {{ $cbk->user->last_name ?? '' }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">UID: {{ $cbk->user_id }}</div>
            </td>
            <td>
                <div class="fw-semibold text-body-emphasis font-monospace" style="font-size: 0.85rem;">{{ $cbk->provider_reference }}</div>
                <div class="text-body-secondary" style="font-size: 0.75rem;">TID: {{ $cbk->charge_id }}</div>
            </td>
            <td>
                <span class="badge bg-body-tertiary border border-secondary-subtle text-body-emphasis px-2 py-1 rounded-pill text-uppercase">
                    {{ $cbk->gateway->name ?? 'N/A' }}
                </span>
            </td>
            <td>
                <div class="text-body-emphasis" style="font-size: 0.85rem;">{{ $cbk->reason }}</div>
                <div class="text-danger fw-semibold" style="font-size: 0.75rem;">
                    @if($cbk->deadline)
                        Prazo: {{ $cbk->deadline->format('d/m/Y') }}
                    @else
                        N/A
                    @endif
                </div>
            </td>
            <td>
                <div class="fw-bold text-danger" style="font-size: 0.85rem;">{{ siteCurrency('symbol') }}{{ number_format($cbk->amount, 2, ',', '.') }}</div>
            </td>
            <td>
                @php
                    $statusColors = [
                        'open' => 'danger',
                        'disputed' => 'warning',
                        'won' => 'success',
                        'lost' => 'dark',
                        'expired' => 'secondary'
                    ];
                    $color = $statusColors[$cbk->status] ?? 'primary';
                @endphp
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $cbk->status }}</span>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openChargebackDrawer({{ $cbk->id }})">
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
    id="chargebackDetailDrawer" 
    title="Inspeção de Disputa (CBK)" 
    position="end" 
    size="xl"
    :tabs="['Resumo Geral', 'Cobrança Original', 'Gateway', 'Wallet / Bloqueio', 'Disputa', 'Ledger', 'Timeline', 'Metadata']"
>
    <div id="drawerLoading" class="p-5 text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3 text-body-secondary">Mapeando risco na base de Custódia...</div>
    </div>
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
    
    <x-slot name="footerActions">
        <div class="w-100 d-flex justify-content-between">
            <button class="btn btn-light fw-semibold text-body-secondary border border-secondary-subtle shadow-sm" onclick="closeChargebackDrawer()">Fechar</button>
            <div class="d-flex gap-2">
                <button class="btn btn-primary fw-semibold shadow-sm text-white"><i class="fa-solid fa-shield-halved me-2"></i> Enviar Defesa (Evidências)</button>
                <button class="btn btn-danger fw-semibold shadow-sm text-white"><i class="fa-solid fa-flag-checkered me-2"></i> Aceitar Perda (Debit Wallet)</button>
            </div>
        </div>
    </x-slot>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const cbkData = @json($chargebacks->keyBy('id'));
    
    function openChargebackDrawer(id) {
        const drawerEl = document.getElementById('chargebackDetailDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerLoading').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');
        
        const tx = cbkData[id];
        
        setTimeout(() => {
            document.getElementById('drawerLoading').classList.add('d-none');
            document.getElementById('drawerContent').classList.remove('d-none');
            document.getElementById('drawerContent').classList.add('d-flex');
            
            document.getElementById('drawerContent').innerHTML = 
                <!-- TAB 1: Resumo -->
                <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="chargebackDetailDrawer-tab-resumo-geral" role="tabpanel">
                    <div class="card border border-danger-subtle shadow-sm rounded-4 bg-danger-subtle mb-4">
                        <div class="card-body p-4 text-center">
                            <div class="text-danger text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Prejuízo Potencial</div>
                            <h2 class="fw-bold mb-0 text-danger">R$ +tx.amount+</h2>
                            <div class="mt-2 text-danger">
                                Motivo: <strong>+tx.reason+</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning border-warning-subtle text-dark d-flex align-items-start gap-3 mb-4">
                        <i class="fa-solid fa-lock fs-4 mt-1"></i>
                        <div>
                            <strong>Bloqueio Cautelar Ativo</strong><br>
                            <small>Este valor foi imediatamente congelado na Wallet do Merchant quando a notificação de chargeback chegou, impedindo o saque do dinheiro em risco.</small>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder Tabs -->
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="chargebackDetailDrawer-tab-cobranca-original" role="tabpanel">
                    <div class="text-center py-5">Detalhes da transação +(tx.charge_id || 'N/A')+ (Data, Cliente, IP, Validação Anti-fraude).</div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="chargebackDetailDrawer-tab-wallet---bloqueio" role="tabpanel">
                    <div class="alert alert-info border-info-subtle">
                        Visualização direta da \WalletBalance\ afetada (Blocked Balance).
                    </div>
                </div>
                <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="chargebackDetailDrawer-tab-ledger" role="tabpanel">
                    <div class="text-center py-5">Ação registrada no Ledger confirmando o bloqueio provisório.</div>
                </div>
            ;
        }, 300);
    }
    
    function closeChargebackDrawer() {
        const drawerEl = document.getElementById('chargebackDetailDrawer');
        const drawer = coreui.Offcanvas.getInstance(drawerEl);
        if(drawer) drawer.hide();
    }
</script>
@endpush
