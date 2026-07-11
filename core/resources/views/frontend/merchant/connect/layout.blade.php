@extends('frontend.layouts.user.hub-v2')
@section('title', 'Origin Connect')

@section('hub_title', 'Origin Connect')
@section('hub_icon', 'fas fa-network-wired')
@section('hub_desc', 'Plataforma de engajamento e automação multicanal')

@section('hub_nav')
<div class="cfg-nav-section" style="margin-top: 0;">Gestão</div>

<a href="{{ route('user.connect.dashboard') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.dashboard') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-chart-line"></i></span>
    <span>Visão Geral</span>
</a>

<a href="{{ route('user.connect.contacts.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.contacts.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-address-book"></i></span>
    <span>Contatos</span>
</a>

<a href="{{ route('user.connect.segments.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.segments.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-filter"></i></span>
    <span>Segmentos</span>
</a>

<a href="{{ route('user.connect.templates.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.templates.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-file-alt"></i></span>
    <span>Templates</span>
</a>

<div class="cfg-nav-section" style="margin-top: 12px;">Execução</div>

<a href="{{ route('user.connect.campaigns.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.campaigns.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-bullhorn"></i></span>
    <span>Campanhas</span>
</a>

<a href="{{ route('user.connect.journeys.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.journeys.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-project-diagram"></i></span>
    <span>Jornadas</span>
</a>

<div class="cfg-nav-section" style="margin-top: 12px;">Infraestrutura</div>

<a href="{{ route('user.connect.providers.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.providers.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-server"></i></span>
    <span>Provedores</span>
</a>

<a href="{{ route('user.connect.analytics') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.analytics') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-chart-bar"></i></span>
    <span>Analytics</span>
</a>

<a href="{{ route('user.connect.dlq.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.dlq.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-exclamation-triangle"></i></span>
    <span>DLQ</span>
</a>

<a href="{{ route('user.connect.alerts.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.alerts.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-bell"></i></span>
    <span>Alertas</span>
</a>

<div class="cfg-nav-section" style="margin-top: 12px;">Sistema</div>

<a href="{{ route('user.connect.settings.index') }}"
   class="cfg-nav-item {{ request()->routeIs('user.connect.settings.*') ? 'active' : '' }}">
    <span class="cfg-nav-icon"><i class="fas fa-sliders-h"></i></span>
    <span>Configurações</span>
</a>

@endsection

@section('hub_content')
    @yield('connect_content')
@endsection
