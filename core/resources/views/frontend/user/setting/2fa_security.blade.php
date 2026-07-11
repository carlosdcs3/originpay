@extends('frontend.layouts.user-v2')
@section('title', __('SeguranÃ§a â€” 2FA'))

@section('user_setting_content')

@if(auth()->user()->two_factor_enabled)

    {{-- â”€â”€ 2FA ENABLED STATE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="cfg-section" style="border-color:rgba(124,58,237,0.2);">
        <div class="cfg-section-header" style="background:linear-gradient(135deg,rgba(124,58,237,0.07) 0%,transparent 100%);">
            <div class="cfg-section-icon" style="background:rgba(124,58,237,0.12);">
                <i class="fas fa-shield-alt" style="color:var(--ds-primary-light);"></i>
            </div>
            <div style="flex:1;">
                <p class="cfg-section-title">AutenticaÃ§Ã£o em Dois Fatores</p>
                <p class="cfg-section-sub">Seu acesso estÃ¡ protegido com 2FA</p>
            </div>
            <span style="background:rgba(124,58,237,0.12);color:var(--ds-primary-light);font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:100px;letter-spacing:0.04em;">âœ“ ATIVO</span>
        </div>
        <div class="cfg-section-body">
            <p style="color:#64748b;font-size:0.85rem;line-height:1.6;max-width:480px;margin-bottom:20px;">
                A autenticaÃ§Ã£o em dois fatores estÃ¡ <strong style="color:var(--ds-primary-light);">habilitada</strong> na sua conta.
                Ao desativÃ¡-la, vocÃª removerÃ¡ esta camada extra de seguranÃ§a e sua conta ficarÃ¡ mais vulnerÃ¡vel.
            </p>

            <div style="background:rgba(255,77,106,0.06);border:1px solid rgba(255,77,106,0.15);border-radius:10px;padding:12px 16px;display:flex;gap:10px;align-items:flex-start;max-width:480px;margin-bottom:20px;">
                <i class="fas fa-exclamation-triangle" style="color:#FF4D6A;margin-top:2px;flex-shrink:0;"></i>
                <span style="color:#94a3b8;font-size:0.8rem;line-height:1.5;">
                    {{ __('Disabling 2FA will remove this additional security measure, making your account more vulnerable to unauthorized access.') }}
                </span>
            </div>

            <form action="{{ route('user.2fa.disable') }}" method="POST" style="max-width:480px;">
                @csrf
                <label class="cfg-label" for="disable-password">Confirme sua senha para desativar</label>
                <input type="password" id="disable-password" name="password" class="cfg-input"
                       placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" required style="margin-bottom:16px;">

                <button type="submit" class="ds-btn-submit"
                        style="background:rgba(255,77,106,0.1);color:#FF4D6A;border:1px solid rgba(255,77,106,0.3);width:auto;padding:10px 24px;">
                    <i class="fas fa-shield-alt" style="margin-right:8px;"></i>
                    Desativar 2FA
                </button>
            </form>
        </div>
    </div>

@else

    {{-- â”€â”€ 2FA DISABLED STATE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-section-icon" style="background:rgba(255,193,7,0.1);">
                <i class="fas fa-shield-alt" style="color:#ffc107;"></i>
            </div>
            <div style="flex:1;">
                <p class="cfg-section-title">AutenticaÃ§Ã£o em Dois Fatores</p>
                <p class="cfg-section-sub">Adicione uma camada extra de seguranÃ§a Ã  sua conta</p>
            </div>
            <span style="background:rgba(255,193,7,0.1);color:#ffc107;font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:100px;letter-spacing:0.04em;">INATIVO</span>
        </div>
        <div class="cfg-section-body">
            <p style="color:#64748b;font-size:0.85rem;line-height:1.6;max-width:520px;margin-bottom:24px;">
                Com o 2FA ativo, alÃ©m da senha vocÃª precisarÃ¡ de um cÃ³digo temporÃ¡rio gerado pelo
                <strong style="color:#e2e8f0;">Google Authenticator</strong> para fazer login.
                Isso garante que somente vocÃª consegue acessar a conta, mesmo que sua senha seja comprometida.
            </p>

            {{-- QR Code + Steps --}}
            <div style="display:flex;gap:28px;align-items:flex-start;flex-wrap:wrap;margin-bottom:24px;">
                <div style="background:#fff;padding:12px;border-radius:14px;display:inline-block;flex-shrink:0;">
                    <img src="{{ $qrCode }}" alt="QR Code 2FA"
                         style="width:150px;height:150px;display:block;">
                </div>
                <div style="flex:1;min-width:220px;">
                    <p style="color:#94a3b8;font-size:0.78rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:14px;">Como configurar</p>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <span style="width:22px;height:22px;border-radius:50%;background:rgba(124,58,237,0.1);color:var(--ds-primary-light);font-size:0.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</span>
                            <span style="color:#64748b;font-size:0.82rem;line-height:1.5;">Baixe o <strong style="color:#94a3b8;">Google Authenticator</strong> na App Store ou Play Store</span>
                        </div>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <span style="width:22px;height:22px;border-radius:50%;background:rgba(124,58,237,0.1);color:var(--ds-primary-light);font-size:0.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</span>
                            <span style="color:#64748b;font-size:0.82rem;line-height:1.5;">Abra o app e escaneie o <strong style="color:#94a3b8;">QR Code</strong> ao lado</span>
                        </div>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <span style="width:22px;height:22px;border-radius:50%;background:rgba(124,58,237,0.1);color:var(--ds-primary-light);font-size:0.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</span>
                            <span style="color:#64748b;font-size:0.82rem;line-height:1.5;">Digite o cÃ³digo de 6 dÃ­gitos gerado abaixo</span>
                        </div>
                    </div>

                    <div style="margin-top:16px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:9px;padding:10px 14px;">
                        <p style="color:#475569;font-size:0.7rem;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:4px;">Chave Manual</p>
                        <code style="color:var(--ds-primary-light);font-size:0.82rem;letter-spacing:0.05em;word-break:break-all;">{{ $secret }}</code>
                    </div>
                </div>
            </div>

            {{-- Activation form --}}
            <form action="{{ route('user.2fa.enable') }}" method="POST" style="max-width:480px;border-top:1px solid rgba(255,255,255,0.05);padding-top:20px;">
                @csrf
                <label class="cfg-label" for="verification_code">CÃ³digo de verificaÃ§Ã£o (6 dÃ­gitos)</label>
                <input type="text" id="verification_code" name="verification_code" class="cfg-input"
                       placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                       style="letter-spacing:0.2em;font-size:1.1rem;margin-bottom:16px;" required>

                <div class="cfg-save-row">
                    <button type="submit" class="ds-btn-submit"
                            style="background:var(--ds-primary-light);color:#fff;width:auto;padding:10px 28px;">
                        <i class="fas fa-check" style="margin-right:8px;"></i>
                        Ativar 2FA
                    </button>
                </div>
            </form>
        </div>
    </div>

@endif

@endsection