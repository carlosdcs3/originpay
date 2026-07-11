@extends('frontend.layouts.user-v2')
@section('title', __('Perfil'))

@section('user_setting_content')

{{-- Email Verification Alert --}}
@if($user->email_verified_at === null)
<div style="background:rgba(255,77,106,0.08);border:1px solid rgba(255,77,106,0.2);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px;margin-bottom:4px;">
    <i class="fas fa-exclamation-triangle" style="color:#FF4D6A;"></i>
    <span style="color:#e2e8f0;font-size:0.875rem;">E-mail não verificado.
        <a href="{{ route('user.settings.verify-email') }}" style="color:var(--ds-primary-light);font-weight:600;">Verificar agora →</a>
    </span>
</div>
@endif

<form action="{{ route('user.settings.profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Avatar --}}
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-section-icon" style="background:rgba(124,58,237,0.1);">
                <i class="fas fa-camera" style="color:var(--ds-primary-light);"></i>
            </div>
            <div>
                <p class="cfg-section-title">Foto de Perfil</p>
                <p class="cfg-section-sub">JPG, PNG ou GIF. Máximo 2MB.</p>
            </div>
        </div>
        <div class="cfg-section-body">
            <x-img name="avatar" :old="$user->avatar" :ref="'avatar'" :name="'avatar'"/>
        </div>
    </div>

    {{-- Personal Info --}}
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-section-icon" style="background:rgba(124,58,237,0.1);">
                <i class="fas fa-user" style="color:var(--ds-primary-light);"></i>
            </div>
            <div>
                <p class="cfg-section-title">Informações Pessoais</p>
                <p class="cfg-section-sub">Nome, usuário, gênero e data de nascimento</p>
            </div>
        </div>
        <div class="cfg-section-body">
            <div class="cfg-field-row">
                <div>
                    <label class="cfg-label" for="first_name">Primeiro Nome</label>
                    <input type="text" id="first_name" name="first_name" class="cfg-input"
                           value="{{ old('first_name', $user->first_name) }}" placeholder="Primeiro nome">
                </div>
                <div>
                    <label class="cfg-label" for="last_name">Sobrenome</label>
                    <input type="text" id="last_name" name="last_name" class="cfg-input"
                           value="{{ old('last_name', $user->last_name) }}" placeholder="Sobrenome">
                </div>
            </div>
            <div class="cfg-field-row">
                <div>
                    <label class="cfg-label" for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" class="cfg-input"
                           value="{{ old('username', $user->username) }}" placeholder="@usuario">
                </div>
                <div>
                    <label class="cfg-label" for="gender">Gênero</label>
                    <select id="gender" name="gender" class="cfg-input">
                        @foreach(\App\Enums\Gender::cases() as $gender)
                            <option value="{{ $gender->value }}" {{ old('gender', $user->gender) === $gender ? 'selected' : '' }}>
                                {{ $gender->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="cfg-field-row">
                <div>
                    <label class="cfg-label" for="birthday">Data de Nascimento</label>
                    <input type="date" id="birthday" name="birthday" class="cfg-input"
                           value="{{ old('birthday', $user->birthday) }}">
                </div>
                <div>
                    <label class="cfg-label" for="phone">Telefone</label>
                    <input type="tel" id="phone" name="phone" class="cfg-input"
                           value="{{ old('phone', $user->phone) }}" placeholder="+55 (11) 99999-9999">
                </div>
            </div>

            @if($user->role === \App\Enums\UserRole::MERCHANT)
            <div class="cfg-field-row">
                <div>
                    <label class="cfg-label" for="business_name">Nome da Empresa</label>
                    <input type="text" id="business_name" name="business_name" class="cfg-input"
                           value="{{ old('business_name', $user->business_name) }}" placeholder="Razão Social">
                </div>
                <div>
                    <label class="cfg-label" for="business_address">Endereço Comercial</label>
                    <input type="text" id="business_address" name="business_address" class="cfg-input"
                           value="{{ old('business_address', $user->business_address) }}" placeholder="Endereço">
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Contact & Location --}}
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-section-icon" style="background:rgba(124,110,255,0.1);">
                <i class="fas fa-map-marker-alt" style="color:var(--ds-purple);"></i>
            </div>
            <div>
                <p class="cfg-section-title">Contato & Localização</p>
                <p class="cfg-section-sub">E-mail, endereço e localidade</p>
            </div>
        </div>
        <div class="cfg-section-body">
            <div class="cfg-field-row">
                <div>
                    <label class="cfg-label" for="email">
                        E-mail
                        @if($user->email_verified_at)
                            <span style="background:rgba(124,58,237,0.12);color:var(--ds-primary-light);font-size:0.65rem;padding:1px 6px;border-radius:100px;margin-left:4px;">✓ Verificado</span>
                        @else
                            <span style="background:rgba(255,77,106,0.12);color:#FF4D6A;font-size:0.65rem;padding:1px 6px;border-radius:100px;margin-left:4px;">Não verificado</span>
                        @endif
                    </label>
                    <input type="email" id="email" name="email" class="cfg-input"
                           value="{{ old('email', $user->email) }}" placeholder="email@exemplo.com">
                </div>
                <div>
                    <label class="cfg-label" for="postal_code">CEP</label>
                    <input type="text" id="postal_code" name="postal_code" class="cfg-input"
                           value="{{ old('postal_code', $user->postal_code) }}" placeholder="00000-000">
                </div>
            </div>
            <div class="cfg-field-row">
                <div>
                    <label class="cfg-label" for="country">País</label>
                    <input type="text" id="country" class="cfg-input" value="{{ $user->country }}" disabled>
                    <p class="cfg-input-hint">País definido pelo KYC</p>
                </div>
                <div>
                    <label class="cfg-label" for="state">Estado</label>
                    <input type="text" id="state" name="state" class="cfg-input"
                           value="{{ old('state', $user->state) }}" placeholder="Ex: São Paulo">
                </div>
            </div>
            <div class="cfg-field-row">
                <div>
                    <label class="cfg-label" for="city">Cidade</label>
                    <input type="text" id="city" name="city" class="cfg-input"
                           value="{{ old('city', $user->city) }}" placeholder="Ex: São Paulo">
                </div>
            </div>
            <div class="cfg-field-row full">
                <div>
                    <label class="cfg-label" for="address">Endereço Completo</label>
                    <textarea id="address" name="address" class="cfg-input" rows="3"
                              placeholder="Rua, número, complemento...">{{ old('address', $user->address) }}</textarea>
                </div>
            </div>

            <div class="cfg-save-row">
                <button type="submit" class="ds-btn-submit" style="background:var(--ds-primary-light);color:#fff;width:auto;padding:10px 28px;">
                    <i class="fas fa-save" style="margin-right:8px;"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>

</form>
@endsection