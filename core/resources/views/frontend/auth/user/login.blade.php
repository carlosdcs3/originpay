@extends('frontend.layouts.auth')
@section('title', __('Entrar'))

@push('styles')
<style>
/* ═══════════════════════════════════════
   AUTH SHARED PREMIUM — Login & Register
═══════════════════════════════════════ */

/* Fade-in on load */
@keyframes op-fadein {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.op-auth-page { animation: op-fadein .35s cubic-bezier(.4,0,.2,1) both; }

/* Pulse dot */
@keyframes op-pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,.5); }
    50%      { box-shadow: 0 0 0 5px rgba(16,185,129,0); }
}

/* Subtle glow behind the form */
.op-form-glow {
    position: absolute;
    width: 480px; height: 480px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,0.07) 0%, transparent 70%);
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    pointer-events: none;
    z-index: 0;
}

/* ── Typography overrides ── */
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
    color: #7c8499;   /* higher contrast than --text-muted */
    line-height: 1.55;
    margin-bottom: 0;
}

/* ── Inputs ── */
.op-auth-input {
    width: 100%;
    padding: 13px 16px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04);
    color: var(--text-primary);
    font-size: .93rem;
    font-family: var(--font);
    outline: none;
    transition: border-color .2s cubic-bezier(.4,0,.2,1),
                box-shadow .2s cubic-bezier(.4,0,.2,1),
                background .2s;
}
.op-auth-input::placeholder { color: #4a5068; }
.op-auth-input:focus {
    border-color: rgba(124,58,237,.65);
    background: rgba(124,58,237,.04);
    box-shadow: 0 0 0 3px rgba(124,58,237,.13);
}
/* Chrome autofill — stay dark */
.op-auth-input:-webkit-autofill,
.op-auth-input:-webkit-autofill:hover,
.op-auth-input:-webkit-autofill:focus {
    -webkit-text-fill-color: var(--text-primary) !important;
    -webkit-box-shadow: 0 0 0 1000px #0f1022 inset !important;
    transition: background-color 5000s ease-in-out 0s;
}

/* ── Labels ── */
.op-auth-label {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    color: #8892a4;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .06em;
}

/* ── Button ── */
.op-auth-submit {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    color: #fff;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: .01em;
    position: relative;
    overflow: hidden;
    transition: transform .2s cubic-bezier(.4,0,.2,1),
                box-shadow .2s cubic-bezier(.4,0,.2,1);
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
.op-auth-submit:disabled { opacity: .55; cursor: not-allowed; transform: none; }

/* ── Links ── */
.op-auth-link {
    color: #9f7aea;
    text-decoration: none;
    font-weight: 600;
    transition: color .2s cubic-bezier(.4,0,.2,1);
}
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

/* ── Error ── */
.op-auth-error {
    background: rgba(239,68,68,.08);
    border: 1px solid rgba(239,68,68,.18);
    border-radius: 12px;
    padding: 13px 16px;
    margin-bottom: 24px;
    color: #fca5a5;
    font-size: .85rem;
    line-height: 1.5;
}

/* ── Card (max-width wrapper) ── */
.op-auth-card     { width: 100%; max-width: 420px; position: relative; z-index: 1; }
.op-auth-card-wide{ width: 100%; max-width: 520px; position: relative; z-index: 1; }

/* ── Eye toggle ── */
.op-eye-btn {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #4a5068; font-size: .9rem; padding: 0; line-height: 1;
    transition: color .2s cubic-bezier(.4,0,.2,1);
}
.op-eye-btn:hover { color: #a78bfa; }

/* ── Right column feature items ── */
.op-feat-row {
    display: flex;
    gap: 16px;
    align-items: flex-start;
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
    color: #a78bfa; font-size: .9rem;
    flex-shrink: 0;
    transition: background .2s, box-shadow .2s;
}

/* ── Status card ── */
.op-status-card {
    padding: 14px 18px;
    background: rgba(255,255,255,.03);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(0,0,0,.25);
}
.op-status-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .78rem; padding: 6px 0;
    transition: opacity .2s;
}
.op-status-row:not(:last-child) { border-bottom: 1px solid rgba(255,255,255,.06); }
.op-status-row:hover { opacity: .8; }
.op-status-lbl { color: #6b7280; }
.op-status-val-green { color: #34d399; font-weight: 600; }
.op-status-val-white { color: #e2e8f0; font-weight: 600; }

/* ── Checkbox ── */
.op-checkbox-label {
    display: flex; align-items: center; gap: 9px;
    font-size: .85rem; color: #7c8499;
    cursor: pointer; user-select: none;
    transition: color .2s;
}
.op-checkbox-label:hover { color: #a0aec0; }
.op-checkbox-label input[type="checkbox"] {
    accent-color: #7c3aed; width: 15px; height: 15px; cursor: pointer;
}

/* ── Hero title right column ── */
.op-hero-title {
    font-size: clamp(2rem, 3.2vw, 3.2rem);
    font-weight: 800;
    letter-spacing: -.04em;
    line-height: 1.05;
    margin-bottom: 32px;
    color: var(--text-primary);
}

/* ── Background orbs ── */
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

@media (max-width: 768px) {
    .auth-split-right { display: none !important; }
}

@keyframes opBtnPulse {
    0% { transform: scale(0.9); opacity: 0.6; }
    50% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.6; }
}
</style>
@endpush

@section('auth-content')

<div style="position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0;">
    <div class="op-orb-1"></div>
    <div class="op-orb-2"></div>
</div>

<div class="op-auth-page" style="display:flex;min-height:100vh;width:100%;position:relative;z-index:1;">

    {{-- ══ LEFT: Form ══ --}}
    <div style="flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:40px 24px;position:relative;">
        <div class="op-form-glow"></div>
        <div class="op-auth-card">

            {{-- Logo + Heading (single block, no duplicate) --}}
            <div style="text-align:center;margin-bottom:32px;">
                <a href="{{ route('home') }}" class="op-auth-brand" aria-label="OriginPay">
                    <span class="op-auth-brand-word">Origin<span>Pay</span></span>
                    <span class="op-auth-brand-tagline">Sua gateway sem limites.</span>
                </a>
                <h1 class="op-h1">Bem-vindo de volta</h1>
                <p class="op-subtitle">Acesse sua conta para gerenciar sua operação.</p>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="op-auth-error">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            {{-- Form — name/id/action/csrf untouched --}}
            <form action="{{ route('user.login') }}" method="post">
                @csrf

                {{-- E-mail --}}
                <div style="margin-bottom:24px;">
                    <label class="op-auth-label" for="login">E-mail ou Usuário</label>
                    <input type="text" name="login" id="login" required class="op-auth-input"
                           value="{{ old('login') }}" autocomplete="username"
                           placeholder="seu@email.com">
                </div>

                {{-- Password --}}
                <div style="margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <label class="op-auth-label" for="password" style="margin-bottom:0;">Senha</label>
                        <a href="{{ route('user.password.request') }}" class="op-auth-link"
                           style="font-size:.78rem;font-weight:600;">Esqueceu a senha?</a>
                    </div>
                    <div style="position:relative;">
                        <input type="password" name="password" id="password" required class="op-auth-input"
                               autocomplete="current-password" placeholder="••••••••"
                               style="padding-right:46px;">
                        <button type="button" class="op-eye-btn"
                                onclick="var i=document.getElementById('password');var ico=document.getElementById('eye-ico');if(i.type==='password'){i.type='text';ico.className='far fa-eye-slash';}else{i.type='password';ico.className='far fa-eye';}">
                            <i class="far fa-eye" id="eye-ico"></i>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <div style="margin-bottom:32px;">
                    <label class="op-checkbox-label" for="rememberMe">
                        <input type="checkbox" name="remember" id="rememberMe">
                        Manter-me conectado neste dispositivo por 7 dias
                    </label>
                </div>

                @if(config('services.recaptcha.status'))
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"
                         style="margin-bottom:24px;display:flex;justify-content:center;"></div>
                @endif

                <button type="button" class="op-auth-submit" id="login-submit"
                        onclick="handleLoginSubmit(this)">
                    Entrar na Plataforma
                </button>
            </form>

            <div style="text-align:center;margin-top:24px;font-size:.88rem;color:#6b7280;">
                Não tem uma conta?&nbsp;<a href="{{ route('user.register') }}" class="op-auth-link">Criar conta grátis</a>
            </div>

        </div>
    </div>

    {{-- ══ RIGHT: Hero ══ --}}
    <div class="auth-split-right"
         style="flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;
                padding:64px 56px;
                background:rgba(124,58,237,0.035);
                border-left:1px solid rgba(255,255,255,.07);
                position:relative;overflow:hidden;">

        {{-- Hero glow --}}
        <div style="position:absolute;top:5%;right:-10%;width:500px;height:500px;
                    background:radial-gradient(circle,rgba(124,58,237,.1) 0%,transparent 65%);
                    filter:blur(70px);pointer-events:none;"></div>
        <div style="position:absolute;bottom:-10%;left:10%;width:350px;height:350px;
                    background:radial-gradient(circle,rgba(124,58,237,.06) 0%,transparent 65%);
                    filter:blur(60px);pointer-events:none;"></div>

        <div style="max-width:480px;position:relative;z-index:1;width:100%;">

            {{-- Label --}}
            <div class="section-label" style="margin-bottom:20px;font-size:.72rem;letter-spacing:.1em;">
                Infraestrutura Real
            </div>

            {{-- Hero title --}}
            <h2 class="op-hero-title">
                Sua operação financeira,<br>
                <span class="text-gradient">sem limites.</span>
            </h2>

            {{-- Feature list --}}
            <div style="display:flex;flex-direction:column;gap:20px;margin-bottom:32px;">

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Segurança Multicamada</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">KYC, Antifraude por IA, 2FA e criptografia em todos os dados.</p>
                    </div>
                </div>

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-server"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Alta Disponibilidade</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">Infraestrutura multi-região com failover automático e SLA 99.99%.</p>
                    </div>
                </div>

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-fingerprint"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">Idempotência Nativa</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">Cobranças jamais duplicadas. Proteção contra falhas de rede.</p>
                    </div>
                </div>

                <div class="op-feat-row">
                    <div class="op-pillar-feature-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:3px;color:#e2e8f0;">PIX em Tempo Real</div>
                        <p style="color:#6b7280;font-size:.79rem;margin:0;line-height:1.55;">Liquidação instantânea 24/7. QR Code dinâmico com vencimento.</p>
                    </div>
                </div>

            </div>

            {{-- Status card (compact) --}}
            <div class="op-status-card">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span style="width:7px;height:7px;border-radius:50%;background:#10b981;
                                 animation:op-pulse 2s infinite;flex-shrink:0;"></span>
                    <span style="font-size:.7rem;color:#6b7280;font-weight:700;
                                 text-transform:uppercase;letter-spacing:.08em;">Sistema Operacional</span>
                </div>
                <div class="op-status-row">
                    <span class="op-status-lbl">API Principal</span>
                    <span class="op-status-val-green">99.99% uptime</span>
                </div>
                <div class="op-status-row">
                    <span class="op-status-lbl">Motor PIX</span>
                    <span class="op-status-val-green">Online</span>
                </div>
                <div class="op-status-row">
                    <span class="op-status-lbl">Latência Média</span>
                    <span class="op-status-val-white">&lt; 45ms</span>
                </div>
            </div>

        </div>
    </div>

</div>

@endsection

@push('scripts')
    <script>
        function handleLoginSubmit(btn) {
            if(btn.disabled) return false;
            btn.disabled = true;
            btn.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;"><svg width="14" height="14" viewBox="0 0 14 14" style="margin-right:10px;"><circle cx="7" cy="7" r="5" fill="rgba(255,255,255,0.9)"><animate attributeName="r" values="5; 7; 5" dur="1.4s" repeatCount="indefinite" /><animate attributeName="opacity" values="0.6; 1; 0.6" dur="1.4s" repeatCount="indefinite" /></circle></svg>Entrando...</div>';
            btn.closest('form').submit();
        }
    </script>
    @if(config('services.recaptcha.status'))
        <script async src="https://www.google.com/recaptcha/api.js"></script>
    @endif
@endpush
