@extends('frontend.merchant.connect.layout')
@section('title', $isEdit ? 'Editar Contato - Origin Connect' : 'Novo Contato - Origin Connect')

@section('connect_content')

{{-- FORM WRAPPER (Includes Dirty State tracking) --}}
<form action="{{ $isEdit ? route('user.connect.contacts.update', $contact->id) : route('user.connect.contacts.store') }}" method="POST" id="contactForm" class="dirty-check-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- HEADER & BREADCRUMBS --}}
    <div class="v2-settings-card" style="margin-bottom: 15px;">
        <div class="v2-settings-header" style="justify-content: space-between; align-items: center; padding: 12px 15px;">
            <div>
                <div style="font-size: 0.8rem; font-weight: 500; color: var(--ds-text-muted); margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                    <a href="{{ route('user.connect.dashboard') }}" style="color: inherit; text-decoration: none;">Origin Connect</a> 
                    <i class="fas fa-chevron-right" style="font-size: 0.55rem; opacity: 0.5;"></i> 
                    <a href="{{ route('user.connect.contacts.index') }}" style="color: inherit; text-decoration: none;">Contatos</a> 
                    <i class="fas fa-chevron-right" style="font-size: 0.55rem; opacity: 0.5;"></i> 
                    <span style="color: var(--ds-primary); font-weight: 600;">{{ $isEdit ? 'Editar Contato' : 'Novo Contato' }}</span>
                </div>
                <h2 style="margin: 0; font-weight: 700; font-size: 1.5rem; color: var(--ds-text-main); letter-spacing: -0.5px;">{{ $isEdit ? 'Editar Contato' : 'Novo Contato' }}</h2>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="{{ route('user.connect.contacts.index') }}" class="v2-btn-secondary" style="height: 32px; padding: 0 15px; font-size: 0.8rem; text-decoration: none;" id="btnCancel">
                    Cancelar
                </a>
                <button type="submit" class="v2-btn-primary" style="height: 32px; padding: 0 15px; font-size: 0.8rem; font-weight: 600;" id="btnSubmit">
                    <i class="fas fa-save" style="margin-right: 6px;"></i> <span id="btnSubmitText">{{ $isEdit ? 'Salvar Alterações' : 'Salvar Contato' }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- FULL HD LAYOUT --}}
    <div style="display: flex; gap: 15px; align-items: stretch;">
        
        {{-- LEFT COLUMN (65%) --}}
        <div style="flex: 0 0 65%; max-width: 65%; display: flex; flex-direction: column; gap: 15px;">
                       {{-- CARD 1: Informações Básicas --}}
            <div class="v2-settings-card" style="margin-bottom: 15px;">
                <div class="v2-settings-header" style="padding: 12px 15px; border-bottom: 1px solid var(--ds-border-light);">
                    <h5 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: var(--ds-text-main);">Informações Básicas</h5>
                </div>
                <div class="v2-settings-body" style="padding: 15px;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Nome <span style="color: var(--ds-danger);">*</span></label>
                            <input type="text" name="name" id="inputName" class="form-control" required value="{{ old('name', $contact->name) }}" placeholder="João Silva" style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Empresa</label>
                            <input type="text" name="company" id="inputCompany" class="form-control" value="{{ old('company', $contact->company) }}" placeholder="OriginPay" style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $contact->email) }}" placeholder="joao@exemplo.com" style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $contact->whatsapp ?? '') }}" placeholder="+55 11 99999-9999" style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Telefone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $contact->phone) }}" placeholder="+55 11 99999-9999" style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Observações</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes', $contact->notes ?? '') }}" placeholder="Anotações internas..." style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                        </div>
                        
                        @if($isEdit)
                            <div class="col-md-6 mb-3">
                                <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Cargo</label>
                                <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $contact->job_title) }}" placeholder="CEO" style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Site</label>
                                <input type="url" name="website" class="form-control" value="{{ old('website', $contact->website) }}" placeholder="https://..." style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($isEdit)
                {{-- CARD 2: Localização --}}
                <div class="v2-settings-card" style="margin: 0;">
                    <div class="v2-settings-header" style="padding: 10px 15px; border-bottom: 1px solid var(--ds-border-light);">
                        <h5 style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--ds-text-main);">Localização</h5>
                    </div>
                    <div class="v2-settings-body" style="padding: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="font-size: 0.7rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">País</label>
                                <select name="country" class="form-control" style="font-size: 0.75rem; height: 28px; padding: 2px 10px;">
                                    <option value="BR" {{ old('country', $contact->country) == 'BR' ? 'selected' : '' }}>Brasil</option>
                                    <option value="US" {{ old('country', $contact->country) == 'US' ? 'selected' : '' }}>EUA</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 0.7rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Estado</label>
                                <input type="text" name="state" class="form-control" value="{{ old('state', $contact->state) }}" placeholder="SP" style="font-size: 0.75rem; height: 28px; padding: 2px 10px;">
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="font-size: 0.7rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Cidade</label>
                                <input type="text" name="city" class="form-control" value="{{ old('city', $contact->city) }}" placeholder="São Paulo" style="font-size: 0.75rem; height: 28px; padding: 2px 10px;">
                            </div>
                            <div>
                                <label style="font-size: 0.7rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Idioma</label>
                                <select name="language" id="inputLang" class="form-control" style="font-size: 0.75rem; height: 28px; padding: 2px 10px;">
                                    <option value="pt-br" {{ old('language', $contact->language) == 'pt-br' ? 'selected' : '' }}>PT-BR</option>
                                    <option value="en" {{ old('language', $contact->language) == 'en' ? 'selected' : '' }}>EN-US</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 0.7rem; font-weight: 600; color: var(--ds-text-muted); margin-bottom: 4px;">Timezone</label>
                                <select name="timezone" id="inputTz" class="form-control" style="font-size: 0.75rem; height: 28px; padding: 2px 10px;">
                                    <option value="America/Sao_Paulo" {{ old('timezone', $contact->timezone) == 'America/Sao_Paulo' ? 'selected' : '' }}>BRT</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- RIGHT COLUMN (35%) --}}
        <div style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
            
            {{-- CARD 7: Resumo --}}
            <div class="v2-settings-card" style="margin: 0; background: linear-gradient(180deg, var(--ds-card-bg) 0%, rgba(0,0,0,0.15) 100%); border-top: 2px solid var(--ds-primary);">
                <div class="v2-settings-body" style="padding: 15px;">
                    <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--ds-text-muted); margin-bottom: 4px;">Contato</div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div id="previewInitials" style="width: 36px; height: 36px; background: rgba(124,58,237,0.2); color: var(--ds-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700; flex-shrink: 0;">
                            {{ $isEdit ? Str::upper(substr($contact->name, 0, 1)) : '?' }}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h6 id="previewName" style="margin: 0; font-weight: 700; color: var(--ds-text-main); font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $isEdit ? $contact->name : 'Sem nome' }}</h6>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px dashed var(--ds-border-light); padding-top: 10px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem;">
                            <span style="color: var(--ds-text-muted);">Status:</span>
                            <span id="previewStatus" style="font-weight: 600; color: var(--ds-success);">Ativo</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 4: Status --}}
            <div class="v2-settings-card" style="margin: 0;">
                <div class="v2-settings-header" style="padding: 10px 15px; border-bottom: 1px solid var(--ds-border-light);">
                    <h5 style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--ds-text-main);">Status</h5>
                </div>
                <div class="v2-settings-body" style="padding: 12px 15px;">
                    @php $currentStatus = old('status', $contact->status ?? 'active'); @endphp
                    
                    <label style="display: flex; align-items: center; margin-bottom: 10px; cursor: pointer;">
                        <input type="radio" name="status" value="active" {{ $currentStatus == 'active' ? 'checked' : '' }} style="margin-right: 10px; accent-color: var(--ds-success);">
                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--ds-text-main);">Ativo</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; margin-bottom: 10px; cursor: pointer;">
                        <input type="radio" name="status" value="archived" {{ $currentStatus == 'archived' ? 'checked' : '' }} style="margin-right: 10px; accent-color: var(--ds-warning);">
                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--ds-text-main);">Arquivado</span>
                    </label>

                    <label style="display: flex; align-items: center; margin-bottom: 0; cursor: pointer;">
                        <input type="radio" name="status" value="blocked" {{ $currentStatus == 'blocked' ? 'checked' : '' }} style="margin-right: 10px; accent-color: var(--ds-danger);">
                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--ds-text-main);">Bloqueado</span>
                    </label>
                </div>
            </div>

            {{-- CARD 6: Origem --}}
            <div class="v2-settings-card" style="margin: 0;">
                <div class="v2-settings-header" style="padding: 10px 15px; border-bottom: 1px solid var(--ds-border-light);">
                    <h5 style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--ds-text-main);">Origem</h5>
                </div>
                <div class="v2-settings-body" style="padding: 12px 15px;">
                    <select name="source" id="inputSource" class="form-control" style="font-size: 0.8rem; height: 32px; padding: 4px 10px;">
                        <option value="manual" {{ old('source', $contact->source) == 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="api" {{ old('source', $contact->source) == 'api' ? 'selected' : '' }}>API</option>
                        <option value="checkout" {{ old('source', $contact->source) == 'checkout' ? 'selected' : '' }}>Checkout</option>
                        <option value="landing_page" {{ old('source', $contact->source) == 'landing_page' ? 'selected' : '' }}>Landing Page</option>
                        <option value="csv" {{ old('source', $contact->source) == 'csv' ? 'selected' : '' }}>CSV</option>
                        <option value="whatsapp" {{ old('source', $contact->source) == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="outro" {{ old('source', $contact->source) == 'outro' ? 'selected' : '' }}>Outro</option>
                    </select>
                </div>
            </div>

            {{-- CARD 5: Tags --}}
            <div class="v2-settings-card" style="margin: 0;">
                <div class="v2-settings-header" style="padding: 10px 15px; border-bottom: 1px solid var(--ds-border-light); display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--ds-text-main);">Tags</h5>
                    @if($isEdit)
                        <button type="button" class="btn btn-sm" style="background: rgba(124,58,237,0.1); color: var(--ds-primary); font-size: 0.65rem; font-weight: 600; padding: 2px 6px;">
                            <i class="fas fa-plus"></i> Nova
                        </button>
                    @endif
                </div>
                <div class="v2-settings-body" style="padding: 15px;">
                    @if($merchantTags->count() > 0)
                        <select name="tags[]" class="form-control" multiple style="font-size: 0.75rem; min-height: 80px; padding: 5px;">
                            @foreach($merchantTags as $tag)
                                <option value="{{ $tag->id }}" {{ (collect(old('tags', $isEdit ? $contact->tags->pluck('id')->toArray() : []))->contains($tag->id)) ? 'selected' : '' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <div style="text-align: center; padding: 5px 0;">
                            <p style="color: var(--ds-text-muted); font-size: 0.75rem; margin: 0 0 5px;">Nenhuma tag cadastrada.</p>
                            <a href="#" style="color: var(--ds-primary); font-size: 0.75rem; font-weight: 600; text-decoration: none;">Gerenciar Tags</a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Live Preview Logic
    const inputName = document.getElementById('inputName');
    const previewName = document.getElementById('previewName');
    const previewInitials = document.getElementById('previewInitials');
    
    const radiosStatus = document.querySelectorAll('input[name="status"]');
    const previewStatus = document.getElementById('previewStatus');

    function updatePreview() {
        const nameVal = inputName.value.trim();
        previewName.textContent = nameVal || 'Sem nome';
        previewInitials.textContent = nameVal ? nameVal.charAt(0).toUpperCase() : '?';

        const checkedStatus = document.querySelector('input[name="status"]:checked');
        if(checkedStatus) {
            const selectedStatus = checkedStatus.value;
            if (selectedStatus === 'active') {
                previewStatus.textContent = 'Ativo';
                previewStatus.style.color = 'var(--ds-success)';
            } else if (selectedStatus === 'archived') {
                previewStatus.textContent = 'Arquivado';
                previewStatus.style.color = 'var(--ds-warning)';
            } else {
                previewStatus.textContent = 'Bloqueado';
                previewStatus.style.color = 'var(--ds-danger)';
            }
        }
    }

    if(inputName) inputName.addEventListener('input', updatePreview);
    radiosStatus.forEach(radio => radio.addEventListener('change', updatePreview));

    // Initialize preview
    updatePreview();

    // 2. Dirty Form Check (beforeunload)
    let isDirty = false;
    const form = document.getElementById('contactForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', () => { isDirty = true; });
        input.addEventListener('input', () => { isDirty = true; });
    });

    window.addEventListener('beforeunload', function(e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'Existem alterações não salvas. Deseja realmente sair?';
        }
    });

    // 3. Submit handling (Spinner & clear dirty flag)
    form.addEventListener('submit', function() {
        isDirty = false; // allow submit without warning
        const btn = document.getElementById('btnSubmit');
        const text = document.getElementById('btnSubmitText');
        
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
        text.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...';
    });

});
</script>
@endpush
