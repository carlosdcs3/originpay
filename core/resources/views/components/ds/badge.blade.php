@props([
    'status' => null, 
    'label' => null,
])

@php
    $statusMap = [
        // Cobranças
        'paid' => ['class' => 'ds-badge-success', 'dot' => 'paid', 'default_label' => 'Pago'],
        'pending' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Pendente'],
        'cancelled' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Cancelado'],
        'expired' => ['class' => 'ds-badge-neutral', 'dot' => null, 'default_label' => 'Expirado'],
        'refunded' => ['class' => 'ds-badge-neutral', 'dot' => null, 'default_label' => 'Reembolsado'],

        // Usuários
        'active' => ['class' => 'ds-badge-success', 'dot' => null, 'default_label' => 'Ativo'],
        'inactive' => ['class' => 'ds-badge-neutral', 'dot' => null, 'default_label' => 'Inativo'],
        'suspended' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Suspenso'],
        'blocked' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Bloqueado'],
        // Legacy Usuários
        '1' => ['class' => 'ds-badge-success', 'dot' => null, 'default_label' => 'Ativo'],
        '0' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Bloqueado'],

        // KYC
        'kyc_pending' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Pendente'],
        'approved' => ['class' => 'ds-badge-success', 'dot' => null, 'default_label' => 'Aprovado'],
        'rejected' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Rejeitado'],
        // Legacy KYC
        '2' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Pendente'],

        // Gateways
        'online' => ['class' => 'ds-badge-success', 'dot' => 'online', 'default_label' => 'Online'],
        'offline' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Offline'],
        'degraded' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Degradado'],
        'open' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Open (Failing)'],
        'half_open' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Half-Open'],
        'closed' => ['class' => 'ds-badge-success', 'dot' => null, 'default_label' => 'Closed (Healthy)'],

        // Saques
        'requested' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Solicitado'],
        'processing' => ['class' => 'ds-badge-warning', 'dot' => null, 'default_label' => 'Processando'],
        'completed' => ['class' => 'ds-badge-success', 'dot' => null, 'default_label' => 'Concluído'],
        'failed' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Falhou'],

        // General
        'success' => ['class' => 'ds-badge-success', 'dot' => null, 'default_label' => 'Sucesso'],
        'error' => ['class' => 'ds-badge-danger', 'dot' => null, 'default_label' => 'Erro'],
    ];

    $mapped = $statusMap[strtolower((string)$status)] ?? ['class' => 'ds-badge-neutral', 'dot' => null, 'default_label' => $status];
    $displayLabel = $label ?? $mapped['default_label'];
@endphp

<span class="badge {{ $mapped['class'] }}" {{ $attributes }}>
    @if($mapped['dot'])
        <span class="ds-status-dot {{ $mapped['dot'] }}"></span>
    @endif
    {{ $displayLabel }}
</span>
