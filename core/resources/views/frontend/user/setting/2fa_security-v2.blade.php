@extends('frontend.user.setting.index')
@section('title', __('Segurança - 2FA'))

@section('user_setting_content')

<style>
@media (max-width: 768px) {
    .twofa-alert {
        padding: 10px 12px !important;
        border-radius: 10px !important;
        gap: 10px !important;
        margin-bottom: 14px !important;
    }

    .twofa-alert i {
        font-size: .9rem !important;
    }

    .twofa-alert div {
        font-size: .78rem !important;
        line-height: 1.35 !important;
    }

    .twofa-copy {
        font-size: .74rem !important;
        line-height: 1.38 !important;
        margin-bottom: 12px !important;
    }

    .twofa-setup {
        display: grid !important;
        grid-template-columns: 96px minmax(0, 1fr) !important;
        gap: 10px !important;
        margin-bottom: 12px !important;
        align-items: start !important;
    }

    .twofa-qr-wrap {
        padding: 7px !important;
        border-radius: 10px !important;
    }

    .twofa-qr-wrap img {
        width: 82px !important;
        height: 82px !important;
    }

    .twofa-steps-title {
        margin-bottom: 8px !important;
        font-size: .62rem !important;
    }

    .twofa-steps {
        gap: 7px !important;
    }

    .twofa-step {
        display: grid !important;
        grid-template-columns: 18px minmax(0, 1fr) !important;
        gap: 7px !important;
        align-items: start !important;
        min-width: 0 !important;
    }

    .twofa-step-number {
        width: 18px !important;
        height: 18px !important;
        font-size: .62rem !important;
        flex: 0 0 18px !important;
    }

    .twofa-step-text {
        display: block !important;
        min-width: 0 !important;
        max-width: 100% !important;
        font-size: .68rem !important;
        line-height: 1.28 !important;
        overflow-wrap: anywhere !important;
    }

    .twofa-secret {
        margin-top: 10px !important;
        padding: 9px 11px !important;
        border-radius: 10px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    .twofa-secret p {
        margin-bottom: 5px !important;
        font-size: .66rem !important;
    }

    .twofa-secret code {
        font-size: .7rem !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
    }

    .twofa-form {
        padding-top: 12px !important;
    }

    .twofa-form .v2-input {
        height: 38px !important;
        border-radius: 8px !important;
        font-size: .86rem !important;
    }

    .twofa-form > div {
        margin-bottom: 12px !important;
    }

    .twofa-footer {
        padding: 10px 0 0 !important;
        border-top: 0 !important;
    }

    .twofa-footer .v2-btn-primary {
        min-height: 38px !important;
        height: 38px !important;
        max-width: 260px !important;
        margin: 0 auto !important;
    }
}
</style>

<div class="v2-page-header" style="margin-bottom: 28px;">
    <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px; color: var(--ds-text-main);">Autenticação em Dois Fatores</h2>
    <p class="v2-page-subtitle" style="font-size: 0.9375rem; color: var(--ds-text-muted); margin: 0;">Adicione uma camada extra de proteção ao seu acesso.</p>
</div>

@if(auth()->user()->two_factor_enabled)

    <div class="v2-settings-card" style="border-color:rgba(124,58,237,0.2);">
        <div class="v2-settings-header" style="background:linear-gradient(135deg,rgba(124,58,237,0.07) 0%,transparent 100%);">
            <div class="v2-settings-header-icon" style="background:rgba(124,58,237,0.08); border: 1px solid rgba(124, 58, 237, 0.15); color:var(--ds-primary-light);">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div style="flex:1;">
                <p class="v2-settings-title">Autenticação em Dois Fatores</p>
                <p class="v2-settings-desc">Seu acesso está protegido com 2FA</p>
            </div>
            <span style="background:rgba(124,58,237,0.12);color:var(--ds-primary-light);font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:32px;letter-spacing:0.04em;">ATIVO</span>
        </div>
        <div class="v2-settings-body">
            <p class="twofa-copy" style="color:var(--ds-text-muted);font-size:0.85rem;line-height:1.6;max-width:480px;margin-bottom:24px;">
                A autenticação em dois fatores está <strong style="color:var(--ds-primary-light);">habilitada</strong> na sua conta.
                Ao desativá-la, você removerá esta camada extra de segurança e sua conta ficará mais vulnerável.
            </p>

            <div class="twofa-alert" style="background:rgba(239, 68, 68, 0.06);border:1px solid rgba(239, 68, 68, 0.15);border-radius:12px;padding:16px;display:flex;gap:12px;align-items:flex-start;max-width:480px;margin-bottom:24px;">
                <i class="fas fa-exclamation-triangle" style="color:#ef4444;margin-top:2px;flex-shrink:0;"></i>
                <span style="color:rgba(255,255,255,0.7);font-size:0.8rem;line-height:1.5;">
                    Desativar o 2FA remove esta proteção adicional e pode deixar sua conta mais vulnerável.
                </span>
            </div>

            <form action="{{ route('user.2fa.disable') }}" method="POST" style="max-width:480px;">
                @csrf
                <div style="margin-bottom:24px;">
                    <label class="v2-label" for="disable-password">Confirme sua senha para desativar</label>
                    <input type="password" id="disable-password" name="password" class="v2-input"
                           placeholder="********" style="border-radius:12px;" required>
                </div>

                <button type="submit" class="v2-btn-outline" style="border-color: rgba(239, 68, 68, 0.3); color: #ef4444; border-radius: 12px; padding: 12px 24px;">
                    <i class="fas fa-shield-alt" style="margin-right:8px;"></i>
                    Desativar 2FA
                </button>
            </form>
        </div>
    </div>

@else

    <div class="twofa-alert" style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); padding: 12px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <i class="fas fa-exclamation-circle" style="color: var(--ds-warning); font-size: 1.1rem;"></i>
        <div style="font-weight: 500; color: var(--ds-warning); font-size: 0.85rem;">Sua conta ainda não possui autenticação em dois fatores.</div>
    </div>

    <div class="v2-settings-card">
        <div class="v2-settings-header">
            <div class="v2-settings-header-icon" style="background:rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.15); color:var(--ds-primary-light);">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div style="flex:1;">
                <p class="v2-settings-title">Autenticação em Dois Fatores</p>
                <p class="v2-settings-desc">Adicione uma camada extra de segurança à sua conta</p>
            </div>
            <span style="background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.4);font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:32px;letter-spacing:0.04em;">INATIVO</span>
        </div>
        <div class="v2-settings-body">
            <p class="twofa-copy" style="color:var(--ds-text-muted);font-size:0.85rem;line-height:1.6;max-width:520px;margin-bottom:24px;">
                Com o 2FA ativo, além da senha você precisará de um código temporário gerado pelo
                <strong style="color:rgba(255,255,255,0.9);">Google Authenticator</strong> para fazer login.
                Isso garante que somente você consegue acessar a conta, mesmo que sua senha seja comprometida.
            </p>

            <div class="twofa-setup" style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;margin-bottom:32px;">
                <div class="twofa-qr-wrap" style="background:#fff;padding:12px;border-radius:12px;display:inline-block;flex-shrink:0;">
                    <img src="{{ $qrCode }}" alt="QR Code 2FA"
                         style="width:150px;height:150px;display:block;">
                </div>
                <div style="flex:1;min-width:220px;">
                    <p class="twofa-steps-title" style="color:rgba(255,255,255,0.7);font-size:0.78rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:16px;">Como configurar</p>
                    <div class="twofa-steps" style="display:flex;flex-direction:column;gap:16px;">
                        <div class="twofa-step" style="display:flex;gap:12px;align-items:flex-start;">
                            <span class="twofa-step-number" style="width:24px;height:24px;border-radius:50%;background:rgba(124,58,237,0.1);color:var(--ds-primary-light);font-size:0.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</span>
                            <span class="twofa-step-text" style="color:var(--ds-text-muted);font-size:0.85rem;line-height:1.5;">Baixe o <strong style="color:rgba(255,255,255,0.8);">Google Authenticator</strong> na App Store ou Play Store</span>
                        </div>
                        <div class="twofa-step" style="display:flex;gap:12px;align-items:flex-start;">
                            <span class="twofa-step-number" style="width:24px;height:24px;border-radius:50%;background:rgba(124,58,237,0.1);color:var(--ds-primary-light);font-size:0.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</span>
                            <span class="twofa-step-text" style="color:var(--ds-text-muted);font-size:0.85rem;line-height:1.5;">Abra o app e escaneie o <strong style="color:rgba(255,255,255,0.8);">QR Code</strong> ao lado</span>
                        </div>
                        <div class="twofa-step" style="display:flex;gap:12px;align-items:flex-start;">
                            <span class="twofa-step-number" style="width:24px;height:24px;border-radius:50%;background:rgba(124,58,237,0.1);color:var(--ds-primary-light);font-size:0.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</span>
                            <span class="twofa-step-text" style="color:var(--ds-text-muted);font-size:0.85rem;line-height:1.5;">Digite o código abaixo</span>
                        </div>
                    </div>

                    <div class="twofa-secret" style="margin-top:24px;background:rgba(255,255,255,0.02);border:1px solid var(--ds-border-medium);border-radius:12px;padding:16px;">
                        <p style="color:rgba(255,255,255,0.5);font-size:0.75rem;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:8px;">Chave Manual</p>
                        <code style="color:var(--ds-primary-light);font-size:0.85rem;letter-spacing:0.05em;word-break:break-all;">{{ $secret }}</code>
                    </div>
                </div>
            </div>

            <form class="twofa-form" action="{{ route('user.2fa.enable') }}" method="POST" style="max-width:480px;border-top:1px solid var(--ds-border-medium);padding-top:24px;">
                @csrf
                <div style="margin-bottom:24px;">
                    <label class="v2-label" for="verification_code">Código de verificação (6 dígitos)</label>
                    <input type="text" id="verification_code" name="verification_code" class="v2-input"
                           placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                           style="letter-spacing:0.2em;font-size:1.1rem;border-radius:12px;" required>
                </div>

                <div class="v2-settings-footer twofa-footer">
                    <button type="submit" class="v2-btn-primary">
                        <i class="fas fa-check" style="margin-right:8px;"></i>
                        Ativar 2FA
                    </button>
                </div>
            </form>
        </div>
    </div>

@endif

@endsection
