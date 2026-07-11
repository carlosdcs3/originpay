@extends('backend.finance.index')
@section('finance_title', 'Saques')
@section('finance_desc', 'Acompanhamento e análise das solicitações de saque dos usuários.')

@section('finance_content')
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-ds.dev-stat-card title="Total de solicitações" value="{{ number_format($kpiTotal, 0, ',', '.') }}" icon="list" />
        </div>
        <div class="col-md-2">
            <x-ds.dev-stat-card title="Pendentes" value="{{ number_format($kpiPending, 0, ',', '.') }}" icon="time" />
        </div>
        <div class="col-md-2">
            <x-ds.dev-stat-card title="Em análise" value="{{ number_format($kpiAnalysis, 0, ',', '.') }}" icon="search" />
        </div>
        <div class="col-md-2">
            <x-ds.dev-stat-card title="Aprovadas" value="{{ number_format($kpiApproved, 0, ',', '.') }}" icon="check" />
        </div>
        <div class="col-md-3">
            <x-ds.dev-stat-card title="Valor pendente" value="R$ {{ number_format($kpiPendingValue, 2, ',', '.') }}" icon="currency" />
        </div>
    </div>

    <x-ds.table
        title="Lista de saques"
        :count="$withdrawals->total() ?? $withdrawals->count()"
        :isEmpty="$withdrawals->isEmpty()"
        :action="route('admin.gateway.withdrawals.index')">

        <x-slot name="filters">
            <select name="status" class="ds-filter-select form-select" style="width:auto;">
                <option value="">Todos os status</option>
                <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pendente</option>
                <option value="PROCESSING" {{ request('status') == 'PROCESSING' ? 'selected' : '' }}>Em análise</option>
                <option value="SUCCESS" {{ request('status') == 'SUCCESS' ? 'selected' : '' }}>Aprovado</option>
                <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>Rejeitado</option>
                <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Cancelado</option>
            </select>
            <select name="period" class="ds-filter-select form-select" style="width:auto;">
                <option value="">Período</option>
                <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hoje</option>
                <option value="7days" {{ request('period') == '7days' ? 'selected' : '' }}>Últimos 7 dias</option>
                <option value="30days" {{ request('period') == '30days' ? 'selected' : '' }}>Últimos 30 dias</option>
            </select>
            <select name="provider" class="ds-filter-select form-select" style="width:auto;">
                <option value="">Gateway</option>
                @foreach($providers as $provider)
                    <option value="{{ $provider }}" {{ request('provider') == $provider ? 'selected' : '' }}>
                        {{ ucfirst(str_replace(['_', '-'], ' ', $provider)) }}
                    </option>
                @endforeach
            </select>
            <input type="text" name="search" class="form-control" placeholder="Usuário, e-mail ou ID" value="{{ request('search') }}" style="width: 250px;">
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Filtrar
            </button>
            @if(request()->anyFilled(['status', 'period', 'provider', 'search']))
                <a href="{{ route('admin.gateway.withdrawals.index') }}" class="btn btn-ghost" style="color:var(--ds-text-muted);">Limpar</a>
            @endif
        </x-slot>

        <x-slot name="thead">
            <th>Solicitação</th>
            <th>Usuário</th>
            <th>Gateway</th>
            <th>Valor</th>
            <th>Taxa</th>
            <th>Valor líquido</th>
            <th>Status</th>
            <th>Data</th>
            <th class="ds-col-action text-end">Ações</th>
        </x-slot>

        @forelse($withdrawals as $withdrawal)
            @php
                $wStatus = strtolower($withdrawal->status);
            @endphp
            <tr>
                <td>
                    <div style="font-family:var(--ds-font-mono);font-size:var(--ds-text-sm); color:var(--ds-text-muted);">
                        #{{ substr($withdrawal->transaction_id ?? $withdrawal->id, 0, 8) }}
                    </div>
                </td>
                <td>
                    <a href="{{ $withdrawal->user ? route('admin.user.manage', $withdrawal->user->username) : '#' }}"
                       style="color:var(--ds-accent);font-weight:500;font-size:var(--ds-text-sm);text-decoration:none;">
                        {{ $withdrawal->user->username ?? 'N/A' }}
                    </a>
                </td>
                <td>
                    <span style="font-size:var(--ds-text-sm);color:var(--ds-text);">
                        {{ $withdrawal->provider ? ucfirst(str_replace(['_', '-'], ' ', $withdrawal->provider)) : 'Local' }}
                    </span>
                </td>
                <td><div class="ds-col-money">R$ {{ number_format($withdrawal->amount, 2, ',', '.') }}</div></td>
                <td><div class="ds-col-money text-danger">R$ {{ number_format($withdrawal->fee_amount, 2, ',', '.') }}</div></td>
                <td><div class="ds-col-money fw-bold">R$ {{ number_format($withdrawal->net_amount, 2, ',', '.') }}</div></td>
                <td>
                    @if($wStatus == 'success')
                        <x-ds.badge status="paid" label="Aprovado" />
                    @elseif($wStatus == 'pending')
                        <x-ds.badge status="pending" label="Pendente" />
                    @elseif($wStatus == 'processing')
                        <x-ds.badge status="pending" label="Em análise" />
                    @elseif($wStatus == 'rejected')
                        <x-ds.badge status="cancelled" label="Rejeitado" />
                    @else
                        <x-ds.badge status="cancelled" :label="$withdrawal->status" />
                    @endif
                </td>
                <td><div class="ds-col-date">{{ $withdrawal->created_at->format('d/m/y H:i') }}</div></td>
                <td class="ds-col-action text-end">
                    <a href="{{ route('admin.gateway.withdrawals.show', $withdrawal->id) }}"
                       class="btn btn-sm {{ in_array($wStatus, ['pending', 'processing']) ? 'btn-outline-primary' : 'btn-ghost' }}"
                       title="Ver detalhes">
                        {{ in_array($wStatus, ['pending', 'processing']) ? 'Analisar' : 'Detalhes' }}
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9">
                    <x-ds.empty-state
                        title="Nenhum saque encontrado"
                        desc="As solicitações de saque aparecerão aqui automaticamente quando forem realizadas."
                        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                    />
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <x-ds.pagination :paginator="$withdrawals" />
        </x-slot>

    </x-ds.table>
@endsection
