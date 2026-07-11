@extends('frontend.user.setting.index')
@section('title', __('Alterar Senha'))

@section('user_setting_content')

<style>
@media (max-width: 768px) {
    .security-form-stack {
        gap: 12px !important;
    }

    .security-tips {
        padding: 12px !important;
        margin-top: 14px !important;
        border-radius: 10px !important;
    }

    .security-tips-title {
        font-size: .68rem !important;
        margin-bottom: 10px !important;
    }

    .security-tips-list {
        gap: 8px !important;
    }

    .security-tip-item {
        align-items: flex-start !important;
        gap: 9px !important;
        font-size: .78rem !important;
        line-height: 1.35 !important;
    }

    .security-tip-item i {
        flex: 0 0 14px !important;
        margin-top: 2px !important;
        font-size: .78rem !important;
    }
}
</style>

<div class="v2-page-header" style="margin-bottom: 28px;">
    <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px; color: var(--ds-text-main);">Alterar Senha</h2>
    <p class="v2-page-subtitle" style="font-size: 0.9375rem; color: var(--ds-text-muted); margin: 0;">Mantenha sua conta segura com uma senha forte e única.</p>
</div>

<form action="{{ route('user.settings.password.update') }}" method="POST">
    @csrf

    <div class="v2-settings-card">
        <div class="v2-settings-header">
            <div class="v2-settings-header-icon" style="background:rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.15); color:var(--ds-primary-light);">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <p class="v2-settings-title">Senha de Acesso</p>
                <p class="v2-settings-desc">Use uma senha forte com pelo menos 8 caracteres, letras e números</p>
            </div>
        </div>
        <div class="v2-settings-body">

            <div class="security-form-stack" style="max-width: 480px; display: flex; flex-direction: column; gap: 20px;">
                <div>
                    <label class="v2-label" for="old-password">Senha Atual</label>
                    <input type="password" name="old_password" id="old-password" class="v2-input"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>

                <div>
                    <label class="v2-label" for="password">Nova Senha</label>
                    <input type="password" name="password" id="password" class="v2-input"
                           placeholder="••••••••" required autocomplete="new-password">
                </div>

                <div>
                    <label class="v2-label" for="password_confirmation">Confirmar Nova Senha</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="v2-input"
                           placeholder="••••••••" required autocomplete="new-password">
                </div>
            </div>

            {{-- Tips --}}
            <div class="security-tips" style="background:rgba(255,255,255,0.02); border:1px solid var(--ds-border-light); border-radius:12px; padding:20px 24px; margin-top:28px; max-width:480px;">
                <p class="security-tips-title" style="color: var(--ds-text-muted); font-size: 0.75rem; font-weight: 700; margin: 0 0 12px; letter-spacing: 0.06em; text-transform: uppercase;">Dicas de segurança</p>
                <ul class="security-tips-list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <li class="security-tip-item" style="color: var(--ds-text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: var(--ds-primary-light); font-size: 0.875rem;"></i>
                        Mínimo de 8 caracteres
                    </li>
                    <li class="security-tip-item" style="color: var(--ds-text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: var(--ds-primary-light); font-size: 0.875rem;"></i>
                        Misture letras maiúsculas, minúsculas e números
                    </li>
                    <li class="security-tip-item" style="color: var(--ds-text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: var(--ds-primary-light); font-size: 0.875rem;"></i>
                        Evite reutilizar senhas de outros serviços
                    </li>
                </ul>
            </div>
        </div>
        <div class="v2-settings-footer">
            <button type="submit" class="v2-btn-primary" data-tp-confirm="true">
                <i class="fas fa-save" style="margin-right: 8px;"></i> Atualizar Senha
            </button>
        </div>
    </div>
</form>

<form action="{{ route('user.transaction-password.update') }}" method="POST" style="margin-top: 28px;">
    @csrf

    <div class="v2-settings-card">
        <div class="v2-settings-header">
            <div class="v2-settings-header-icon" style="background:rgba(220, 38, 38, 0.08); border: 1px solid rgba(220, 38, 38, 0.15); color:#DC2626;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <p class="v2-settings-title">Senha Transacional</p>
                <p class="v2-settings-desc">Utilizada para confirmar transferências, geração de API Keys e operações sensíveis.</p>
            </div>
        </div>
        <div class="v2-settings-body">

            <div class="security-form-stack" style="max-width: 480px; display: flex; flex-direction: column; gap: 20px;">
                <div>
                    <label class="v2-label" for="tp-current-password">Senha de login atual</label>
                    <input type="password" name="current_password" id="tp-current-password" class="v2-input"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>

                <div>
                    <label class="v2-label" for="tp-current-tp">Senha transacional atual</label>
                    <input type="password" name="current_transaction_password" id="tp-current-tp" class="v2-input js-transaction-pin"
                           placeholder="••••" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off">
                </div>

                <div>
                    <label class="v2-label" for="tp-new-tp">Nova senha transacional</label>
                    <input type="password" name="transaction_password" id="tp-new-tp" class="v2-input js-transaction-pin"
                           placeholder="••••" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off">
                </div>

                <div>
                    <label class="v2-label" for="tp-new-tp-confirm">Confirmar nova senha transacional</label>
                    <input type="password" name="transaction_password_confirmation" id="tp-new-tp-confirm" class="v2-input js-transaction-pin"
                           placeholder="••••" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off">
                </div>
            </div>

            {{-- Tips --}}
            <div class="security-tips" style="background:rgba(255,255,255,0.02); border:1px solid var(--ds-border-light); border-radius:12px; padding:20px 24px; margin-top:28px; max-width:480px;">
                <p class="security-tips-title" style="color: var(--ds-text-muted); font-size: 0.75rem; font-weight: 700; margin: 0 0 12px; letter-spacing: 0.06em; text-transform: uppercase;">Atenção</p>
                <ul class="security-tips-list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <li class="security-tip-item" style="color: var(--ds-text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #DC2626; font-size: 0.875rem;"></i>
                        A senha deve ter exatamente 4 dígitos numéricos
                    </li>
                    <li class="security-tip-item" style="color: var(--ds-text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #DC2626; font-size: 0.875rem;"></i>
                        Não use sequências fáceis como 0000, 1234, 1122
                    </li>
                </ul>
            </div>
        </div>
        <div class="v2-settings-footer">
            <button type="submit" class="v2-btn-primary" style="background:#DC2626; border-color:#DC2626;">
                <i class="fas fa-key" style="margin-right: 8px;"></i> Alterar Senha Transacional
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-transaction-pin').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
        input.addEventListener('paste', function(event) {
            event.preventDefault();
        });
    });
});
</script>
@endpush
