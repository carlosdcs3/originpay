@extends('frontend.layouts.auth')

@section('title', 'Verificar e-mail')

@push('styles')
<style>
.op-verify-shell {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
}

.op-verify-card {
    width: 100%;
    max-width: 560px;
    padding: 32px;
    border-radius: 24px;
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 30px 80px rgba(2, 6, 23, 0.45);
}

.op-verify-badge {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(124, 58, 237, 0.18);
    color: #a78bfa;
    font-size: 1.25rem;
    margin-bottom: 18px;
}

.op-verify-title {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 10px;
    line-height: 1.15;
}

.op-verify-copy {
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.65;
    margin-bottom: 0;
}

.op-verify-actions {
    display: grid;
    gap: 12px;
    margin-top: 28px;
}

.op-auth-success {
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.2);
    color: #86efac;
    font-size: 0.9rem;
    line-height: 1.5;
}

.op-verify-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    min-height: 48px;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.03);
    color: var(--text-primary);
    font-weight: 600;
}

.op-verify-note {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    color: #7c8499;
    font-size: 0.84rem;
    line-height: 1.6;
}
</style>
@endpush

@section('auth-content')
    <div class="op-verify-shell">
        <div class="op-verify-card">
            <div class="text-center">
                <a href="{{ route('home') }}" style="display:inline-block;margin-bottom:28px;">
                    <img src="{{ asset('frontend/images/originpay/originpay-logo-horizontal-dark.svg') }}" alt="OriginPay" style="height:40px;width:auto;">
                </a>
                <div class="op-verify-badge">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h1 class="op-verify-title">Confirme seu e-mail</h1>
                <p class="op-verify-copy">
                    Enviamos um link de verificacao para <strong>{{ auth()->user()->email }}</strong>.
                    Depois da confirmacao, o acesso completo ao painel da OriginPay e liberado.
                </p>
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="op-auth-success" style="margin-top:24px;">
                    Um novo link de verificacao foi enviado para o seu e-mail.
                </div>
            @endif

            <div class="op-verify-actions">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="op-auth-submit">
                        Reenviar e-mail de verificacao
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="op-verify-secondary">
                        Sair desta conta
                    </button>
                </form>
            </div>

            <div class="op-verify-note">
                Se o e-mail nao aparecer em alguns minutos, verifique spam e promocoes. Depois de validar o endereco, voce pode voltar normalmente para o painel.
            </div>
        </div>
    </div>
@endsection
