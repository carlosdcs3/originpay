@extends('backend.layouts.app')
@section('title', 'Central Operacional de Webhooks')

@section('content')
<x-admin.page-hero 
    title="Central Operacional de Webhooks" 
    description="Motor passivo de recebimento de notificações dos gateways (Inbox Pattern)."
    status="Produção"
    statusColor="success"
    :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Financeiro' => null,
        'Webhooks' => null
    ]"
/>

<x-admin.kpi-grid>
    <x-admin.kpi-card title="Recebidos" value="{{ $kpis['received'] }}" icon="fa-solid fa-inbox" color="primary" />
    <x-admin.kpi-card title="Processados" value="{{ $kpis['processed'] }}" icon="fa-solid fa-check-double" color="success" />
    <x-admin.kpi-card title="Falhas" value="{{ $kpis['failed'] }}" icon="fa-solid fa-triangle-exclamation" color="warning" />
    <x-admin.kpi-card title="Dead Letter (DLQ)" value="{{ $kpis['dead_letter'] }}" icon="fa-solid fa-skull-crossbones" color="danger" />
</x-admin.kpi-grid>

<x-admin.data-table 
    :headers="['Correlation / Gateway', 'Evento', 'Status', 'Tentativas', 'Data', '']"
    :paginator="$events"
    emptyStateTitle="Nenhum webhook recebido"
    emptyStateDesc="Verifique se os endpoints dos PSPs estão configurados." >
    
    @foreach($events as $evt)
        <tr style="cursor: pointer;" onclick="openWebhookDrawer({{ $evt->id }})" class="table-row-hover">
            <td>
                <div class="fw-semibold text-body-emphasis" style="font-size: 0.85rem;">{{ Str::limit($evt->correlation_id, 8, '') }}</div>
                <div class="text-body-secondary text-uppercase" style="font-size: 0.75rem;">{{ $evt->gateway }} - {{ $evt->payload_version }}</div>
            </td>
            <td>
                <span class="badge bg-body-tertiary border border-secondary-subtle text-body-emphasis px-2 py-1 rounded-pill">
                    {{ $evt->event_type ?? 'N/A' }}
                </span>
            </td>
            <td>
                @php
                    $colors = [
                        'received' => 'secondary',
                        'processing' => 'info',
                        'processed' => 'success',
                        'failed' => 'warning',
                        'dead_letter' => 'danger'
                    ];
                    $statusName = $evt->status->value ?? $evt->status; // support Enum or string fallback
                    $color = $colors[$statusName] ?? 'primary';
                @endphp
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">{{ $statusName }}</span>
            </td>
            <td>{{ $evt->attempts }}</td>
            <td><div class="text-body-secondary" style="font-size: 0.85rem;">{{ $evt->created_at->format('d/m/Y H:i') }}</div></td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle rounded-3" onclick="event.stopPropagation(); openWebhookDrawer({{ $evt->id }})">
                    <i class="fa-solid fa-chevron-right text-body-secondary"></i>
                </button>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>

<!-- Drawer -->
<x-admin.drawer id="webhookDrawer" title="Inspeção de Evento" position="end" size="xl"
    :tabs="['Resumo', 'Payload', 'Logs de Erro', 'Reprocessamento']">
    <div id="drawerContent" class="d-none h-100 flex-column"></div>
</x-admin.drawer>

@endsection

@push('script')
<script>
    const eventsData = @json($events->keyBy('id'));
    
    function openWebhookDrawer(id) {
        const drawerEl = document.getElementById('webhookDrawer');
        const drawer = new coreui.Offcanvas(drawerEl);
        drawer.show();
        
        document.getElementById('drawerContent').classList.remove('d-none');
        
        const ev = eventsData[id];
        
        document.getElementById('drawerContent').innerHTML = 
            <div class="tab-pane fade show active p-4 h-100 overflow-auto" id="webhookDrawer-tab-resumo" role="tabpanel">
                <h4>Correlation ID</h4>
                <code>+ev.correlation_id+</code>
                <hr>
                <h5>Erro Registrado (se houver)</h5>
                <p class="text-danger">+(ev.error_message || 'Nenhum erro reportado.')+</p>
            </div>
            <div class="tab-pane fade p-4 h-100 overflow-auto bg-body-tertiary" id="webhookDrawer-tab-payload" role="tabpanel">
                <pre><code>+JSON.stringify(ev.raw_payload, null, 2)+</code></pre>
            </div>
            <!-- Outras abas podem ser implementadas via backend call para pegar logs do Elastic/Sentry -->
        ;
    }
</script>
@endpush
