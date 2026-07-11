@extends('frontend.layouts.user-v2')
@section('title', __('Configurações'))

@section('hub_title', 'Configurações')
@section('hub_icon', 'fas fa-sliders-h')
@section('hub_desc', 'Gerencie seu perfil, segurança e preferências')

@section('hub_nav')
<div class="v2-settings-nav-section" style="margin-top: 0;">Conta</div>

<a href="{{ route('user.settings.profile') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.settings.profile') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-user"></i></span>
    <span>Perfil</span>
    @if(!auth()->user()->email_verified_at)
        <span class="v2-settings-nav-badge">!</span>
    @endif
</a>

<a href="{{ route('user.settings.account') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.settings.account') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-wallet"></i></span>
    <span>Minha Conta</span>
</a>

<a href="{{ route('user.pix-keys.index') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.pix-keys.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-qrcode"></i></span>
    <span>Chaves PIX</span>
</a>

<a href="{{ route('user.kyc.verify') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.kyc.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-id-card"></i></span>
    <span>Verificação KYC</span>
    @php $kyc = auth()->user()->kycProfile; @endphp
    @if(!$kyc || $kyc->status->value === 'unverified')
        <span class="v2-settings-nav-badge">KYC</span>
    @elseif($kyc->status->value === 'verified')
        <span class="v2-settings-nav-badge ok"><i class="fas fa-check"></i></span>
    @endif
</a>

<div class="v2-settings-nav-section">Segurança</div>

<a href="{{ route('user.settings.password.change') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.settings.password.change') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-lock"></i></span>
    <span>Alterar Senha</span>
</a>

<a href="{{ route('user.2fa.setup') }}"
   class="v2-settings-nav-item {{ request()->routeIs('user.2fa.*') ? 'active' : '' }}">
    <span class="v2-settings-nav-icon"><i class="fas fa-shield-alt"></i></span>
    <span>Autenticação 2FA</span>
    @if(auth()->user()->two_factor_enabled)
        <span class="v2-settings-nav-badge ok"><i class="fas fa-check"></i></span>
    @endif
</a>

@endsection

@section('hub_content')
    @yield('user_setting_content')
@endsection


