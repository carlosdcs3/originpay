@extends('frontend.layouts.user.hub-v2')
@section('title', __('Desenvolvedor'))

@section('hub_title', 'Hub Desenvolvedor')
@section('hub_icon', 'fas fa-code')
@section('hub_desc', 'Gerencie chaves, webhooks, sandbox e integrações')
@section('hub_nav_class', 'developer-hub-nav')

@push('styles')
<style>
    .developer-hub-nav .developer-nav-section {
        color: var(--ds-text-muted);
        cursor: default;
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .developer-hub-nav {
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            gap: 8px !important;
            padding-bottom: 8px !important;
        }

        .developer-hub-nav .developer-nav-section {
            display: none !important;
        }

        .developer-hub-nav .v2-settings-nav-item {
            flex: 0 0 104px !important;
            min-height: 58px !important;
            padding: 9px 8px !important;
        }

        .developer-hub-nav .v2-settings-nav-item.active {
            order: -1 !important;
        }
    }
</style>
@endpush

@section('hub_nav')

{{-- Integração --}}
<div class="v2-settings-nav-section developer-nav-section" style="margin-top: 0;">Integração</div>

<a href="{{ route('user.developer.api-keys.index') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.developer.api-keys.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-key"></i></span>
    Chaves de API
</a>
<a href="{{ route('user.developer.webhooks.index') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.developer.webhooks.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-satellite-dish"></i></span>
    Webhooks
</a>
<a href="{{ route('user.developer.logs.index') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.developer.logs.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-terminal"></i></span>
    Logs da API
</a>

{{-- Ferramentas --}}
<div class="v2-settings-nav-section developer-nav-section">Ferramentas</div>

<a href="{{ route('user.developer.sandbox.index') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.developer.sandbox.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-flask"></i></span>
    Sandbox
</a>

{{-- Recursos --}}
<div class="v2-settings-nav-section developer-nav-section">Recursos</div>

<a href="{{ route('user.developer.docs.index') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.developer.docs.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-book"></i></span>
    Documentação
</a>

@endsection

@section('hub_content')
    @yield('user_developer_content')
@endsection
