@extends('frontend.user.setting.index')
@section('title', 'Minhas Chaves PIX')

@section('user_setting_content')

<style>
@media (max-width: 768px) {
    .pix-settings-grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 12px !important;
    }

    .pix-key-list {
        gap: 8px !important;
    }

    .pix-key-item {
        padding: 10px !important;
        border-radius: 10px !important;
        gap: 10px !important;
        align-items: center !important;
    }

    .pix-key-main {
        min-width: 0 !important;
        gap: 10px !important;
    }

    .pix-key-icon {
        width: 34px !important;
        height: 34px !important;
        flex: 0 0 34px !important;
    }

    .pix-key-value {
        max-width: 145px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        font-size: .88rem !important;
    }

    .pix-key-meta {
        gap: 6px !important;
        flex-wrap: wrap !important;
        font-size: .68rem !important;
    }

    .pix-key-badge {
        padding: 3px 8px !important;
        font-size: .58rem !important;
    }

    .pix-key-actions {
        gap: 6px !important;
        flex: 0 0 auto !important;
    }

    .pix-key-actions button {
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .pix-empty-state {
        padding: 16px !important;
        font-size: .78rem !important;
        border-radius: 10px !important;
    }

    .pix-form-group {
        margin-bottom: 12px !important;
    }

    .pix-form-submit {
        height: 40px !important;
        border-radius: 8px !important;
    }
}
</style>

<div class="v2-page-header" style="margin-bottom: 28px;">
    <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px; color: var(--ds-text-main);">Chaves PIX</h2>
    <p class="v2-page-subtitle" style="font-size: 0.9375rem; color: var(--ds-text-muted); margin: 0;">Gerencie suas chaves PIX para recebimento de transferências.</p>
</div>

<div class="pix-settings-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    {{-- LISTA DE CHAVES --}}
    <div class="v2-settings-card">
        <div class="v2-settings-header">
            <div class="v2-settings-header-icon" style="background: rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.15); color: var(--ds-primary-light);">
                <i class="fas fa-key"></i>
            </div>
            <div>
                <p class="v2-settings-title">Minhas Chaves PIX ({{ $pixKeys->count() }}/3)</p>
                <p class="v2-settings-desc">Gerencie suas chaves PIX de saque</p>
            </div>
        </div>
        <div class="v2-settings-body">
            @if($pixKeys->isEmpty())
                <div class="pix-empty-state" style="padding: 24px; text-align: center; color: var(--ds-text-muted); font-size: 0.85rem; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px dashed var(--ds-border-light);">
                    Nenhuma chave PIX cadastrada. Adicione uma nova chave ao lado.
                </div>
            @else
                <div class="pix-key-list" style="display:flex; flex-direction:column; gap:16px;">
                    @foreach($pixKeys as $key)
                        <div class="pix-key-item" style="display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.02); border:1px solid var(--ds-border-medium); padding:16px; border-radius:12px;">
                            <div class="pix-key-main" style="display:flex; align-items:center; gap:16px;">
                                <div class="pix-key-icon" style="width:40px; height:40px; background:rgba(124,58,237,0.08); border-radius:8px; border:1px solid rgba(124,58,237,0.15); display:flex; align-items:center; justify-content:center; color:var(--ds-primary-light); font-size:1rem;">
                                    <x-icons.pix size="24" />
                                </div>
                                <div>
                                    <strong class="pix-key-value" style="color:var(--ds-text-main); font-size:0.95rem; display:block; margin-bottom:4px;">{{ $key->pix_key }}</strong>
                                    <span class="pix-key-meta" style="display:flex; align-items:center; gap:8px; color:var(--ds-text-muted); font-size:0.75rem; text-transform:uppercase;">
                                        {{ $key->key_type }}
                                        @if($key->is_primary)
                                            <span class="pix-key-badge" style="background:rgba(124,58,237,0.1); border:1px solid rgba(124,58,237,0.18); color:var(--ds-primary-light); padding:4px 12px; border-radius:32px; font-weight:700; font-size:0.65rem; letter-spacing:0.04em;">Principal</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            <div class="pix-key-actions" style="display:flex; gap:8px;">
                                @if(!$key->is_primary)
                                     <form action="{{ route('user.pix-keys.set-primary', $key->id) }}" method="POST">
                                         @csrf
                                         <button type="submit" class="v2-btn-outline" data-tp-confirm="true" style="padding:8px 12px; border:none; background:rgba(124,58,237,0.08); color:var(--ds-primary-light); border-radius:8px;" title="Tornar Principal">
                                             <i class="fas fa-star"></i>
                                         </button>
                                     </form>
                                 @endif
                                 
                                <form action="{{ route('user.pix-keys.destroy', $key->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="v2-btn-outline" data-tp-confirm="true"
                                        style="padding:8px 12px; border:none; background:rgba(239, 68, 68, 0.08); color:#ef4444; border-radius:8px;" title="Remover">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ADICIONAR NOVA CHAVE --}}
    <div class="v2-settings-card">
        <div class="v2-settings-header">
            <div class="v2-settings-header-icon" style="background: rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.15); color: var(--ds-primary-light);">
                <x-icons.pix size="20" />
            </div>
            <div>
                <p class="v2-settings-title">Adicionar Chave PIX</p>
                <p class="v2-settings-desc">Cadastre uma nova chave</p>
            </div>
        </div>
        <div class="v2-settings-body">
            @if($pixKeys->count() >= 3)
                <div style="background: rgba(124, 58, 237, 0.05); border: 1px solid rgba(124, 58, 237, 0.15); padding: 16px; border-radius: 12px; display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px;">
                    <i class="fas fa-info-circle" style="color: var(--ds-primary-light); margin-top: 3px;"></i>
                    <div style="font-size: 0.85rem; color: rgba(255,255,255,0.8); line-height: 1.4;">Você atingiu o limite máximo de 3 chaves. Remova alguma chave para adicionar outra.</div>
                </div>
            @else
                <form action="{{ route('user.pix-keys.store') }}" method="POST" id="pixKeyForm">
                    @csrf
                    
                    <div class="pix-form-group" style="margin-bottom: 16px;">
                        <label class="v2-label" for="keyTypeSelect">Tipo de Chave</label>
                        <select class="v2-input" name="key_type" id="keyTypeSelect" required style="border-radius:12px;">
                            <option value="cpf">CPF</option>
                            <option value="cnpj">CNPJ</option>
                            <option value="email">E-mail</option>
                            <option value="phone">Celular</option>
                            <option value="random">Chave Aleatória</option>
                        </select>
                    </div>

                    <div class="pix-form-group" style="margin-bottom: 32px;">
                        <label class="v2-label" for="pixKeyInput">Chave PIX</label>
                        <input type="text" class="v2-input" name="pix_key" id="pixKeyInput" placeholder="000.000.000-00" required style="border-radius:12px;">
                        <span id="pixKeyError" style="color: #ef4444; font-size: 0.75rem; display: none; margin-top: 8px;">Chave PIX inválida.</span>
                    </div>

                    <button type="submit" class="v2-btn-primary pix-form-submit" id="pixKeySubmit" data-tp-confirm="true" style="width:100%;">
                        Cadastrar Chave
                    </button>
                </form>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('keyTypeSelect');
    const keyInput = document.getElementById('pixKeyInput');
    const form = document.getElementById('pixKeyForm');
    const errorSpan = document.getElementById('pixKeyError');
    const submitBtn = document.getElementById('pixKeySubmit');

    if (!typeSelect || !keyInput || !form) return;

    const masks = {
        cpf: function(value) {
            return value.replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        },
        cnpj: function(value) {
            return value.replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        },
        phone: function(value) {
            let v = value.replace(/\D/g, '');
            if (v.startsWith('55')) v = v.substring(2); 
            
            v = v.replace(/^(\d{2})(\d)/g, '($1) $2');
            v = v.replace(/(\d)(\d{4})$/, '$1-$2');
            if (v.length > 0) {
                return '+55 ' + v;
            }
            return v;
        }
    };

    const placeholders = {
        cpf: '000.000.000-00',
        cnpj: '00.000.000/0000-00',
        email: 'seu@email.com',
        phone: '+55 (11) 99999-9999',
        random: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'
    };

    const validators = {
        cpf: function(cpf) {
            cpf = cpf.replace(/\D/g, '');
            if(cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
            let sum = 0, rest;
            for (let i = 1; i <= 9; i++) sum = sum + parseInt(cpf.substring(i-1, i)) * (11 - i);
            rest = (sum * 10) % 11;
            if ((rest === 10) || (rest === 11)) rest = 0;
            if (rest !== parseInt(cpf.substring(9, 10))) return false;
            sum = 0;
            for (let i = 1; i <= 10; i++) sum = sum + parseInt(cpf.substring(i-1, i)) * (12 - i);
            rest = (sum * 10) % 11;
            if ((rest === 10) || (rest === 11)) rest = 0;
            if (rest !== parseInt(cpf.substring(10, 11))) return false;
            return true;
        },
        cnpj: function(cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
            if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
            let size = cnpj.length - 2
            let numbers = cnpj.substring(0, size);
            let digits = cnpj.substring(size);
            let sum = 0;
            let pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            if (result != digits.charAt(0)) return false;
            size = size + 1;
            numbers = cnpj.substring(0, size);
            sum = 0;
            pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            if (result != digits.charAt(1)) return false;
            return true;
        },
        email: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        phone: function(phone) {
            const digits = phone.replace(/\D/g, '');
            return digits.length === 12 || digits.length === 13; 
        },
        random: function(uuid) {
            return /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(uuid.trim());
        }
    };

    typeSelect.addEventListener('change', function() {
        const type = this.value;
        keyInput.value = '';
        keyInput.placeholder = placeholders[type] || '';
        errorSpan.style.display = 'none';
        keyInput.style.borderColor = 'var(--ds-border-medium)';
    });

    keyInput.addEventListener('input', function(e) {
        const type = typeSelect.value;
        if (masks[type]) {
            let start = this.selectionStart;
            let end = this.selectionEnd;
            const prevLen = this.value.length;
            
            this.value = masks[type](this.value);
            
            const diff = this.value.length - prevLen;
            this.setSelectionRange(start + diff, end + diff);
        }
        
        errorSpan.style.display = 'none';
        keyInput.style.borderColor = 'var(--ds-border-medium)';
    });

    form.addEventListener('submit', function(e) {
        const type = typeSelect.value;
        const val = keyInput.value;
        
        if (!validators[type](val)) {
            e.preventDefault();
            errorSpan.style.display = 'block';
            keyInput.style.borderColor = '#ef4444';
            
            keyInput.classList.add('animate__animated', 'animate__headShake');
            setTimeout(() => keyInput.classList.remove('animate__animated', 'animate__headShake'), 500);
        } else {
            if (type === 'cpf' || type === 'cnpj') {
                keyInput.value = val.replace(/\D/g, '');
            } else if (type === 'phone') {
                keyInput.value = '+' + val.replace(/\D/g, '');
            } else {
                keyInput.value = val.trim();
            }
        }
    });

    typeSelect.dispatchEvent(new Event('change'));
});
</script>
@endpush
@endsection
