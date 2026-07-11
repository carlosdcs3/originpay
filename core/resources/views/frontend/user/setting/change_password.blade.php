@extends('frontend.layouts.user-v2')
@section('title', __('Alterar Senha'))

@section('user_setting_content')
<form action="{{ route('user.settings.password.update') }}" method="POST">
    @csrf

    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-section-icon" style="background:rgba(255,77,106,0.1);">
                <i class="fas fa-lock" style="color:#FF4D6A;"></i>
            </div>
            <div>
                <p class="cfg-section-title">Alterar Senha</p>
                <p class="cfg-section-sub">Use uma senha forte com pelo menos 8 caracteres</p>
            </div>
        </div>
        <div class="cfg-section-body">

            <div class="cfg-field-row full" style="max-width:480px;">
                <div>
                    <label class="cfg-label" for="old-password">Senha Atual</label>
                    <input type="password" name="old_password" id="old-password" class="cfg-input"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <div class="cfg-field-row" style="max-width:480px;">
                <div>
                    <label class="cfg-label" for="password">Nova Senha</label>
                    <input type="password" name="password" id="password" class="cfg-input"
                           placeholder="••••••••" required autocomplete="new-password">
                </div>
                <div>
                    <label class="cfg-label" for="password_confirmation">Confirmar Nova Senha</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="cfg-input"
                           placeholder="••••••••" required autocomplete="new-password">
                </div>
            </div>

            {{-- Tips --}}
            <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:10px;padding:14px 16px;margin-top:4px;margin-bottom:16px;max-width:480px;">
                <p style="color:#64748b;font-size:0.75rem;font-weight:600;margin:0 0 8px;letter-spacing:0.04em;text-transform:uppercase;">Dicas de segurança</p>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:5px;">
                    <li style="color:#64748b;font-size:0.78rem;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-circle" style="font-size:0.35rem;color:#475569;"></i>
                        Mínimo de 8 caracteres
                    </li>
                    <li style="color:#64748b;font-size:0.78rem;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-circle" style="font-size:0.35rem;color:#475569;"></i>
                        Misture letras maiúsculas, minúsculas e números
                    </li>
                    <li style="color:#64748b;font-size:0.78rem;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-circle" style="font-size:0.35rem;color:#475569;"></i>
                        Evite reutilizar senhas de outros serviços
                    </li>
                </ul>
            </div>

            <div class="cfg-save-row">
                <button type="submit" class="ds-btn-submit"
                        style="background:var(--ds-primary-light);color:#fff;width:auto;padding:10px 28px;">
                    <i class="fas fa-save" style="margin-right:8px;"></i> Atualizar Senha
                </button>
            </div>
        </div>
    </div>
</form>
@endsection