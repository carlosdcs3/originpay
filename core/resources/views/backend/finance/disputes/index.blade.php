@extends('backend.finance.index')
@section('finance_title', $pageTitle)
@section('finance_desc', 'Gerenciamento de MED Pix, Chargebacks e contestações.')

@section('finance_content')
    <!-- Top KPIs Bento Grid -->
    <div class="ds-bento mb-4">
        <div class="ds-col-span-12 ds-col-lg-span-3">
            <div class="ds-hero-card">
                <div class="ds-hero-label">Disputas Abertas</div>
                <div class="ds-hero-value">{{ $kpis['open_count'] ?? 0 }}</div>
                <div class="ds-hero-trend">
                    <i class="la la-exclamation-circle text-warning"></i> Casos em andamento
                </div>
            </div>
        </div>
        
        <div class="ds-col-span-12 ds-col-lg-span-3">
            <div class="ds-hero-card">
                <div class="ds-hero-label">Valor Retido</div>
                <div class="ds-hero-value" style="color: var(--ds-danger);">R$ {{ number_format(($kpis['retained_cents'] ?? 0) / 100, 2, ',', '.') }}</div>
                <div class="ds-hero-trend">
                    <i class="la la-lock text-danger"></i> Saldo bloqueado
                </div>
            </div>
        </div>

        <div class="ds-col-span-12 ds-col-lg-span-6">
            <div class="ds-secondary-kpi-grid">
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">Aguardando Lojista</div>
                    <div class="ds-compact-kpi-value text-warning">{{ $kpis['waiting_merchant_count'] ?? 0 }}</div>
                </div>
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">Disputas Ganhas</div>
                    <div class="ds-compact-kpi-value text-success">{{ $kpis['won_count'] ?? 0 }}</div>
                </div>
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">Disputas Perdidas</div>
                    <div class="ds-compact-kpi-value text-danger">{{ $kpis['lost_count'] ?? 0 }}</div>
                </div>
                <div class="ds-compact-kpi">
                    <div class="ds-compact-kpi-label">SLA Médio</div>
                    <div class="ds-compact-kpi-value">{{ $kpis['avg_response_hours'] ?? 0 }}h</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Casos -->
    <x-ds.card title="Lista de Casos" padding="0">
        <div class="table-responsive m-0">
            <table class="ds-table m-0">
                <thead>
                    <tr>
                        <th>ID Disputa</th>
                        <th>Tipo</th>
                        <th>Lojista</th>
                        <th>Valor Retido</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th>Prazo</th>
                        <th class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disputes as $dispute)
                        <tr>
                            <td><span class="ds-mono text-muted">{{ substr($dispute->uuid, 0, 8) }}</span></td>
                            <td><span class="badge ds-badge-secondary">{{ $dispute->type->label() }}</span></td>
                            <td class="fw-medium">{{ $dispute->merchant->fullname ?? $dispute->merchant->name ?? '—' }}</td>
                            <td class="fw-semibold" style="font-variant-numeric: tabular-nums;">R$ {{ $dispute->formatted_retained_amount }}</td>
                            <td>{{ $dispute->gateway ?? '—' }}</td>
                            <td>
                                <span class="badge ds-badge-{{ $dispute->status->color() }}">{{ $dispute->status->label() }}</span>
                            </td>
                            <td>
                                @if($dispute->due_at)
                                    <span class="text-{{ $dispute->due_at->isPast() ? 'danger' : 'muted' }} ds-text-xs">
                                        {{ $dispute->due_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.finance.chargebacks.show', $dispute->uuid) }}" class="btn btn-sm btn-outline-primary">Ver Caso</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-4">
                                <x-ds.empty-state title="Nenhuma disputa encontrada" desc="Ainda não há disputas registradas no sistema." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($disputes->hasPages())
            <div class="p-3 border-top">
                {{ $disputes->links() }}
            </div>
        @endif
    </x-ds.card>
@endsection
