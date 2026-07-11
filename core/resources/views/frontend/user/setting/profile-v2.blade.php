@extends('frontend.user.setting.index')
@section('title', __('Perfil'))

@section('user_setting_content')

<style>
    @media (max-width: 768px) {
        .profile-summary-row {
            display: grid !important;
            grid-template-columns: 72px minmax(0, 1fr) !important;
            gap: 14px !important;
            align-items: center !important;
        }

        .profile-avatar-wrap {
            width: 72px !important;
            height: 72px !important;
        }

        .profile-avatar-wrap .profile-avatar-edit {
            width: 28px !important;
            height: 28px !important;
            border-width: 2px !important;
        }

        .profile-summary-row h3 {
            font-size: .98rem !important;
            margin-bottom: 4px !important;
            overflow-wrap: anywhere !important;
        }

        .profile-summary-row p {
            font-size: .74rem !important;
            margin-bottom: 10px !important;
            overflow-wrap: anywhere !important;
        }

        .profile-badges {
            gap: 6px !important;
            margin-bottom: 8px !important;
        }

        .profile-badges .v2-badge {
            font-size: .66rem !important;
            padding: 3px 8px !important;
        }

        .profile-form-grid {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            margin-bottom: 12px !important;
        }

        .profile-form-grid:last-child {
            margin-bottom: 0 !important;
        }

        .profile-form-grid .v2-input {
            height: 40px !important;
            border-radius: 8px !important;
            font-size: .84rem !important;
        }

        .profile-form-grid .v2-label {
            font-size: .72rem !important;
            margin-bottom: 5px !important;
        }
    }
</style>

{{-- Page Header --}}
<div class="v2-page-header" style="margin-bottom: 28px;">
    <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px; color: var(--ds-text-main);">Perfil do Usuário</h2>
    <p class="v2-page-subtitle" style="font-size: 0.9375rem; color: var(--ds-text-muted); margin: 0;">Gerencie suas informações pessoais e detalhes da conta.</p>
</div>

{{-- Email Verification Alert moved to topbar --}}

<form action="{{ route('user.settings.profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

{{-- Resumo Executivo do Usuário (Card) --}}
<div class="v2-settings-card" style="margin-bottom: 24px;">
    <div class="v2-settings-header">
        <div class="v2-settings-header-icon" style="background: rgba(124,58,237,0.08); border: 1px solid rgba(124,58,237,0.15); color: var(--ds-primary-light);">
            <i class="fas fa-camera"></i>
        </div>
        <div>
            <p class="v2-settings-title">Foto e Status da Conta</p>
            <p class="v2-settings-desc">Sua foto de perfil e status atual da conta.</p>
        </div>
    </div>
    <div class="v2-settings-body">
    <div class="profile-summary-row" style="display: flex; align-items: center; gap: 32px;">
        <div style="display: flex; flex-direction: column; align-items: center;">
            @php
                $avatarUrl = null;
                if ($user->avatar) {
                    if (str_starts_with($user->avatar, 'http')) {
                        $avatarUrl = $user->avatar;
                    } elseif (str_contains($user->avatar, '/')) {
                        $avatarUrl = asset('storage/' . $user->avatar);
                    } else {
                        $avatarUrl = asset('assets/images/user/profile/' . $user->avatar);
                    }
                }
            @endphp
            <div class="profile-avatar-wrap" style="position: relative; width: 96px; height: 96px; border-radius: 50%; flex-shrink: 0;">
                @if($avatarUrl)
                    <div style="width: 100%; height: 100%; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.05);">
                        <img data-avatar-preview src="{{ $avatarUrl }}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                @else
                    <div style="width: 100%; height: 100%; border-radius: 50%; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--ds-text-muted); border: 2px solid rgba(255,255,255,0.05); overflow: hidden;">
                        <img data-avatar-preview src="" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                        <i id="avatar-fallback-icon" class="fas fa-user"></i>
                    </div>
                @endif
                
                <label for="avatar" class="profile-avatar-edit" style="position: absolute; bottom: 0; right: 0; width: 34px; height: 34px; background: #7C3AED; color: white; border: 2px solid #131722; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);" title="Alterar foto" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 0 10px rgba(124,58,237,0.5)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                    <i class="fas fa-camera" style="font-size: 0.8rem;"></i>
                </label>
                <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
            </div>
            <div id="avatar-file-name" style="font-size: 0.75rem; color: var(--ds-primary-light); margin-top: 12px; font-weight: 500; display: none;"></div>
        </div>
        
        <div style="flex-grow: 1;">
            <h3 style="margin: 0 0 8px; font-size: 1.25rem; font-weight: 600; color: var(--ds-text-main);">{{ $user->full_name }}</h3>
            <p style="margin: 0 0 16px; color: var(--ds-text-muted); font-size: 0.875rem;">{{ $user->email }}</p>
            
            <div class="profile-badges" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 16px;">
                <span class="v2-badge v2-badge-success" style="font-size: 0.75rem; padding: 4px 12px;">● Conta Ativa</span>
                @if($user->kyc_status === \App\Enums\KycStatus::APPROVED)
                    <span class="v2-badge" style="background: rgba(124, 58, 237, 0.1); color: #8B5CF6; font-size: 0.75rem; padding: 4px 12px;">● KYC Aprovado</span>
                @elseif($user->kyc_status === \App\Enums\KycStatus::PENDING)
                    <span class="v2-badge v2-badge-warning" style="font-size: 0.75rem; padding: 4px 12px;">● KYC Pendente</span>
                @else
                    <span class="v2-badge" style="background: rgba(255, 255, 255, 0.05); color: #A1A1AA; font-size: 0.75rem; padding: 4px 12px;">● KYC Não Iniciado</span>
                @endif
            </div>
            
            <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.4);">Último acesso: {{ $user->last_login ? $user->last_login->format('d/m/Y \à\s H:i') : 'Hoje' }}</p>
        </div>
    </div>
</div>

    {{-- Personal Info Card --}}
<div class="v2-settings-card" style="margin-bottom: 24px;">
    <div class="v2-settings-header">
        <div class="v2-settings-header-icon" style="background: rgba(124,58,237,0.08); border: 1px solid rgba(124,58,237,0.15); color: var(--ds-primary-light);">
            <i class="fas fa-id-card"></i>
        </div>
        <div>
            <p class="v2-settings-title">Informações Pessoais</p>
            <p class="v2-settings-desc">Atualize seus dados cadastrais essenciais.</p>
        </div>
    </div>
    <div class="v2-settings-body">
            <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <div>
                    <label class="v2-label" for="first_name">Primeiro Nome</label>
                    <input type="text" id="first_name" name="first_name" class="v2-input"
                           value="{{ old('first_name', $user->first_name) }}" placeholder="Primeiro nome">
                </div>
                <div>
                    <label class="v2-label" for="last_name">Sobrenome</label>
                    <input type="text" id="last_name" name="last_name" class="v2-input"
                           value="{{ old('last_name', $user->last_name) }}" placeholder="Sobrenome">
                </div>
            </div>
            <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <div>
                    <label class="v2-label" for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" class="v2-input"
                           value="{{ old('username', $user->username) }}" placeholder="@usuario">
                </div>
                <div>
                    <label class="v2-label" for="gender">Gênero</label>
                    <select id="gender" name="gender" class="v2-input" style="cursor: pointer;">
                        @foreach(\App\Enums\Gender::cases() as $gender)
                            <option value="{{ $gender->value }}" {{ old('gender', $user->gender) === $gender ? 'selected' : '' }}>
                                {{ $gender->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <div>
                    <label class="v2-label" for="birthday">Data de Nascimento</label>
                    <input type="date" id="birthday" name="birthday" class="v2-input"
                           value="{{ old('birthday', $user->birthday) }}">
                </div>
                <div>
                    <label class="v2-label" for="phone">Telefone</label>
                    <input type="tel" id="phone" name="phone" class="v2-input"
                           value="{{ old('phone', $user->phone) }}" placeholder="+55 (11) 99999-9999">
                </div>
            </div>

            @if($user->role === \App\Enums\UserRole::MERCHANT)
            <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label class="v2-label" for="business_name">Nome da Empresa</label>
                    <input type="text" id="business_name" name="business_name" class="v2-input"
                           value="{{ old('business_name', $user->business_name) }}" placeholder="Razão Social">
                </div>
                <div>
                    <label class="v2-label" for="business_address">Endereço Comercial</label>
                    <input type="text" id="business_address" name="business_address" class="v2-input"
                           value="{{ old('business_address', $user->business_address) }}" placeholder="Endereço">
                </div>
            </div>
            @endif
    </div>
</div>

    {{-- Contact & Location Card --}}
<div class="v2-settings-card" style="margin-bottom: 24px;">
    <div class="v2-settings-header">
        <div class="v2-settings-header-icon" style="background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.15); color: #38bdf8;">
            <i class="fas fa-map-marker-alt"></i>
        </div>
        <div>
            <p class="v2-settings-title">Contato &amp; Localização</p>
            <p class="v2-settings-desc">Mantenha seu endereço atualizado para faturamento.</p>
        </div>
    </div>
    <div class="v2-settings-body">
            <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <div>
                    <label class="v2-label" for="email">
                        E-mail
                        @if($user->email_verified_at)
                            <span style="color:var(--ds-success);font-size:0.75rem;margin-left:8px;"><i class="fas fa-check-circle"></i> Verificado</span>
                        @else
                            <span style="color:var(--ds-warning);font-size:0.75rem;margin-left:8px;"><i class="fas fa-exclamation-triangle"></i> Não verificado</span>
                        @endif
                    </label>
                    <input type="email" id="email" name="email" class="v2-input"
                           value="{{ old('email', $user->email) }}" placeholder="email@exemplo.com">
                </div>
                <div>
                    <label class="v2-label" for="postal_code">CEP</label>
                    <input type="text" id="postal_code" name="postal_code" class="v2-input"
                           value="{{ old('postal_code', $user->postal_code) }}" placeholder="00000-000">
                </div>
            </div>
            <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <div>
                    <label class="v2-label" for="country">País</label>
                    <input type="text" id="country" class="v2-input" value="{{ $user->country }}" disabled readonly style="background: rgba(255,255,255,0.02); color: #64748b;">
                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.3); margin: 8px 0 0 0;">País definido pelo KYC</p>
                </div>
                <div>
                    <label class="v2-label" for="state">Estado</label>
                    <input type="text" id="state" name="state" class="v2-input"
                           value="{{ old('state', $user->state) }}" placeholder="Ex: São Paulo">
                </div>
            </div>
            <div style="margin-bottom: 24px;">
                <label class="v2-label" for="city">Cidade</label>
                <input type="text" id="city" name="city" class="v2-input"
                       value="{{ old('city', $user->city) }}" placeholder="Ex: São Paulo">
            </div>
            <div>
                <label class="v2-label" for="address">Endereço Completo</label>
                <input type="text" id="address" name="address" class="v2-input"
                       value="{{ old('address', $user->address) }}" placeholder="Rua, número, complemento...">
            </div>
        </div>
    </div>
    <div class="v2-settings-footer">
        <button type="button" class="v2-btn-tertiary" onclick="window.location.reload();">Cancelar</button>
        <button type="submit" class="v2-btn-primary" data-tp-confirm="true" style="margin-left: 12px;"><i class="fas fa-save" style="margin-right: 8px;"></i>Salvar Alterações</button>
    </div>
</div>

</form>

@push('scripts')
<script>
    document.getElementById('avatar').addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;

        const fileNameDisplay = document.getElementById('avatar-file-name');
        if (fileNameDisplay) {
            fileNameDisplay.innerText = file.name;
            fileNameDisplay.style.display = 'block';
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const previewImgs = document.querySelectorAll('[data-avatar-preview]');
            previewImgs.forEach(img => {
                img.src = e.target.result;
                img.style.display = 'block';
            });
            const fallbackIcon = document.getElementById('avatar-fallback-icon');
            if (fallbackIcon) {
                fallbackIcon.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    });
</script>
@endpush
@endsection
