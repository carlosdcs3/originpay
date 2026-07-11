@extends('backend.layouts.app')
@section('title', $title)

@section('content')
<x-ds.page 
    :title="$title" 
    :breadcrumb="[
        ['title' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['title' => 'Usuários']
    ]">
    
    <x-slot name="actions">
        @can('user-create')
            <a href="#new_user_modal" data-coreui-toggle="modal" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Novo Usuário
            </a>
        @endcan
    </x-slot>

    {{-- KPIs --}}
    @php
        try {
            $totalUsers = \App\Models\User::count();
            $activeUsers = \App\Models\User::where('status', 1)->count();
            $blockedUsers = \App\Models\User::where('status', 0)->count();
            $pendingKyc = \App\Models\User::where('kyc_status', 2)->count();
            $newToday = \App\Models\User::whereDate('created_at', \Carbon\Carbon::today())->count();
        } catch(\Exception $e) {
            $totalUsers = $activeUsers = $blockedUsers = $pendingKyc = $newToday = '—';
        }
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <x-ds.dev-stat-card title="Total" :value="$totalUsers" icon="users" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Ativos" :value="$activeUsers" icon="check-circle" />
        </div>
        <div class="col-md-2 col-6">
            <x-ds.dev-stat-card title="Bloqueados" :value="$blockedUsers" icon="ban" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Pending KYC" :value="$pendingKyc" icon="id-card" />
        </div>
        <div class="col-md-2 col-6">
            <x-ds.dev-stat-card title="Hoje" :value="$newToday" icon="calendar-day" />
        </div>
    </div>

    {{-- Tabs --}}
    <div class="ds-tabs mb-4">
        <a class="ds-tab-btn {{ request('status', 'all') === 'all' && !request('kyc_status') && !request('email_verified') ? 'active' : '' }}" href="{{ route('admin.user.index') }}">
            Todos os Usuários
        </a>
        <a class="ds-tab-btn {{ request('status') === '1' ? 'active' : '' }}" href="{{ route('admin.user.index', ['status' => 1]) }}">
            Ativos
        </a>
        <a class="ds-tab-btn {{ request('status') === '0' ? 'active' : '' }}" href="{{ route('admin.user.index', ['status' => 0]) }}">
            Suspensos
        </a>
        <a class="ds-tab-btn {{ request('email_verified') === '0' ? 'active' : '' }}" href="{{ route('admin.user.index', ['email_verified' => 0]) }}">
            Não Verificados (Email)
        </a>
        <a class="ds-tab-btn {{ request('kyc_status') === '2' ? 'active' : '' }}" href="{{ route('admin.user.index', ['kyc_status' => 2]) }}">
            Aguardando KYC
        </a>
    </div>

    {{-- Table Card --}}
    <x-ds.table 
        title="Lista de Usuários" 
        :count="$users->total() ?? $users->count()"
        :isEmpty="$users->isEmpty()"
        :action="url()->current()">
        
        <x-slot name="filters">
            @include('backend.user.partials._filters')
        </x-slot>
        
        <x-slot name="thead">
            <th>Usuário</th>
            <th>Email / Status</th>
            <th>Informações KYC</th>
            <th>Cadastro</th>
            <th>Último Login</th>
            @can('user-manage')
                 <th class="ds-col-action text-end">Ação</th>
            @endcan
        </x-slot>

        @forelse($users as $user)
            @php
                $avatarData = getUserAvatarDetails($user->first_name, $user->last_name);
                $kycSubmission = $user->kycSubmission;
                $kycStatus = $kycSubmission?->status ?? null;
                $statusText = $kycStatus?->label() ?? 'Não Enviado';
                $kycTitle   = $kycSubmission?->kycTemplate->title ?? 'Sem submissão';
            @endphp
        <tr>
            <td>
                <div class="ds-user-cell">
                    @if($user->avatar)
                        <img class="ds-user-avatar" src="{{ asset($user->avatar) }}" alt="Avatar">
                    @else
                        <div class="ds-user-avatar {{ $avatarData['class'] }}" style="color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;">
                            {{ $avatarData['initials'] }}
                        </div>
                    @endif
                    <div>
                        <div class="ds-user-name">{{ title($user->name) }}</div>
                        <div class="ds-user-email">
                            {{ $user->username }}
                            <span class="badge bg-{{ $user->role?->color() ?? 'secondary' }}" style="font-size:10px !important;padding:1px 4px!important;margin-left:4px;">{{ $user->role?->title() ?? 'System' }}</span>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-size:var(--ds-text-sm);color:var(--ds-text);">{{ maskSensitive($user->email) }}</div>
                <div style="margin-top:2px;">
                    @if($user->email_verified_at)
                        <x-ds.badge status="paid" label="Verificado" />
                    @else
                        <x-ds.badge status="cancelled" label="Não Verificado" />
                    @endif
                </div>
            </td>
            <td>
                <div style="font-size:var(--ds-text-sm);color:var(--ds-text);">
                    {{ $kycSubmission ? 'Doc: ' . $kycTitle : 'Sem documento KYC' }}
                </div>
                <div style="margin-top:2px;">
                    @if(strtolower($statusText) === 'approved' || strtolower($statusText) === 'aprovado')
                        <x-ds.badge status="paid" :label="$statusText" />
                    @elseif(strtolower($statusText) === 'pending' || strtolower($statusText) === 'pendente')
                        <x-ds.badge status="pending" :label="$statusText" />
                    @elseif(strtolower($statusText) === 'rejected' || strtolower($statusText) === 'rejeitado')
                        <x-ds.badge status="cancelled" :label="$statusText" />
                    @else
                        <x-ds.badge :label="$statusText" />
                    @endif
                </div>
            </td>
            <td>
                <div class="ds-col-date">{{ $user->created_at->format('d/m/y H:i') }}</div>
                <div style="font-size:.6rem;color:var(--ds-text-muted);margin-top:1px;">{{ $user->created_at->diffForHumans() }}</div>
            </td>
            <td>
                @if($user->last_login_at)
                    <div class="ds-col-date">{{ \Carbon\Carbon::parse($user->last_login_at)->format('d/m/y H:i') }}</div>
                    <div style="font-size:.6rem;color:var(--ds-text-muted);margin-top:1px;">IP: {{ $user->last_login_ip ?? 'N/A' }}</div>
                @else
                    <span class="ds-col-date">Nunca</span>
                @endif
            </td>
            @can('user-manage')
            <td class="ds-col-action text-end">
                <a href="{{ $user->username ? route('admin.user.manage', $user->username) : '#' }}" class="btn btn-secondary btn-sm" style="font-size:var(--ds-text-xs);padding:.25rem .5rem;">
                    Gerenciar
                </a>
            </td>
            @endcan
        </tr>
        @empty
        <tr>
            <td colspan="6">
                <x-ds.empty-state 
                    title="Nenhum usuário corresponde aos filtros" 
                    desc="Verifique sua busca ou altere as abas selecionadas. Não há dados ativos no momento."
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'
                />
            </td>
        </tr>
        @endforelse

        <x-slot name="pagination">
            <x-ds.pagination :paginator="$users" />
        </x-slot>

    </x-ds.table>
</x-ds.page>
@endsection
