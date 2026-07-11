@extends('frontend.layouts.auth')
@section('title', __('Criar Conta'))
@section('auth-content')

@push('styles')
<style>
/* ========================================
   AUTH SHARED PREMIUM - Login & Register
======================================== */
@keyframes op-fadein {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.op-auth-page { animation: op-fadein .35s cubic-bezier(.4,0,.2,1) both; }

@keyframes op-pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,.5); }
    50%      { box-shadow: 0 0 0 5px rgba(16,185,129,0); }
}

.op-form-glow {
    position: absolute;
    width: 520px; height: 520px; border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,.06) 0%, transparent 70%);
    top: 50%; left: 50%; transform: translate(-50%,-50%);
    pointer-events: none; z-index: 0;
}

.op-h1 {
    font-size: 1.85rem; font-weight: 800;
    letter-spacing: -.035em; color: var(--text-primary);
    margin-bottom: 10px; line-height: 1.15;
}
.op-subtitle { font-size: .92rem; color: #7c8499; line-height: 1.55; }

.op-auth-input {
    width: 100%; padding: 13px 16px;
    border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04);
    color: var(--text-primary); font-size: .93rem; font-family: var(--font);
    outline: none;
    transition: border-color .2s cubic-bezier(.4,0,.2,1),
                box-shadow .2s cubic-bezier(.4,0,.2,1), background .2s;
}
.op-auth-input::placeholder { color: #4a5068; }
.op-auth-input:focus {
    border-color: rgba(124,58,237,.65);
    background: rgba(124,58,237,.04);
    box-shadow: 0 0 0 3px rgba(124,58,237,.13);
}
.op-auth-input:-webkit-autofill,
.op-auth-input:-webkit-autofill:hover,
.op-auth-input:-webkit-autofill:focus {
    -webkit-text-fill-color: var(--text-primary) !important;
    -webkit-box-shadow: 0 0 0 1000px #0f1022 inset !important;
    transition: background-color 5000s ease-in-out 0s;
}

.op-auth-label {
    display: block; font-size: .78rem; font-weight: 600; color: #8892a4;
    margin-bottom: 8px; text-transform: uppercase; letter-spacing: .06em;
}

.op-auth-submit {
    width: 100%; padding: 14px; border-radius: 12px; border: none;
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    color: #fff; font-size: .95rem; font-weight: 700; cursor: pointer;
    letter-spacing: .01em; position: relative; overflow: hidden;
    transition: transform .2s cubic-bezier(.4,0,.2,1), box-shadow .2s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 4px 20px rgba(109,40,217,.35), 0 1px 4px rgba(0,0,0,.3);
}
.op-auth-submit::before {
    display: none;
}
@keyframes shimmer { 0%{left:-100%} 100%{left:160%} }
.op-auth-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(109,40,217,.32), 0 2px 8px rgba(0,0,0,.24);
}
.op-auth-submit:active { transform: translateY(0); }
.op-auth-submit:disabled { opacity:.55; cursor:not-allowed; transform:none; }

.op-auth-link { color: #9f7aea; text-decoration: none; font-weight: 600; transition: color .2s; }
.op-auth-link:hover { color: #c4b5fd; }

.op-auth-brand {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    margin-bottom: 32px;
    text-decoration: none;
}
.op-auth-brand-word {
    color: #ffffff;
    font-size: 1.72rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1;
}
.op-auth-brand-word span { color: #7c3aed; }
.op-auth-brand-tagline {
    color: #94a3b8;
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 0.01em;
}

.op-auth-error {
    background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.18);
    border-radius: 12px; padding: 13px 16px; margin-bottom: 24px;
    color: #fca5a5; font-size: .85rem; line-height: 1.5;
}

.op-auth-check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 4px 0 24px;
    color: #94a3b8;
    font-size: .84rem;
    line-height: 1.45;
}
.op-auth-check input {
    width: 18px;
    height: 18px;
    margin-top: 1px;
    accent-color: #7c3aed;
    flex-shrink: 0;
}

.op-auth-card      { width:100%; max-width:420px; position:relative; z-index:1; }
.op-auth-card-wide { width:100%; max-width:540px; position:relative; z-index:1; }

.op-feat-row {
    display: flex; gap: 16px; align-items: flex-start;
    transition: transform .2s cubic-bezier(.4,0,.2,1);
}
.op-feat-row:hover { transform: translateX(4px); }
.op-feat-row:hover .op-pillar-feature-icon {
    background: rgba(124,58,237,.3);
    box-shadow: 0 0 12px rgba(124,58,237,.3);
}
.op-pillar-feature-icon {
    width: 40px; height: 40px; border-radius: 11px;
    background: rgba(124,58,237,.18);
    display: flex; align-items: center; justify-content: center;
    color: #a78bfa; font-size: .9rem; flex-shrink: 0;
    transition: background .2s, box-shadow .2s;
}

.op-status-card {
    padding: 14px 18px; background: rgba(255,255,255,.03);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.25);
}
.op-status-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .78rem; padding: 6px 0; transition: opacity .2s;
}
.op-status-row:not(:last-child) { border-bottom: 1px solid rgba(255,255,255,.06); }
.op-status-row:hover { opacity: .8; }
.op-status-lbl { color: #6b7280; }
.op-status-val-green { color: #34d399; font-weight: 600; }
.op-status-val-white { color: #e2e8f0; font-weight: 600; }

.op-hero-title {
    font-size: clamp(2rem, 3.2vw, 3.2rem);
    font-weight: 800; letter-spacing: -.04em; line-height: 1.05;
    margin-bottom: 32px; color: var(--text-primary);
}

.op-orb-1 {
    position: absolute; width: 600px; height: 600px; border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,.18) 0%, transparent 70%);
    top: -180px; left: -220px; filter: blur(90px); pointer-events: none;
}
.op-orb-2 {
    position: absolute; width: 400px; height: 400px; border-radius: 50%;
    background: radial-gradient(circle, rgba(0,212,170,.07) 0%, transparent 70%);
    bottom: -100px; right: -100px; filter: blur(80px); pointer-events: none;
}

select.op-auth-input option {
    background: #0f1022;
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .auth-split-right { display: none !important; }
}
@media (max-width: 600px) {
    .op-form-grid-2 { grid-template-columns: 1fr !important; }
    .op-auth-card-wide { padding: 0 4px; }
}
</style>
@endpush

<div style="position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0;">
    <div class="op-orb-1"></div>
    <div class="op-orb-2"></div>
</div>

<div class="op-auth-page" style="display:flex;min-height:100vh;width:100%;position:relative;z-index:1;">

    {{-- LEFT: Form --}}
    <div style="flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:40px 24px;position:relative;">
        <div class="op-form-glow"></div>
        <div class="op-auth-card-wide">

            {{-- Logo + Heading (single block) --}}
            <div style="text-align:center;margin-bottom:32px;">
                <a href="{{ route('home') }}" class="op-auth-brand" aria-label="OriginPay">
                    <span class="op-auth-brand-word">Origin<span>Pay</span></span>
                    <span class="op-auth-brand-tagline">Sua gateway sem limites.</span>
                </a>
                <h1 class="op-h1">Criar sua conta</h1>
                <p class="op-subtitle">Infraestrutura completa de pagamentos. Comece grátis, agora.</p>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="op-auth-error">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('user.register') }}" method="POST">
                @csrf

                <div style="margin-bottom:20px;">
                    <label class="op-auth-label" for="name">Nome Completo</label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name') }}" required class="op-auth-input"
                           autocomplete="name" placeholder="João Silva">
                </div>

                <div style="margin-bottom:20px;">
                    <label class="op-auth-label" for="email">E-mail</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email') }}" required class="op-auth-input"
                           autocomplete="email" placeholder="voce@empresa.com">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;" class="op-form-grid-2">
                    <div>
                        <label class="op-auth-label" for="password">Senha</label>
                        <input type="password" name="password" id="password" required class="op-auth-input"
                               autocomplete="new-password" placeholder="Mín. 8 caracteres">
                    </div>
                    <div>
                        <label class="op-auth-label" for="password_confirmation">Confirmar Senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               required class="op-auth-input" autocomplete="new-password"
                               placeholder="Repita a senha">
                    </div>
                </div>

                <label class="op-auth-check" for="terms">
                    <input type="checkbox" name="terms" id="terms" value="1" required @checked(old('terms'))>
                    <span>
                        Li e aceito os
                        <a href="{{ route('termos') }}" class="op-auth-link" target="_blank" rel="noopener">Termos de Uso</a>
                        e a
                        <a href="{{ route('privacidade') }}" class="op-auth-link" target="_blank" rel="noopener">Política de Privacidade</a>
                    </span>
                </label>

                <button type="submit" class="op-auth-submit">
                    Criar Conta Gratuitamente
                </button>
            </form>

            <div style="text-align:center;margin-top:24px;font-size:.88rem;color:#6b7280;">
                Já tem uma conta? <a href="{{ route('user.login') }}" class="op-auth-link">Entrar</a>
            </div>

        </div>
    </div>

    {{-- RIGHT: Hero --}}
    <div class="auth-split-right"
         style="flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;
                padding:64px 56px;
                background:rgba(124,58,237,0.035);
                border-left:1px solid rgba(255,255,255,.07);
                position:relative;overflow:hidden;">

        <div style="position:absolute;top:5%;right:-10%;width:500px;height:500px;
                    background:radial-gradient(circle,rgba(124,58,237,.1) 0%,transparent 65%);
                    filter:blur(70px);pointer-events:none;"></div>
        <div style="position:absolute;bottom:-10%;left:10%;width:350px;height:350px;
                    background:radial-gradient(circle,rgba(124,58,237,.06) 0%,transparent 65%);
                    filter:blur(60px);pointer-events:none;"></div>

        <div style="max-width:480px;position:relative;z-index:1;width:100%;">

            <div class="section-label" style="margin-bottom:20px;font-size:.72rem;letter-spacing:.1em;">
                Cinco pilares
            </div>

            <h2 class="op-hero-title">
                Tudo que você precisa<br>
                <span class="text-gradient">para escalar seus pagamentos.</span>
            </h2>

            <div style="display:flex;flex-direction:column;gap:20px;margin-bottom:32px;">

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-download"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Receba</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">PIX, Cartão, Boleto e Crypto com liquidação automática.</p>
                    </div>
                </div>

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-plug"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Integre</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">REST API, Webhooks, Sandbox e SDKs prontos para uso.</p>
                    </div>
                </div>

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Gerencie</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">Dashboard, Extrato completo e Relatórios exportáveis.</p>
                    </div>
                </div>

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Proteja</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">KYC automático, Antifraude por IA e 2FA nativo.</p>
                    </div>
                </div>

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Escale</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">Alta disponibilidade, automações e multi-conta incluídos.</p>
                    </div>
                </div>

            </div>

            {{-- Testimonial (compact) --}}
            <div class="op-status-card"
                 style="font-size:.82rem;color:#6b7280;font-style:italic;line-height:1.7;">
                "A migração para a OriginPay reduziu nosso tempo de conciliação de dias para segundos."
                <div style="margin-top:8px;color:#e2e8f0;font-style:normal;font-weight:700;font-size:.78rem;">
                    - CTO, Marketplace Pro
                </div>
            </div>

        </div>
    </div>

</div>

@endsection

