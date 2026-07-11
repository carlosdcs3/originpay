@extends('backend.layouts.app')
@section('title', __('Referrals'))
@section('content')
    <div class="clearfix my-4 ">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark">{{ __('Referrals') }}</h2>
                <p class="text-muted mb-0 small">{{ __('Easily manage and customize referral rewards, levels, and settings below.') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.referral.card.content') }}" class="btn btn-info d-flex align-items-center text-white shadow-sm">
                    <i class="la la-file-alt me-1"></i>
                    <span class="d-none d-sm-inline">{{ __('Referral Content') }}</span>
                </a>
                <a href="#new-reward-modal" data-coreui-toggle="modal" class="btn btn-primary d-flex align-items-center shadow-sm">
                    <x-icon name="add" class="icon"/>
                    <span class="d-none d-sm-inline ms-1">{{ __('Add Reward') }}</span>
                </a>
            </div>
        </div>
    </div>
    
    {{-- KPIs --}}
    @php
        try {
            $totalLevels = \App\Models\ReferralReward::count();
            $maxPercentage = \App\Models\ReferralReward::max('percentage') ?? 0;
            $activeTypes = count($referralRewards ?? []);
        } catch(\Exception $e) {
            $totalLevels = $maxPercentage = $activeTypes = '—';
        }
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-12">
            <x-ds.dev-stat-card title="Total de Níveis" :value="$totalLevels" icon="layer-group" />
        </div>
        <div class="col-md-4 col-12">
            <x-ds.dev-stat-card title="Tipos Ativos" :value="$activeTypes" icon="check-circle" />
        </div>
        <div class="col-md-4 col-12">
            <x-ds.dev-stat-card title="Maior Bônus" :value="$maxPercentage.'%'" icon="star" />
        </div>
    </div>

    <div class="row g-3">
        @foreach($referralRewards as $rewardType => $rewards)
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100 rounded-3">
                    <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center py-3 rounded-top-3">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">{{ __(':name Rewards', ['name' => ucwords($rewardType)]) }}</h5>
                            <p class="small text-muted mb-0">{{ __('Manage and customize reward levels below.') }}</p>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <div class="form-check form-switch">
                                <input
                                        id="reward-switch-{{ $rewardType }}"
                                        class="form-check-input coevs-switch shadow-sm"
                                        type="checkbox"
                                        name="status"
                                        data-type="{{ $rewardType }}"
                                        value="1"
                                        @checked(setting($rewardType.'_rewards'))
                                        aria-label="{{ __('Toggle status for :rewardType', ['rewardType' => $rewardType]) }}"
                                        data-coreui-toggle="tooltip"
                                        data-coreui-placement="top"
                                        title="{{ __('If disabled, no rewards will be given out for :rewardType actions.', ['rewardType' => $rewardType]) }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="list-group list-group-flush border rounded-3 overflow-hidden shadow-sm">
                            @forelse($rewards as $reward)
                                <div class="list-group-item d-flex justify-content-between align-items-center border-bottom border-light py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold" style="width: 40px; height: 40px;">
                                            {{ $reward->level }}
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">{{ __('Level :count', ['count' => $reward->level]) }}</h6>
                                            <small class="text-muted">Recompensa padrão</small>
                                        </div>
                                        <span class="badge rounded-pill bg-success text-white px-3 py-2 ms-4 fs-6 shadow-sm">{{ $reward->percentage }}%</span>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-light border edit-modal shadow-sm" data-edit-url="{{ route('admin.referral.edit', ['id' => $reward->id]) }}" title="Editar Nível">
                                            <i class="la la-pen text-primary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border delete shadow-sm" data-url="{{ route('admin.referral.delete', ['type' => $rewardType, 'id' => $reward->id]) }}" title="Remover Nível">
                                            <i class="la la-trash text-danger"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center">
                                    <x-ds.empty-state 
                                        title="Nenhum nível configurado" 
                                        desc="Este programa de indicação ainda não possui níveis ativos." 
                                        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' 
                                    />
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @include('backend.referral.partials._new_reward_modal')
    @include('backend.referral.partials._update_reward_modal')

@endsection
@push('scripts')
    <script>
        "use strict";
        $(document).on('change', '.coevs-switch', function () {
            let type = $(this).data('type');
            let status = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                url: "{{ route('admin.referral.status-update', ['type' => ':type', 'status' => ':status']) }}"
                    .replace(':type', type)
                    .replace(':status', status),
                method: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {
                        notifyEvs('success', response.message);
                    }
                }
            });
        });
        editFormByModal('edit-reward-modal', 'edit-reward-data');
    </script>
@endpush
