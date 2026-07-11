@extends('backend.layouts.app')
@section('title', 'Todos KYC')

@section('content')
<x-ds.page 
    title="Análise KYC" 
    :breadcrumb="[
        ['title' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['title' => 'KYC']
    ]">
    {{-- KPIs --}}
    @php
        try {
            $pendingKyc = \App\Models\KycForm::where('status', 0)->count();
            $approvedKyc = \App\Models\KycForm::where('status', 1)->count();
            $rejectedKyc = \App\Models\KycForm::where('status', 2)->count();
            $expiredKyc = \App\Models\KycForm::where('status', 3)->count(); // fallback logic
        } catch(\Exception $e) {
            $pendingKyc = $approvedKyc = $rejectedKyc = $expiredKyc = '—';
        }
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Pendentes" :value="$pendingKyc" icon="clock" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Aprovados" :value="$approvedKyc" icon="check-circle" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Rejeitados" :value="$rejectedKyc" icon="times-circle" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Expirados" :value="$expiredKyc" icon="history" />
        </div>
    </div>

    {{-- Table Card --}}
    <x-ds.table 
        title="Solicitações Pendentes" 
        :count="$kycRequests->total() ?? $kycRequests->count()"
        :isEmpty="$kycRequests->isEmpty()"
        :action="route('admin.kyc.index')">
        
        <x-slot name="filters">
            <input type="hidden" name="daterange" value="{{ request('daterange') }}">
            <div class="position-relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ds-text-muted);pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" name="search" class="ds-filter-input form-control" placeholder="Buscar por usuário..." value="{{ request('search') }}" style="padding-left:2rem!important;">
            </div>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Filtrar
            </button>
            @if(request('search') || request('daterange'))
                <a href="{{ route('admin.kyc.index') }}" class="btn btn-ghost" style="color:var(--ds-text-muted);">Limpar</a>
            @endif
        </x-slot>
        
        <x-slot name="thead">
            <th>Usuário</th>
            <th>Tipo de Documento</th>
            <th>Data de Envio</th>
            @can('kyc-action')
                <th class="ds-col-action text-end">Ação</th>
            @endcan
        </x-slot>

        @forelse($kycRequests as $submission)
        <tr>
            <td>
                <div class="ds-user-cell">
                    <img class="ds-user-avatar" src="{{ asset($submission->user->avatar_alt) }}" alt="{{ $submission->user->name }}">
                    <div>
                        <div class="ds-user-name">{{ $submission->user->name }}</div>
                        <div class="ds-user-email">{{ $submission->user->email }}</div>
                    </div>
                </div>
            </td>
            <td>
                <div>{{ $submission->kycTemplate->title }}</div>
                <div style="margin-top:2px;">
                    <span class="badge bg-{{ $submission->user->role->color() }}" style="font-size:10px !important;padding:1px 4px!important;">
                        {{ $submission->user->role->title() }}
                    </span>
                </div>
            </td>
            <td>
                <div class="ds-col-date">{{ $submission->created_at->format('d/m/y H:i') }}</div>
                <div style="font-size:.6rem;color:var(--ds-text-muted);margin-top:1px;">{{ $submission->created_at->diffForHumans() }}</div>
            </td>
            @can('kyc-action')
            <td class="ds-col-action text-end">
                <button type="button" class="btn btn-secondary btn-sm" data-coreui-toggle="modal" data-coreui-target="#review-{{ $submission->id }}" style="font-size:var(--ds-text-xs);padding:.25rem .5rem;">
                    Revisar
                </button>
                @include('backend.kyc.partials._review_modal',['action' => 'view'])
            </td>
            @endcan
        </tr>
        @empty
        <tr>
            <td colspan="4">
                <x-ds.empty-state 
                    title="Nenhuma solicitação aguardando revisão" 
                    desc="Solicitações de verificação de identidade (KYC) submetidas pelos usuários aparecerão aqui." 
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>'
                />
            </td>
        </tr>
        @endforelse

        <x-slot name="pagination">
            <x-ds.pagination :paginator="$kycRequests" />
        </x-slot>

    </x-ds.table>

</x-ds.page>
@endsection
