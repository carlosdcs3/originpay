@props(['url', 'status' => 'active', 'events' => [], 'lastDelivery' => 'Nunca'])

@php
    $statusClass = $status === 'active' ? 'text-success' : 'text-danger';
    $statusIcon = $status === 'active' ? 'check-circle' : 'times-circle';
    $statusLabel = $status === 'active' ? 'Ativo' : 'Desativado';
@endphp

<div class="card border shadow-sm rounded-3 mb-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h6 class="fw-bold mb-0" style="font-family: var(--ds-font-mono);">{{ $url }}</h6>
                    <span class="{{ $statusClass }} fw-semibold d-flex align-items-center" style="font-size: 0.75rem;"><i class="la la-{{ $statusIcon }} me-1"></i> {{ $statusLabel }}</span>
                </div>
                <div class="d-flex gap-1 mb-2">
                    @foreach($events as $event)
                        <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">{{ $event }}</span>
                    @endforeach
                </div>
                <div class="text-muted" style="font-size: var(--ds-text-xs);">
                    Última entrega: {{ $lastDelivery }}
                </div>
            </div>
            <div>
                <button class="btn btn-sm btn-light border" onclick="alert('Ação ainda não disponível')">Editar</button>
                <button class="btn btn-sm btn-outline-primary" onclick="alert('Simulação de envio em breve')">Testar</button>
            </div>
        </div>
    </div>
</div>
