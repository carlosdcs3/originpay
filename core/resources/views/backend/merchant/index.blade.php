@extends('backend.layouts.app')
@section('title', $title)

@section('content')
<x-ds.page
    :title="$title"
    :breadcrumb="[
        ['title' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['title' => 'Clientes'],
        ['title' => 'Lojistas']
    ]">

    @php
        try {
            $totalMerchants = \App\Models\Merchant::count();
            $pendingMerchants = \App\Models\Merchant::where('status', 'pending')->orWhere('status', 0)->count();
            $approvedMerchants = \App\Models\Merchant::where('status', 'approved')->orWhere('status', 1)->count();
            $rejectedMerchants = \App\Models\Merchant::where('status', 'rejected')->orWhere('status', 2)->count();
        } catch(\Exception $e) {
            $totalMerchants = $pendingMerchants = $approvedMerchants = $rejectedMerchants = '—';
        }
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Total" :value="$totalMerchants" icon="store" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Em análise" :value="$pendingMerchants" icon="clock" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Aprovados" :value="$approvedMerchants" icon="check-circle" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Rejeitados" :value="$rejectedMerchants" icon="ban" />
        </div>
    </div>

    <div class="ds-tabs mb-4">
        <a class="ds-tab-btn {{ request('status', 'all') === 'all' ? 'active' : '' }}" href="{{ route('admin.merchant.index') }}">
            Todos os lojistas
        </a>
        <a class="ds-tab-btn {{ request('status') === 'pending' ? 'active' : '' }}" href="{{ route('admin.merchant.index', ['status' => 'pending']) }}">
            Pendentes
        </a>
        <a class="ds-tab-btn {{ request('status') === 'approved' ? 'active' : '' }}" href="{{ route('admin.merchant.index', ['status' => 'approved']) }}">
            Aprovados
        </a>
        <a class="ds-tab-btn {{ request('status') === 'rejected' ? 'active' : '' }}" href="{{ route('admin.merchant.index', ['status' => 'rejected']) }}">
            Rejeitados
        </a>
    </div>

    <x-ds.table
        title="Lista de lojistas"
        :count="$merchants->total() ?? $merchants->count()"
        :isEmpty="$merchants->isEmpty()"
        :action="url()->current()">

        <x-slot name="filters">
            @include('backend.merchant.partials._filter')
        </x-slot>

        <x-slot name="thead">
            <th>Lojista</th>
            <th>Responsável</th>
            <th>Chave de integração</th>
            <th>Status</th>
            <th>Cadastro</th>
            @can('merchant-manage')
                <th class="ds-col-action text-end">Ação</th>
            @endcan
        </x-slot>

        @forelse($merchants as $merchant)
            <tr>
                <td>
                    <div class="ds-user-cell">
                        <img class="ds-user-avatar" src="{{ asset($merchant->business_logo) }}" alt="Logo">
                        <div>
                            <div class="ds-user-name">{{ $merchant->business_name }}</div>
                            <div class="ds-user-email">{{ $merchant->site_url }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <a href="{{ route('admin.user.manage', $merchant->user->username) }}" style="color:var(--ds-accent);font-weight:500;font-size:var(--ds-text-sm);text-decoration:none;">
                        {{ $merchant->user->name }}
                    </a>
                </td>
                <td>
                    <div class="ds-col-id">{{ $merchant->merchant_key }}</div>
                </td>
                <td>
                    @php
                        $statusVal = strtolower($merchant->status->name ?? $merchant->status);
                    @endphp
                    <x-ds.badge :status="$statusVal" :label="($statusVal === 'pending' ? 'Pendente' : ($statusVal === 'approved' ? 'Aprovado' : ($statusVal === 'rejected' ? 'Rejeitado' : $merchant->status)))" />
                </td>
                <td>
                    <div class="ds-col-date">{{ $merchant->created_at->format('d/m/y H:i') }}</div>
                    <div style="font-size:.6rem;color:var(--ds-text-muted);margin-top:1px;">{{ $merchant->created_at->diffForHumans() }}</div>
                </td>
                @can('merchant-manage')
                    <td class="ds-col-action text-end">
                        <button type="button" class="btn btn-secondary btn-sm" data-coreui-toggle="modal" data-coreui-target="#review-{{ $merchant->id }}" style="font-size:var(--ds-text-xs);padding:.25rem .5rem;">
                            {{ strtolower($merchant->status->name ?? $merchant->status) === 'pending' ? 'Analisar' : 'Detalhes' }}
                        </button>
                        @include('backend.merchant.partials._review_modal')
                    </td>
                @endcan
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-ds.empty-state
                        title="Nenhum lojista registrado"
                        desc="Solicitações de abertura de conta business enviadas por usuários aparecerão aqui para revisão."
                        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>'
                    />
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <x-ds.pagination :paginator="$merchants" />
        </x-slot>
    </x-ds.table>
</x-ds.page>
@endsection
