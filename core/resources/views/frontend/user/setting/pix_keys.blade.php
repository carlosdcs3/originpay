@extends('frontend.layouts.user-v2')
@section('title', 'Minhas Chaves PIX')

@section('user_setting_content')

<div class="ds-page-header">
    <h4 style="font-size:1.1rem;font-weight:700;margin:0 0 2px;color:var(--ds-text-primary);">
        <i class="fas fa-qrcode" style="color:var(--ds-primary);margin-right:8px;font-size:0.95rem;"></i>
        Minhas Chaves PIX
    </h4>
    <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;">Gerencie até 3 chaves PIX. A chave principal será usada por padrão nos saques.</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    {{-- LISTA DE CHAVES --}}
    <div class="ds-card">
        <div class="ds-card-header">
            <span class="ds-v2-card-header">Chaves Cadastradas ({{ $pixKeys->count() }}/3)</span>
        </div>
        <div class="ds-card-body">
            @if($pixKeys->isEmpty())
                <div style="padding: 20px; text-align: center; color: var(--ds-text-muted); font-size: 0.9rem;">
                    Nenhuma chave PIX cadastrada. Adicione uma nova chave ao lado.
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:10px; padding:15px;">
                    @foreach($pixKeys as $key)
                        <div style="display:flex; align-items:center; justify-content:space-between; background:var(--ds-bg-light); border:1px solid var(--ds-border-light); padding:12px; border-radius:8px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; background:rgba(124,58,237,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--ds-primary);">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div>
                                    <strong style="color:var(--ds-text-primary); font-size:0.9rem; display:block;">{{ $key->pix_key }}</strong>
                                    <span style="color:var(--ds-text-muted); font-size:0.75rem; text-transform:uppercase;">
                                        {{ $key->key_type }}
                                        @if($key->is_primary)
                                            <span style="margin-left:5px; background:rgba(124,58,237,0.15); color:var(--ds-primary); padding:2px 6px; border-radius:4px; font-weight:600; font-size:0.65rem;">Principal</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            <div style="display:flex; gap:8px;">
                                @if(!$key->is_primary)
                                    <form action="{{ route('user.pix-keys.set-primary', $key->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="ds-btn" style="padding:4px 8px; font-size:0.75rem; background:transparent; border:1px solid var(--ds-primary); color:var(--ds-primary);" title="Tornar Principal">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    </form>
                                @endif
                                
                                <form action="{{ route('user.pix-keys.destroy', $key->id) }}" method="POST" onsubmit="return confirm('Deseja realmente remover esta chave PIX?')">
                                    @csrf
                                    <button type="submit" class="ds-btn" style="padding:4px 8px; font-size:0.75rem; background:transparent; border:1px solid var(--ds-error); color:var(--ds-error);" title="Remover">
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
    <div class="ds-card">
        <div class="ds-card-header">
            <span class="ds-v2-card-header">Adicionar Chave PIX</span>
        </div>
        <div class="ds-card-body padded">
            @if($pixKeys->count() >= 3)
                <div class="ds-alert-banner">
                    <i class="fas fa-info-circle" style="color:var(--ds-primary);"></i>
                    <span>Você atingiu o limite máximo de 3 chaves. Remova alguma chave para adicionar outra.</span>
                </div>
            @else
                <form action="{{ route('user.pix-keys.store') }}" method="POST" id="pixKeyForm">
                    @csrf
                    
                    <div class="ds-form-group">
                        <label class="ds-label">Tipo de Chave</label>
                        <select class="v2-input" name="key_type" id="keyTypeSelect" required>
                            <option value="cpf">CPF</option>
                            <option value="cnpj">CNPJ</option>
                            <option value="email">E-mail</option>
                            <option value="phone">Celular</option>
                            <option value="random">Chave Aleatória</option>
                        </select>
                    </div>

                    <div class="ds-form-group">
                        <label class="ds-label">Chave PIX</label>
                        <input type="text" class="v2-input" name="pix_key" id="pixKeyInput" placeholder="000.000.000-00" required>
                        <span id="pixKeyError" style="color: var(--ds-error); font-size: 0.75rem; display: none; margin-top: 5px;">Chave PIX inválida.</span>
                    </div>

                    <button type="submit" class="ds-btn-submit" id="pixKeySubmit" style="width:100%;">
                        <i class="fas fa-plus"></i>
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
            if (v.startsWith('55')) v = v.substring(2); // remove 55 if they paste with it
            
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
            return digits.length === 12 || digits.length === 13; // 55 + 10 or 11 digits
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
        keyInput.style.borderColor = 'var(--ds-border)';
    });

    keyInput.addEventListener('input', function(e) {
        const type = typeSelect.value;
        if (masks[type]) {
            let start = this.selectionStart;
            let end = this.selectionEnd;
            const prevLen = this.value.length;
            
            this.value = masks[type](this.value);
            
            // Adjust cursor position roughly
            const diff = this.value.length - prevLen;
            this.setSelectionRange(start + diff, end + diff);
        }
        
        // Hide error while typing
        errorSpan.style.display = 'none';
        keyInput.style.borderColor = 'var(--ds-border)';
    });

    form.addEventListener('submit', function(e) {
        const type = typeSelect.value;
        const val = keyInput.value;
        
        if (!validators[type](val)) {
            e.preventDefault();
            errorSpan.style.display = 'block';
            keyInput.style.borderColor = 'var(--ds-error)';
            
            // Add shake animation
            keyInput.classList.add('animate__animated', 'animate__headShake');
            setTimeout(() => keyInput.classList.remove('animate__animated', 'animate__headShake'), 500);
        } else {
            // Normalization before submit to help backend
            if (type === 'cpf' || type === 'cnpj') {
                keyInput.value = val.replace(/\D/g, '');
            } else if (type === 'phone') {
                keyInput.value = '+' + val.replace(/\D/g, '');
            } else {
                keyInput.value = val.trim();
            }
        }
    });

    // Disparar o evento no carregamento para garantir o placeholder e a máscara corretos
    typeSelect.dispatchEvent(new Event('change'));
});
</script>
@endpush
@endsection
