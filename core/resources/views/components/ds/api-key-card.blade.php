@props(['title', 'env' => 'test', 'status' => 'active', 'keyPrefix' => 'sk_test_1234...', 'createdAt', 'lastUsed' => 'Nunca', 'rotatedAt' => null])

@php
    $envClass = $env === 'production' ? 'bg-danger text-white' : 'bg-secondary text-white';
    $envLabel = $env === 'production' ? 'PRODUÇÃO' : 'SANDBOX';
    $statusClass = $status === 'active' ? 'text-success' : 'text-danger';
    $statusIcon = $status === 'active' ? 'check-circle' : 'times-circle';
    $statusLabel = $status === 'active' ? 'Ativa' : 'Revogada';
@endphp

<div class="card border border-light shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="fw-bold mb-0">{{ $title }}</h5>
                    <span class="badge {{ $envClass }}" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ $envLabel }}</span>
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size: var(--ds-text-sm);">
                    <span class="{{ $statusClass }} fw-semibold d-flex align-items-center"><i class="la la-{{ $statusIcon }} me-1"></i> {{ $statusLabel }}</span>
                    <span class="text-muted">•</span>
                    <span class="text-muted">Criada em {{ $createdAt }}</span>
                </div>
            </div>

            <span class="text-muted" style="font-size: var(--ds-text-xs);">Gerencie esta chave pelo painel de integrações.</span>
        </div>

        <div class="bg-light rounded-2 p-3 mb-4 d-flex justify-content-between align-items-center border">
            <div class="font-monospace text-dark fs-6">{{ $keyPrefix }}</div>
            <span class="text-muted" style="font-size: var(--ds-text-xs);">Prefixo visível para conferência.</span>
        </div>

        <div class="row text-muted" style="font-size: var(--ds-text-xs);">
            <div class="col-6">
                <strong>Último uso:</strong> <br>
                {{ $lastUsed }}
            </div>
            <div class="col-6 text-end">
                <strong>Última rotação:</strong> <br>
                {{ $rotatedAt ?? 'Nunca rotacionada' }}
            </div>
        </div>
    </div>
</div>
