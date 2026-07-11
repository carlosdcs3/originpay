@extends('frontend.layouts.auth')
@section('title', 'Entrar na conta merchant')

@push('styles')
<style>
@keyframes op-fadein {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.op-auth-page { animation: op-fadein .35s cubic-bezier(.4,0,.2,1) both; }

.op-form-glow {
    position: absolute;
    width: 480px;
    height: 480px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,.07) 0%, transparent 70%);
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

.op-eye-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #4a5068;
    font-size: .9rem;
    padding: 0;
}

.op-eye-btn:hover { color: #a78bfa; }

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

@media (max-width: 768px) {
    .auth-split-right { display: none !important; }
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
        <div class="op-auth-card">
            <div style="text-align:center;margin-bottom:32px;">
                <a href="{{ route('home') }}" style="display:inline-block;margin-bottom:32px;text-decoration:none;">
                    <img src="{{ asset('frontend/images/originpay/originpay-logo-horizontal-dark.svg') }}" alt="OriginPay" style="height:44px;width:auto;">
                </a>
                <div class="op-pill" style="margin-bottom:18px;">
                    <i class="fas fa-store"></i>
                    Painel merchant
                </div>
                <h1 class="op-h1">Entrar na sua operação</h1>
                <p class="op-subtitle">Acesse cobranças, links de pagamento, saques e integrações da sua conta OriginPay.</p>
            </div>

            @if ($errors->any())
                <div class="op-auth-error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('merchant.login') }}" method="POST">
                @csrf

                <div style="margin-bottom:20px;">
                    <label class="op-auth-label" for="login">E-mail ou usuário</label>
                    <input type="text" name="login" id="login" class="op-auth-input" placeholder="voce@empresa.com" autocomplete="username" required>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="op-auth-label" for="password">Senha</label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="password" class="op-auth-input" placeholder="Digite sua senha" autocomplete="current-password" required>
                        <button type="button" class="op-eye-btn" id="togglePassword" aria-label="Mostrar senha">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin:0 0 24px;flex-wrap:wrap;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;color:#7c8499;cursor:pointer;">
                        <input type="checkbox" name="remember" value="1" style="accent-color:#7c3aed;">
                        Manter conectado
                    </label>
                    <a href="{{ route('merchant.password.request') }}" class="op-auth-link">Esqueci minha senha</a>
                </div>

                @if(config('services.recaptcha.status'))
                    <div class="g-recaptcha mt-4 mb-4" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
                @endif

                <button class="op-auth-submit" type="submit">
                    Entrar no painel merchant
                </button>
            </form>

            <div style="text-align:center;margin-top:24px;font-size:.88rem;color:#7c8499;">
                Ainda não tem conta?
                <a href="{{ route('merchant.register') }}" class="op-auth-link">Criar conta merchant</a>
            </div>

            <div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);font-size:.84rem;color:#7c8499;">
                Usa a OriginPay como usuário comum?
                <a href="{{ route('user.login') }}" class="op-auth-link">Entrar como usuário</a>
            </div>
        </div>
    </div>

    <div class="auth-split-right" style="flex:1;display:flex;align-items:center;justify-content:center;padding:48px 56px;position:relative;">
        <div style="max-width:520px;">
            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8b5cf6;margin-bottom:16px;">Painel operacional do merchant</div>
            <h2 class="op-side-title">Tudo o que sua operação precisa, em poucos cliques.</h2>

            <div class="op-side-list">
                <div class="op-side-item">
                    <div class="op-side-icon"><i class="fas fa-link"></i></div>
                    <div>
                        <h3>Checkout e links de pagamento</h3>
                        <p>Crie cobranças, compartilhe links e acompanhe o status sem trocar de sistema.</p>
                    </div>
                </div>
                <div class="op-side-item">
                    <div class="op-side-icon"><i class="fas fa-code"></i></div>
                    <div>
                        <h3>API keys e webhooks</h3>
                        <p>Integre seu ERP, loja ou backend com credenciais organizadas por ambiente.</p>
                    </div>
                </div>
                <div class="op-side-item">
                    <div class="op-side-icon"><i class="fas fa-arrow-down"></i></div>
                    <div>
                        <h3>Saques com visibilidade</h3>
                        <p>Consulte saldo, acompanhe validações e saiba exatamente quando sacar.</p>
                    </div>
                </div>
            </div>

            <div class="op-status-card">
                <div class="op-status-row">
                    <span class="op-status-lbl">Ambiente</span>
                    <span class="op-status-val-green">Sandbox e produção</span>
                </div>
                <div class="op-status-row">
                    <span class="op-status-lbl">Integrações</span>
                    <span class="op-status-val-white">API, webhooks, links</span>
                </div>
                <div class="op-status-row">
                    <span class="op-status-lbl">Operação</span>
                    <span class="op-status-val-white">Cobrança, saldo e saque</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script async src="https://www.google.com/recaptcha/api.js"></script>
@endpush
