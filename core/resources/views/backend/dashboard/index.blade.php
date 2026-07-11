@extends('backend.layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $tpvDaily = $kpis['tpv_daily'] ?? 0;
    $tpvMonthly = $kpis['tpv_monthly'] ?? 0;
    $netRevenueMonthly = $kpis['net_revenue_monthly'] ?? 0;
    $chargesPaidToday = $kpis['charges_paid_today'] ?? 0;
    $chargesCreatedToday = $kpis['charges_created_today'] ?? 0;
    $pendingWithdrawals = $pendingItems['withdrawals'] ?? 0;
    $pendingKyc = $pendingItems['kyc'] ?? 0;
    $activeGateways = $kpis['active_gateways'] ?? 0;
@endphp

<x-ds.page
    title="Visão Geral"
    desc="Saúde operacional e financeira da OriginPay em um único lugar."
    :breadcrumb="[
        ['title' => 'Dashboard']
    ]">

    <x-slot name="actions">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.gateway.charges.index') }}" class="btn btn-primary btn-sm">Cobranças</a>
            <a href="{{ route('admin.gateway.withdrawals.index') }}" class="btn btn-outline-primary btn-sm">Saques</a>
            <a href="{{ route('admin.payment.gateway.index') }}" class="btn btn-outline-primary btn-sm">Gateways</a>
            <a href="{{ route('admin.alerts.index') }}" class="btn btn-outline-primary btn-sm">Alertas</a>
        </div>
    </x-slot>

    <!-- Top KPIs Bento Grid -->
    <div class="ds-bento mb-4">
        <!-- Hero Metrics -->
        <div class="ds-col-span-12 ds-col-lg-span-4">
            <div class="ds-hero-card">
                <div class="ds-hero-label">Receita Bruta Hoje</div>
                <div class="ds-hero-value">R$ {{ number_format($tpvDaily, 2, ',', '.') }}</div>
                <div class="ds-hero-trend">
                    <i class="la la-arrow-up text-success"></i> Cobranças pagas nas últimas 24h
                </div>
            </div>
        </div>
        
        <div class="ds-col-span-12 ds-col-lg-span-4">
            <div class="ds-hero-card">
                <div class="ds-hero-label">Receita Líquida (Mês)</div>
                <div class="ds-hero-value" style="color: var(--ds-primary);">R$ {{ number_format($netRevenueMonthly, 2, ',', '.') }}</div>
                <div class="ds-hero-trend">
                    <i class="la la-chart-bar text-primary"></i> Taxas arrecadadas da plataforma
                </div>
            </div>
        </div>

        <!-- Secondary KPIs -->
        <div class="ds-col-span-12 ds-col-lg-span-4">
            <div class="ds-secondary-kpi-grid">
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">Volume Mês</div>
                    <div class="ds-compact-kpi-value">R$ {{ number_format($tpvMonthly, 2, ',', '.') }}</div>
                </div>
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">Pagas Hoje</div>
                    <div class="ds-compact-kpi-value">{{ $chargesPaidToday }}</div>
                </div>
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">Criadas Hoje</div>
                    <div class="ds-compact-kpi-value">{{ $chargesCreatedToday }}</div>
                </div>
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">Pendentes (Gap)</div>
                    <div class="ds-compact-kpi-value">{{ max($chargesCreatedToday - $chargesPaidToday, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status & Priorities Grid -->
    <div class="ds-bento mb-4">
        <!-- Status Operacional -->
        <div class="ds-col-span-12 ds-col-lg-span-6">
            <x-ds.card title="Saúde da Operação" class="h-100" padding="0">
                <div class="ds-status-list">
                    <div class="ds-status-item">
                        <div class="ds-status-label">
                            <i class="la la-server fs-5"></i> Status operacional
                        </div>
                        <div class="ds-status-value">
                            @if($platformStatus === 'critical')
                                <span class="text-danger fw-bold"><i class="la la-exclamation-circle"></i> Incidente Crítico</span>
                            @elseif($platformStatus === 'degraded')
                                <span class="text-warning fw-bold"><i class="la la-exclamation-triangle"></i> Degradada</span>
                            @else
                                <span class="text-success fw-bold"><i class="la la-check-circle"></i> Estável</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="ds-status-item">
                        <div class="ds-status-label">
                            <i class="la la-plug fs-5"></i> Gateways ativos
                        </div>
                        <div class="ds-status-value">{{ $activeGateways }} <span class="text-muted ms-1">integrações</span></div>
                    </div>
                    
                    <div class="ds-status-item">
                        <div class="ds-status-label">
                            <i class="la la-balance-scale fs-5"></i> Falhas de reconciliação
                        </div>
                        <div class="ds-status-value">
                            @if($failedReconciliations > 0)
                                <span class="text-danger">{{ $failedReconciliations }} falhas</span>
                            @else
                                <span class="text-success">0 falhas</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="ds-status-item">
                        <div class="ds-status-label">
                            <i class="la la-id-card fs-5"></i> Fila de KYC
                        </div>
                        <div class="ds-status-value">{{ $pendingKyc }} <span class="text-muted ms-1">aguardando</span></div>
                    </div>
                </div>
            </x-ds.card>
        </div>

        <!-- Atenção Necessária -->
        <div class="ds-col-span-12 ds-col-lg-span-6">
            <x-ds.card title="Atenção Necessária" class="h-100" padding="0">
                <div class="ds-priority-list">
                    <a href="{{ route('admin.kyc.index') }}" class="ds-priority-item">
                        <div class="ds-priority-icon">
                            <span class="badge ds-badge-warning p-2 rounded-circle"><i class="la la-id-card fs-6"></i></span>
                        </div>
                        <div class="ds-priority-content">
                            <div class="ds-priority-title">Análises KYC Pendentes</div>
                            <div class="ds-priority-desc">Existem cadastros aguardando aprovação operacional.</div>
                        </div>
                        <div class="ds-priority-action">
                            <span class="badge bg-secondary text-white">{{ $pendingKyc }}</span>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.gateway.withdrawals.index') }}" class="ds-priority-item">
                        <div class="ds-priority-icon">
                            <span class="badge ds-badge-warning p-2 rounded-circle"><i class="la la-wallet fs-6"></i></span>
                        </div>
                        <div class="ds-priority-content">
                            <div class="ds-priority-title">Saques Pendentes</div>
                            <div class="ds-priority-desc">Transações de saque precisando de revisão manual ou falhas de provedor.</div>
                        </div>
                        <div class="ds-priority-action">
                            <span class="badge bg-secondary text-white">{{ $pendingWithdrawals }}</span>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.support-chat.index') }}" class="ds-priority-item">
                        <div class="ds-priority-icon">
                            <span class="badge ds-badge-info p-2 rounded-circle"><i class="la la-comment fs-6"></i></span>
                        </div>
                        <div class="ds-priority-content">
                            <div class="ds-priority-title">Conversas de Suporte</div>
                            <div class="ds-priority-desc">Lojistas aguardando resposta do time de suporte.</div>
                        </div>
                        <div class="ds-priority-action">
                            <span class="badge bg-secondary text-white">{{ $pendingItems['conversations'] ?? 0 }}</span>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.finance.reconciliation') }}" class="ds-priority-item">
                        <div class="ds-priority-icon">
                            <span class="badge ds-badge-danger p-2 rounded-circle"><i class="la la-exchange-alt fs-6"></i></span>
                        </div>
                        <div class="ds-priority-content">
                            <div class="ds-priority-title">Conciliações Divergentes</div>
                            <div class="ds-priority-desc">Anomalias encontradas entre gateway e ledger interno.</div>
                        </div>
                        <div class="ds-priority-action">
                            <span class="badge bg-secondary text-white">{{ $pendingItems['anomalies'] ?? 0 }}</span>
                        </div>
                    </a>
                </div>
            </x-ds.card>
        </div>
    </div>

    <!-- Alertas & Eventos Relevantes -->
    <div class="ds-bento mb-4">
        <div class="ds-col-span-12 ds-col-lg-span-4">
            <x-ds.card title="Alertas" class="h-100" padding="0">
                @if(!empty($alerts))
                    <div class="ds-status-list">
                        @foreach($alerts as $alert)
                            <div class="ds-status-item">
                                <div class="ds-status-label text-warning">
                                    <i class="la la-exclamation-triangle fs-5"></i>
                                    {{ $alert }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4">
                        <x-ds.empty-state title="Sistema Operando" desc="Nenhum alerta crítico ativo no momento." />
                    </div>
                @endif
            </x-ds.card>
        </div>

        <div class="ds-col-span-12 ds-col-lg-span-8">
            <x-ds.card title="Últimos Eventos Relevantes" class="h-100" padding="0">
                <div class="table-responsive m-0">
                    <table class="ds-table m-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Identificador</th>
                                <th>Usuário</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th class="text-end">Quando</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions ?? [] as $trx)
                                @php
                                    $trxType = $trx->trx_type?->value ?? $trx->type ?? 'transacao';
                                    $trxTypeLabel = match($trxType) {
                                        'deposit', 'payment' => 'Cobrança',
                                        'withdraw', 'withdrawal' => 'Saque',
                                        default => 'Transação',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="badge ds-badge-info">{{ $trxTypeLabel }}</span></td>
                                    <td><span class="ds-mono text-muted">{{ substr($trx->trx ?? $trx->id, 0, 16) }}</span></td>
                                    <td class="fw-medium">{{ $trx->user->fullname ?? $trx->user->name ?? '—' }}</td>
                                    <td class="fw-semibold" style="font-variant-numeric: tabular-nums;">R$ {{ number_format($trx->amount ?? 0, 2, ',', '.') }}</td>
                                    <td>
                                        @if($trx->status == \App\Enums\TrxStatus::COMPLETED)
                                            <x-ds.badge status="paid" label="Concluída" />
                                        @elseif((string) $trx->status === '2')
                                            <x-ds.badge status="pending" label="Pendente" />
                                        @else
                                            <x-ds.badge :status="$trx->status" />
                                        @endif
                                    </td>
                                    <td class="text-end text-muted ds-text-xs">
                                        {{ \Carbon\Carbon::parse($trx->created_at)->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4">
                                        <x-ds.empty-state title="Sem atividade recente" desc="Nenhum evento financeiro recente foi encontrado." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ds.card>
        </div>
    </div>

</x-ds.page>
@endsection
