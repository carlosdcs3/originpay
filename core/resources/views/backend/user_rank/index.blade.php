@php use App\Enums\TrxType; @endphp
@extends('backend.layouts.app')
@section('title', __('User Ranks'))
@section('content')
    <div class="clearfix my-3 d-flex justify-content-between align-items-center">
        <div class="fs-4 fw-bold text-dark">{{ __('User Ranks Management') }}</div>
        <a href="#new_user_rank_modal" data-coreui-toggle="modal" class="btn btn-primary">
            <x-icon name="add" class="icon me-1"/>{{ __('Add New') }}
        </a>
    </div>

    {{-- KPIs --}}
    @php
        try {
            $totalRanks = \App\Models\Ranking::count();
            $activeRanks = \App\Models\Ranking::where('is_active', 1)->count();
            $defaultRank = \App\Models\Ranking::where('is_default', 1)->first();
        } catch(\Exception $e) {
            $totalRanks = $activeRanks = '—';
            $defaultRank = null;
        }
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-12">
            <x-ds.dev-stat-card title="Total de Níveis" :value="$totalRanks" icon="layer-group" />
        </div>
        <div class="col-md-4 col-12">
            <x-ds.dev-stat-card title="Níveis Ativos" :value="$activeRanks" icon="check-circle" />
        </div>
        <div class="col-md-4 col-12">
            <x-ds.dev-stat-card title="Nível Padrão" :value="$defaultRank ? $defaultRank->name : 'Nenhum'" icon="star" />
        </div>
    </div>

    <div class="card border-0 mb-4 shadow-sm rounded-3">
        <div class="card-body px-0 py-0">
            <div class="table-responsive">
                <table class="table user-table align-items-center mb-0">
                    <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('Name') . ' | ' . __('Description') }}</th>
                        <th>{{ __('Trx Amount | Reward')  }}</th>
                        <th>{{ __('Trx Type Allowed') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="pe-4 text-end">{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($userRanks as $userRank)
                        <tr class="align-middle">
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img class="avatar rounded me-3 border shadow-sm" width="40" height="40"
                                         src="{{ asset($userRank->icon) }}" alt="User Avatar" style="object-fit: cover;">
                                    <div>
                                        <div class="fw-bold text-dark text-nowrap d-flex align-items-center">
                                            {{ $userRank->name }}
                                            @if($userRank->is_default)
                                                <span class="badge badge-sm bg-success ms-2">{{ __('Default') }}</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted mt-1" style="max-width: 250px;">
                                            {{ \Illuminate\Support\Str::limit($userRank->description, 70) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    {{ $userRank->transaction_amount .' '. siteCurrency() }}
                                </div>
                                <div class="text-muted small mt-1">
                                    {{ __('Reward:') }} <span class="fw-semibold text-success">{{ $userRank->reward .' '. siteCurrency() }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                @foreach($userRank->transaction_types as $transactionType)
                                    <span class="badge bg-{{ \App\Enums\TrxType::getBadgesColor([$transactionType]) }} px-2 py-1">{{ title($transactionType) }}</span>
                                @endforeach
                                </div>
                            </td>
                            <td>
                                <x-ds.badge :status="$userRank->is_active ? 'paid' : 'cancelled'" :label="strtoupper($userRank->is_active ? 'active' : 'inactive')" />
                            </td>
                            <td class="pe-4 text-end">
                                <button type="button" data-edit-url="{{ route('admin.ranking.edit', $userRank->id) }}"
                                        class="btn btn-secondary btn-sm edit-modal" style="font-size:var(--ds-text-xs);padding:.25rem .5rem;">
                                    <x-icon name="manage" height="14" class="me-1"/>
                                    {{ __('Gerenciar') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <x-ds.empty-state 
                                    title="Nenhum nível de ranking configurado" 
                                    desc="Crie níveis de fidelidade ou limites baseados em volume de transações." 
                                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />' 
                                />
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    @include('backend.user_rank.partials._new_user_rank_modal')
    @include('backend.user_rank.partials._edit_user_rank_modal')

@endsection
@push('scripts')
    <script>
        $(document).ready(function () {
            editFormByModal('edit_user_rank_modal', 'edit_user_rank_data',true , true);
        });
    </script>
@endpush
