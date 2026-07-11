@extends('frontend.layouts.auth')
@section('title', 'Criar conta merchant')

@php
    $myCurrentLocation = getLocation();
    $allCountries = getCountries();
@endphp

@push('styles')
<style>
@keyframes op-fadein {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.op-auth-page { animation: op-fadein .35s cubic-bezier(.4,0,.2,1) both; }

.op-form-glow {
    position: absolute;
    width: 520px;
    height: 520px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,.06) 0%, transparent 70%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 0;
}

.op-orb-1 {
    position: absolute;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,.18) 0%, transparent 70%);
    top: -180px;
    left: -220px;
    filter: blur(90px);
    pointer-events: none;
}

.op-orb-2 {
    position: absolute;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0,212,170,.07) 0%, transparent 70%);
    bottom: -100px;
    right: -100px;
    filter: blur(80px);
    pointer-events: none;
}

.op-h1 {
    font-size: 1.85rem;
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--text-primary);
    margin-bottom: 10px;
    line-height: 1.15;
}

.op-subtitle {
    font-size: .92rem;
    color: #7c8499;
    line-height: 1.55;
    margin-bottom: 0;
}

.op-auth-input::placeholder { color: #4a5068; }

.op-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(16,185,129,.08);
    border: 1px solid rgba(16,185,129,.18);
    color: #86efac;
    font-size: .78rem;
    font-weight: 600;
}

.op-side-title {
    font-size: clamp(2rem, 3.2vw, 3rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -.04em;
    margin-bottom: 28px;
    color: var(--text-primary);
}

.op-side-list {
    display: grid;
    gap: 18px;
    margin-bottom: 28px;
}

.op-side-item {
    display: flex;
    gap: 14px;
    align-items: flex-start;
}

.op-side-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    background: rgba(124,58,237,.18);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #a78bfa;
    flex-shrink: 0;
}

.op-side-item h3 {
    margin: 0 0 4px;
    font-size: .95rem;
    color: var(--text-primary);
}

.op-side-item p {
    margin: 0;
    color: var(--text-muted);
    font-size: .85rem;
    line-height: 1.55;
}

.op-status-card {
    padding: 14px 18px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(0,0,0,.25);
}

.op-status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: .78rem;
    padding: 6px 0;
}

.op-status-row:not(:last-child) { border-bottom: 1px solid rgba(255,255,255,.06); }
.op-status-lbl { color: #6b7280; }
.op-status-val-green { color: #34d399; font-weight: 600; }
.op-status-val-white { color: #e2e8f0; font-weight: 600; }

.op-form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.op-auth-textarea {
    width: 100%;
    min-height: 96px;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.04);
    color: var(--text-primary);
    font-size: 0.95rem;
    font-family: var(--font);
    resize: vertical;
}

.op-phone-wrap {
    display: flex;
    padding: 0;
    overflow: hidden;
    height: 48px;
}

.op-phone-code {
    padding: 0 12px;
    background: rgba(255,255,255,0.04);
    color: #7c8499;
    font-size: .85rem;
    border-right: 1px solid rgba(255,255,255,0.08);
    white-space: nowrap;
    display: flex;
    align-items: center;
    flex-shrink: 0;
    height: 100%;
}

.op-phone-input {
    flex: 1;
    padding: 0 16px;
    border: none;
    background: transparent;
    color: var(--text-primary);
}

.op-phone-input:focus { box-shadow: none; }

@media (max-width: 768px) {
    .auth-split-right { display: none !important; }
}

@media (max-width: 600px) {
    .op-form-grid-2 { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('auth-content')
<div style="position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0;">
    <div class="op-orb-1"></div>
    <div class="op-orb-2"></div>
</div>

<div class="op-auth-page" style="display:flex;min-height:100vh;width:100%;position:relative;z-index:1;">
    <div style="flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:40px 24px;position:relative;">
        <div class="op-form-glow"></div>
        <div class="op-auth-card-wide">
            <div style="text-align:center;margin-bottom:32px;">
                <a href="{{ route('home') }}" style="display:inline-block;margin-bottom:32px;text-decoration:none;">
                    <img src="{{ asset('frontend/images/originpay/originpay-logo-horizontal-dark.svg') }}" alt="OriginPay" style="height:44px;width:auto;">
                </a>
                <div class="op-pill" style="margin-bottom:18px;">
                    <i class="fas fa-store"></i>
                    Conta merchant
                </div>
                <h1 class="op-h1">Criar sua conta merchant</h1>
                <p class="op-subtitle">Comece a receber com links de pagamento, API keys, webhooks e saques em um único painel.</p>
            </div>

            @if ($errors->any())
                <div class="op-auth-error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('merchant.register') }}" method="POST">
                @csrf

                <div class="op-form-grid-2">
                    <div>
                        <label class="op-auth-label" for="first_name">Nome</label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" class="op-auth-input" autocomplete="given-name" placeholder="João" required>
                    </div>
                    <div>
                        <label class="op-auth-label" for="last_name">Sobrenome</label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" class="op-auth-input" autocomplete="family-name" placeholder="Silva" required>
                    </div>
                </div>

                <div class="op-form-grid-2">
                    <div>
                        <label class="op-auth-label" for="username">Usuário</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" class="op-auth-input" autocomplete="username" placeholder="minhaempresa" required>
                    </div>
                    <div>
                        <label class="op-auth-label" for="email">E-mail de acesso</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="op-auth-input" autocomplete="email" placeholder="financeiro@empresa.com" required>
                    </div>
                </div>

                <div class="op-form-grid-2">
                    <div>
                        <label class="op-auth-label" for="countrySelect">País</label>
                        <select id="countrySelect" name="country" class="op-auth-input" required>
                            <option selected disabled>Selecionar país</option>
                            @foreach($allCountries as $country)
                                <option value="{{ $country['code'].':'.$country['dial_code'] }}" @selected(old('country', $myCurrentLocation['dial_code']) == $country['dial_code'])>
                                    {{ title($country['name']) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="op-auth-label" for="phone">Celular / WhatsApp</label>
                        <div class="op-auth-input op-phone-wrap">
                            <span class="op-phone-code" id="phone_code">{{ old('country') ? explode(':', old('country'))[1] ?? $myCurrentLocation['dial_code'] : $myCurrentLocation['dial_code'] }}</span>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="op-phone-input" placeholder="(11) 99999-9999" required>
                        </div>
                    </div>
                </div>

                <div class="op-form-grid-2">
                    <div>
                        <label class="op-auth-label" for="business_name">Nome do negócio</label>
                        <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" class="op-auth-input" placeholder="Minha Empresa LTDA" required>
                    </div>
                    <div>
                        <label class="op-auth-label" for="business_address">Endereço comercial</label>
                        <input type="text" name="business_address" id="business_address" value="{{ old('business_address') }}" class="op-auth-input" placeholder="Rua, número e complemento" required>
                    </div>
                </div>

                <div class="op-form-grid-2">
                    <div>
                        <label class="op-auth-label" for="password">Senha</label>
                        <input type="password" name="password" id="password" class="op-auth-input" autocomplete="new-password" placeholder="Crie uma senha forte" required>
                    </div>
                    <div>
                        <label class="op-auth-label" for="password_confirmation">Confirmar senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="op-auth-input" autocomplete="new-password" placeholder="Repita sua senha" required>
                    </div>
                </div>

                <button type="submit" class="op-auth-submit" style="margin-top:12px;">
                    Criar conta merchant
                </button>
            </form>

            <div style="text-align:center;margin-top:24px;font-size:.88rem;color:#7c8499;">
                Já possui acesso?
                <a href="{{ route('merchant.login') }}" class="op-auth-link">Entrar no painel</a>
            </div>

            <div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);font-size:.84rem;color:#7c8499;">
                Quer usar a OriginPay como usuário comum?
                <a href="{{ route('user.register') }}" class="op-auth-link">Criar conta de usuário</a>
            </div>
        </div>
    </div>

    <div class="auth-split-right" style="flex:1;display:flex;align-items:center;justify-content:center;padding:48px 56px;position:relative;">
        <div style="max-width:520px;">
            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8b5cf6;margin-bottom:16px;">Painel operacional do merchant</div>
            <h2 class="op-side-title">Abra sua operação e publique sua primeira cobrança hoje.</h2>

            <div class="op-side-list">
                <div class="op-side-item">
                    <div class="op-side-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <h3>Receba com rapidez</h3>
                        <p>Ative cobranças, links de pagamento e rotinas de conciliação sem depender de suporte.</p>
                    </div>
                </div>
                <div class="op-side-item">
                    <div class="op-side-icon"><i class="fas fa-satellite-dish"></i></div>
                    <div>
                        <h3>Integre com segurança</h3>
                        <p>Crie chaves, configure ambientes e conecte webhooks com o mesmo fluxo do painel.</p>
                    </div>
                </div>
                <div class="op-side-item">
                    <div class="op-side-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <h3>Controle operacional</h3>
                        <p>Acompanhe status, saldo e solicitações de saque em uma interface pensada para uso diário.</p>
                    </div>
                </div>
            </div>

            <div class="op-status-card">
                <div class="op-status-row">
                    <span class="op-status-lbl">Primeiro passo</span>
                    <span class="op-status-val-green">Conta criada em minutos</span>
                </div>
                <div class="op-status-row">
                    <span class="op-status-lbl">Depois do acesso</span>
                    <span class="op-status-val-white">API keys e checkout</span>
                </div>
                <div class="op-status-row">
                    <span class="op-status-lbl">Operação diária</span>
                    <span class="op-status-val-white">Cobranças, saldo e saque</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
