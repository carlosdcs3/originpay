@extends('frontend.layouts.user-v2')
@section('title', 'Criar Link de Pagamento')

@section('styles')
<style>
    /* OriginPay SaaS Minimal Variables */
    :root {
        --op-bg: #09090B;
        --op-card-bg: #111318;
        --op-border: rgba(255, 255, 255, 0.08);
        --op-border-hover: rgba(255, 255, 255, 0.15);
        --op-primary: #7C3AED;
        --op-primary-hover: #6D28D9;
        --op-text-main: #FAFAFA;
        --op-text-muted: #A1A1AA;
        --op-text-dark: #52525B;
        --op-radius-lg: 12px;
        --op-radius-md: 8px;
        --op-radius-sm: 6px;
        --transition: 200ms ease;
    }

    body { background: var(--op-bg); color: var(--op-text-main); font-family: 'Inter', sans-serif; }

    /* Override V2 Container to maximize space and avoid overflow */
    .v2-header { height: 44px !important; }
    .v2-content { padding: 12px 24px !important; display: flex; flex-direction: column; min-height: calc(100vh - 44px); }

    /* Page Header */
    .op-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--op-border); }
    .op-header-titles h1 { font-size: 1.15rem; font-weight: 600; margin: 0 0 4px 0; color: #FFF; letter-spacing: -0.01em; }
    .op-header-titles p { font-size: 0.75rem; color: var(--op-text-muted); margin: 0; }
    .op-btn-doc { padding: 6px 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--op-border); border-radius: var(--op-radius-sm); color: var(--op-text-muted); font-size: 0.7rem; font-weight: 500; cursor: pointer; transition: var(--transition); }
    .op-btn-doc:hover { background: rgba(255,255,255,0.1); color: #FFF; }

    /* Grid System 65/35 */
    .op-grid { display: grid; grid-template-columns: minmax(0, 1.8fr) 1fr; gap: 24px; align-items: start; flex: 1; }
    @media(max-width: 1024px) { .op-grid { grid-template-columns: 1fr; } }

    /* Cards */
    .op-card { background: var(--op-card-bg); border: 1px solid var(--op-border); border-radius: var(--op-radius-lg); padding: 16px; margin-bottom: 16px; }
    .op-card-header { margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
    .op-card-title { font-size: 0.85rem; font-weight: 600; color: #FFF; margin: 0; }
    
    .op-card-muted { border: 1px dashed var(--op-border); border-radius: var(--op-radius-lg); padding: 16px; margin-bottom: 16px; text-align: center; }
    .op-card-muted h3 { font-size: 0.8rem; font-weight: 500; color: var(--op-text-muted); margin: 0 0 4px 0; }
    .op-card-muted p { font-size: 0.7rem; color: var(--op-text-dark); margin: 0; }

    /* Form Fields */
    .op-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
    .op-row.full { grid-template-columns: 1fr; }
    
    .op-label { display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 500; color: #D4D4D8; margin-bottom: 6px; }
    .op-char-count { color: var(--op-text-dark); font-size: 0.65rem; }
    
    .op-input-wrap { position: relative; display: flex; align-items: center; }
    .op-prefix { position: absolute; left: 10px; color: var(--op-text-muted); font-size: 0.75rem; font-weight: 500; }
    
    .op-input { width: 100%; height: 36px; background: var(--op-bg); border: 1px solid var(--op-border); border-radius: var(--op-radius-md); color: #FFF; padding: 0 10px; font-size: 0.8rem; transition: var(--transition); outline: none; }
    .op-input.has-prefix { padding-left: 32px; }
    .op-input::placeholder { color: #52525B; }
    .op-input:focus { border-color: var(--op-primary); box-shadow: 0 0 0 2px rgba(124,58,237,0.2); }
    .op-input:disabled { opacity: 0.5; cursor: not-allowed; }
    
    textarea.op-input { height: 56px; padding-top: 8px; resize: none; line-height: 1.3; }
    
    .op-error-msg { color: #EF4444; font-size: 0.7rem; font-weight: 500; margin-top: 4px; display: none; align-items: center; gap: 4px; }
    .op-input.is-invalid { border-color: #EF4444 !important; }

    /* Segments & Pills */
    .op-segments { display: flex; background: var(--op-bg); border: 1px solid var(--op-border); border-radius: var(--op-radius-md); padding: 2px; }
    .op-segment { flex: 1; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 500; color: var(--op-text-muted); cursor: pointer; border-radius: var(--op-radius-sm); transition: var(--transition); }
    .op-segment:hover { color: #FFF; }
    .op-segment.active { background: var(--op-card-bg); color: #FFF; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 1px 2px rgba(0,0,0,0.2); }

    .op-methods { display: flex; gap: 8px; }
    .op-method { flex: 1; height: 36px; background: var(--op-bg); border: 1px solid var(--op-border); border-radius: var(--op-radius-md); display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.75rem; font-weight: 500; color: var(--op-text-muted); cursor: pointer; transition: var(--transition); user-select: none; }
    .op-method:hover { border-color: var(--op-border-hover); color: #FFF; }
    .op-method.active { background: rgba(124,58,237,0.1); border-color: var(--op-primary); color: #FFF; }

    /* Action Footer */
    .op-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; margin-top: auto; }
    .op-footer-text { font-size: 0.75rem; color: var(--op-text-muted); font-weight: 500; }
    .op-actions { display: flex; gap: 8px; }
    .op-btn-secondary { height: 36px; padding: 0 16px; background: transparent; border: 1px solid transparent; color: var(--op-text-muted); font-size: 0.8rem; font-weight: 500; border-radius: var(--op-radius-md); cursor: pointer; transition: var(--transition); }
    .op-btn-secondary:hover { color: #FFF; background: rgba(255,255,255,0.05); }
    .op-btn-primary { height: 36px; padding: 0 20px; background: var(--op-primary); color: #FFF; font-size: 0.8rem; font-weight: 500; border: none; border-radius: var(--op-radius-md); cursor: pointer; display: flex; align-items: center; gap: 6px; transition: var(--transition); }
    .op-btn-primary:hover { background: var(--op-primary-hover); }

    /* Real Checkout Preview — Faithful Replica */
    .op-preview-wrapper { position: sticky; top: 12px; display: flex; flex-direction: column; gap: 12px; }

    .chk-card {
        background: #111218;
        border: 1px solid rgba(124,58,237,0.15);
        border-radius: 16px;
        box-shadow: 0 0 0 1px rgba(124,58,237,0.06), 0 16px 48px rgba(0,0,0,0.6);
        overflow: hidden;
        width: 100%;
    }

    /* Head */
    .chk-head {
        padding: 20px 20px 14px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .chk-head-top { display: flex; align-items: flex-start; gap: 12px; }
    .chk-avatar {
        width: 40px; height: 40px; border-radius: 10px;
        background: transparent;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; overflow: hidden;
    }
    .chk-avatar img { width: 40px; height: 40px; object-fit: contain; }
    .chk-info { flex: 1; }
    .chk-title { font-size: 0.9rem; font-weight: 700; color: #F0F0F5; line-height: 1.3; }
    .chk-seller { font-size: 0.68rem; color: #6E6E85; margin-top: 2px; }
    .chk-amount-col { text-align: right; flex-shrink: 0; }
    .chk-price { font-size: 1.35rem; font-weight: 900; color: #F0F0F5; letter-spacing: -0.03em; }
    .chk-currency { font-size: 0.65rem; color: #6E6E85; text-align: right; margin-top: 2px; }
    .chk-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 9px; border-radius: 999px;
        font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        background: rgba(124,58,237,0.15); color: #C4B5FD;
        border: 1px solid rgba(124,58,237,0.25);
        margin-top: 10px;
    }
    .chk-badge i { font-size: 0.6rem; }

    /* Method Tabs */
    .chk-method-section { padding: 14px 20px 0; }
    .chk-method-label { font-size: 0.6rem; font-weight: 700; color: #6E6E85; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .chk-method-tabs { display: flex; gap: 6px; }
    .chk-method-tab {
        flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;
        padding: 8px 4px; background: #161820;
        border: 1px solid rgba(255,255,255,0.07); border-radius: 8px;
        font-size: 0.62rem; font-weight: 600; color: #6E6E85;
    }
    .chk-method-tab.active {
        border-color: var(--op-primary);
        background: rgba(124,58,237,0.1);
        color: #F0F0F5;
        box-shadow: 0 0 0 2px rgba(124,58,237,0.12);
    }
    .chk-method-tab i { font-size: 0.9rem; }
    .chk-method-tab img { width: 18px; height: 18px; object-fit: contain; filter: brightness(0) invert(1); opacity: 0.6; }
    .chk-method-tab.active img { opacity: 1; }

    /* Form Fields */
    .chk-form { padding: 12px 20px 0; }
    .chk-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
    .chk-field { display: flex; flex-direction: column; gap: 3px; }
    .chk-field label { font-size: 0.58rem; font-weight: 600; color: #6E6E85; }
    .chk-fake-input {
        height: 32px; background: #161820;
        border: 1px solid rgba(255,255,255,0.07); border-radius: 7px;
        display: flex; align-items: center; padding: 0 8px;
        gap: 6px; font-size: 0.65rem; color: #3A3A50;
    }
    .chk-fake-input i { font-size: 0.65rem; color: #3A3A50; }
    .chk-fake-input span { color: #F0F0F5; font-size: 0.7rem; }

    /* Submit */
    .chk-submit-area { padding: 14px 20px 16px; }
    .chk-btn {
        width: 100%; height: 40px;
        background: linear-gradient(160deg, #9B5DE5 0%, #7C3AED 55%, #5B21B6 100%);
        box-shadow: 0 4px 14px rgba(124,58,237,0.35);
        color: #fff; font-size: 0.82rem; font-weight: 700;
        border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .chk-btn i { font-size: 0.75rem; }

    /* Seal */
    .chk-seal {
        display: flex; align-items: center; justify-content: center;
        gap: 5px; padding: 0 0 14px;
        font-size: 0.62rem; color: #6E6E85;
    }
    .chk-seal i { color: var(--op-primary); font-size: 0.65rem; }

    /* Output Link Info */
    .op-link-box { border: 1px dashed var(--op-border); border-radius: var(--op-radius-md); padding: 10px; display: flex; flex-direction: column; gap: 6px; background: rgba(255,255,255,0.01); }
    .op-link-box p { margin: 0; font-size: 0.7rem; color: var(--op-text-muted); font-weight: 500; }
    .op-fake-url { height: 28px; background: var(--op-bg); border: 1px solid var(--op-border); border-radius: var(--op-radius-sm); display: flex; align-items: center; padding: 0 8px; font-size: 0.65rem; color: var(--op-text-dark); }
</style>
@endsection

@section('content')

<!-- Header (Minimal) -->
<div class="op-header">
    <div class="op-header-titles">
        <h1>Criar Link de Pagamento</h1>
        <p>Preencha as informações abaixo para gerar um checkout hospedado pela OriginPay.</p>
    </div>
    <button class="op-btn-doc"><i class="fas fa-book"></i> Ver documentação</button>
</div>

<!-- Main Form -->
<form id="pl-form" method="POST" action="{{ route('user.payment-links.charges.store') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1;">
    @csrf
    <div class="op-grid">
        
        <!-- Left Column -->
        <div style="display: flex; flex-direction: column; height: 100%;">
            
            <!-- Card 1: Produto -->
            <div class="op-card">
                <div class="op-card-header">
                    <h2 class="op-card-title">1. Informações do Produto</h2>
                </div>
                <div class="op-row full">
                    <div>
                        <div class="op-label">
                            <span>Nome do produto ou serviço</span>
                            <span class="op-char-count" id="count-title">0/80</span>
                        </div>
                        <input type="text" id="in-title" name="title" class="op-input" value="{{ old('title') }}" placeholder="Ex: Consultoria Premium" maxlength="80" autocomplete="off">
                        @error('title') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                        <div class="op-error-msg" id="err-title"><i class="fas fa-info-circle"></i> O nome é obrigatório.</div>
                    </div>
                </div>
                <div class="op-row full">
                    <div>
                        <div class="op-label">
                            <span>Descrição curta (opcional)</span>
                            <span class="op-char-count" id="count-desc">0/200</span>
                        </div>
                        <textarea id="in-desc" name="description" class="op-input" placeholder="Detalhes que aparecer&atilde;o no checkout..." maxlength="200">{{ old('description') }}</textarea>
                        @error('description') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="op-row full">
                    <div>
                        <div class="op-label">Valor da cobrança</div>
                        <div class="op-input-wrap">
                            <span class="op-prefix">BRL</span>
                            <input id="in-amount-mask" type="text" class="op-input has-prefix" placeholder="0,00" autocomplete="off">
                            <input id="in-amount-real" type="hidden" name="amount" value="{{ old('amount') }}">
                        </div>
                        @error('amount') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                        <div class="op-error-msg" id="err-amount"><i class="fas fa-info-circle"></i> Informe um valor maior que zero.</div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Regras e Pagamento -->
            @php
                $defaultMethods = $paymentMethods->pluck('code')->all();
                $oldMethods = old('allowed_payment_methods', $defaultMethods);
            @endphp
            <div class="op-card">
                <div class="op-card-header">
                    <h2 class="op-card-title">2. Regras de Pagamento</h2>
                </div>
                
                <div class="op-row">
                    <div>
                        <div class="op-label">Métodos aceitos</div>
                        <div class="op-methods">
                            @forelse($paymentMethods as $method)
                                <label class="op-method {{ in_array($method['code'], $oldMethods, true) ? 'active' : '' }}">
                                    <input type="checkbox" name="allowed_payment_methods[]" value="{{ $method['code'] }}" class="d-none pm-checkbox" {{ in_array($method['code'], $oldMethods, true) ? 'checked' : '' }}>
                                    <i class="{{ $method['icon_class'] }}"></i> {{ $method['label'] }}
                                </label>
                            @empty
                                <div style="width:100%;padding:14px;border:1px dashed rgba(239,68,68,0.35);border-radius:8px;color:#FCA5A5;font-size:0.75rem;text-align:center;">
                                    Nenhum metodo de pagamento esta disponivel. Entre em contato com o administrador.
                                </div>
                            @endforelse
                        </div>
                        @error('allowed_payment_methods') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                        <div class="op-error-msg" id="err-methods"><i class="fas fa-info-circle"></i> Selecione ao menos um método.</div>
                    </div>
                    <div>
                        <div class="op-label">Validade do link</div>
                        @php $oldExpires = old('expires_at'); @endphp
                        <input type="hidden" id="in-expires" name="expires_at" value="{{ $oldExpires }}">
                        <div class="op-segments" style="margin-bottom: 8px;">
                            <div class="op-segment op-pill {{ !$oldExpires ? 'active' : '' }}" data-val="">Nunca</div>
                            <div class="op-segment op-pill" data-val="24h">24h</div>
                            <div class="op-segment op-pill" data-val="7d">7 dias</div>
                            <div class="op-segment op-pill" data-val="custom">Data</div>
                        </div>
                        
                        <div id="custom-date-wrapper" style="display: none; animation: fadeIn 0.3s ease;">
                            <div class="op-label" style="font-size: 0.65rem; margin-bottom: 4px;">Data de expiração</div>
                            <input type="date" id="in-expires-custom" class="op-input" style="color-scheme: dark; font-size: 0.75rem;">
                        </div>

                        @error('expires_at') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="op-row" style="margin-top: 12px; margin-bottom: 0;">
                    <div>
                        <div class="op-label">URL de Sucesso (Opcional)</div>
                        <input type="text" class="op-input" placeholder="https://" disabled title="Disponível em breve">
                    </div>
                    <div>
                        <div class="op-label">URL de Falha (Opcional)</div>
                        <input type="text" class="op-input" placeholder="https://" disabled title="Disponível em breve">
                    </div>
                </div>
            </div>

            <!-- Customization Accordion -->
            <div class="op-card" id="customization-accordion" style="margin-bottom: 16px;">
                <div class="op-card-header" style="cursor: pointer; user-select: none;" onclick="toggleCustomization()">
                    <div>
                        <h2 class="op-card-title">3. Personalização do Checkout</h2>
                        <p style="margin:4px 0 0 0; font-size:0.7rem; color:var(--op-text-muted);">Logo, cores e textos exibidos na p&aacute;gina.</p>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span id="cust-status" style="font-size:0.7rem; font-weight:600; color:var(--op-primary);">Padr&atilde;o</span>
                        <i class="fas fa-chevron-down" id="cust-icon" style="transition: transform 0.3s; color:var(--op-text-muted);"></i>
                    </div>
                </div>

                <div id="cust-body" style="display:none; padding-top: 16px; border-top: 1px solid var(--op-border); margin-top: 12px;">
                    <div class="op-row">
                        <div>
                            <div class="op-label">Logo no checkout (Opcional)</div>
                            <input type="file" id="in-logo" name="logo" accept="image/png, image/jpeg, image/jpg, image/webp" style="display:none;">
                            
                            <div id="upload-card" tabindex="0" style="
                                display: flex; 
                                align-items: center; 
                                gap: 12px; 
                                padding: 12px 16px; 
                                background: #0B0D12; 
                                border: 1px dashed #232733; 
                                border-radius: 14px; 
                                cursor: pointer; 
                                transition: var(--transition);
                            " onmouseover="this.style.borderColor='var(--op-primary)'" onmouseout="this.style.borderColor='#232733'" onclick="document.getElementById('in-logo').click()">
                                
                                <div id="upload-state-empty" style="display: flex; align-items: center; gap: 12px; width: 100%;">
                                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(124,58,237,0.1); color: var(--op-primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-size: 0.8rem; font-weight: 500; color: #FFF;">Enviar logo</span>
                                        <span style="font-size: 0.65rem; color: var(--op-text-muted);">PNG, JPG ou WEBP até 2MB</span>
                                        <span style="font-size: 0.6rem; color: var(--op-text-dark);">Recomendado: 512×512</span>
                                    </div>
                                </div>

                                <div id="upload-state-filled" style="display: none; align-items: center; justify-content: space-between; width: 100%;">
                                    <div style="display: flex; align-items: center; gap: 12px; overflow:hidden;">
                                        <img id="upload-thumbnail" src="" style="width: 36px; height: 36px; object-fit: contain; border-radius: 6px; background: #000; border: 1px solid #232733; flex-shrink: 0;">
                                        <span id="upload-filename" style="font-size: 0.75rem; color: #FFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px;"></span>
                                    </div>
                                    <button type="button" class="op-btn-secondary" style="height: 28px; padding: 0 10px; font-size: 0.65rem; flex-shrink: 0;" onclick="event.stopPropagation(); window.removeLogo();"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            
                            <div id="upload-error" class="op-error-msg" style="display:none; margin-top: 6px;"><i class="fas fa-info-circle"></i> <span id="upload-error-text"></span></div>
                            @error('logo') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                        </div>
                        <div>
                            <div class="op-label">Cor principal</div>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input type="color" id="in-color-picker" value="#7C3AED" style="width:36px; height:36px; padding:0; border:1px solid var(--op-border); border-radius:4px; cursor:pointer; background:transparent;">
                                <input type="text" id="in-primary-color" name="primary_color" class="op-input" value="{{ old('primary_color', '#7C3AED') }}" placeholder="#7C3AED" pattern="^#[0-9A-Fa-f]{6}$" title="Hexadecimal ex: #7C3AED">
                            </div>
                            @error('primary_color') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="op-row">
                        <div>
                            <div class="op-label">Cor de fundo</div>
                            <select id="in-bg-theme" name="bg_theme" class="op-input">
                                <option value="dark" {{ old('bg_theme') == 'dark' ? 'selected' : '' }}>Escuro (Padr&atilde;o)</option>
                                <option value="light" {{ old('bg_theme') == 'light' ? 'selected' : '' }}>Claro</option>
                                <option value="custom" {{ old('bg_theme') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                            </select>
                            @error('bg_theme') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                        </div>
                        <div id="custom-bg-wrapper" style="display: {{ old('bg_theme') == 'custom' ? 'block' : 'none' }};">
                            <div class="op-label">Escolha a cor de fundo</div>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input type="color" id="in-bg-picker" value="#0E0E11" style="width:36px; height:36px; padding:0; border:1px solid var(--op-border); border-radius:4px; cursor:pointer; background:transparent;">
                                <input type="text" id="in-bg-color" name="bg_color" class="op-input" value="{{ old('bg_color', '#0E0E11') }}" placeholder="#0E0E11" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                            @error('bg_color') <div class="op-error-msg" style="display:flex"><i class="fas fa-info-circle"></i> {{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Footer -->
            <div class="op-footer">
                <span class="op-footer-text">Pronto para publicar?</span>
                <div class="op-actions">
                    <a href="{{ route('user.payment-links.index') }}" class="op-btn-secondary" style="display:flex; align-items:center; text-decoration:none;">Cancelar</a>
                    <button type="submit" class="op-btn-primary" id="btn-submit" @disabled($paymentMethods->isEmpty())>
                        <i class="fas fa-link" id="btn-submit-icon"></i> 
                        <span id="btn-submit-text">Publicar Link</span>
                    </button>
                </div>
            </div>
            
        </div>

        <!-- Right Column: Faithful Checkout Preview -->
        <div class="op-preview-wrapper">

            <div class="chk-card" id="live-mock">

                <!-- Head -->
                <div class="chk-head">
                    <div class="chk-head-top">
                        <div class="chk-avatar">
                            <img src="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" alt="OriginPay" id="prev-logo">
                        </div>
                        <div class="chk-info">
                            <div class="chk-title" id="prev-title">Nome do Produto</div>
                            <div class="chk-seller">{{ auth()->user()->name ?? 'Sua Empresa' }}</div>
                        </div>
                        <div class="chk-amount-col">
                            <div class="chk-price" id="prev-price">R$ 0,00</div>
                            <div class="chk-currency">BRL</div>
                        </div>
                    </div>
                    <div class="chk-badge"><i class="fas fa-clock"></i> Aguardando pagamento</div>
                </div>

                <!-- Method Tabs -->
                <div class="chk-method-section">
                    <div class="chk-method-label">Como deseja pagar?</div>
                    <div class="chk-method-tabs" id="prev-methods">
                        @forelse($paymentMethods as $method)
                            <div class="chk-method-tab {{ $loop->first ? 'active' : '' }}">
                                <i class="{{ $method['icon_class'] }}"></i>
                                <span>{{ $method['label'] }}</span>
                            </div>
                        @empty
                            <span style="color:#71717A; font-size:0.7rem;">Nenhum metodo disponivel</span>
                        @endforelse
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="chk-form">
                    <div class="chk-form-grid" style="margin-top:10px;">
                        <div class="chk-field">
                            <label>Nome completo</label>
                            <div class="chk-fake-input"><i class="fas fa-user"></i><span style="color:#3A3A50">Jo&atilde;o da Silva</span></div>
                        </div>
                        <div class="chk-field">
                            <label>E-mail</label>
                            <div class="chk-fake-input"><i class="fas fa-envelope"></i><span style="color:#3A3A50">joao@email.com</span></div>
                        </div>
                        <div class="chk-field">
                            <label>CPF / CNPJ</label>
                            <div class="chk-fake-input"><i class="fas fa-id-card"></i><span style="color:#3A3A50">000.000.000-00</span></div>
                        </div>
                        <div class="chk-field">
                            <label>Telefone (opcional)</label>
                            <div class="chk-fake-input"><i class="fas fa-phone"></i><span style="color:#3A3A50">(11) 99999-9999</span></div>
                        </div>
                    </div>
                </div>

                <!-- Button -->
                <div class="chk-submit-area">
                    <div class="chk-btn" id="prev-btn">
                        <i class="fas fa-lock"></i>
                        <span id="prev-btn-text">Pagar R$ 0,00</span>
                    </div>
                </div>

                <!-- Seal -->
                <div class="chk-seal">
                    <i class="fas fa-shield-halved"></i>
                    Pagamento seguro via <strong style="margin-left:3px;color:#A1A1AA;">OriginPay</strong>
                </div>

            </div>

            <div class="op-link-box">
                <p>O link público será gerado após salvar.</p>
                <div class="op-fake-url">pay.originpay.com/l/xxxxxxxx</div>
            </div>

        </div>
        
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Inputs
    const inTitle = document.getElementById('in-title');
    const inDesc = document.getElementById('in-desc');
    const countTitle = document.getElementById('count-title');
    const countDesc = document.getElementById('count-desc');
    const maskInput = document.getElementById('in-amount-mask');
    const realInput = document.getElementById('in-amount-real');
    
    // Preview
    const prevTitle = document.getElementById('prev-title');
    const prevPrice = document.getElementById('prev-price');
    const prevMethods = document.getElementById('prev-methods');
    const prevBtnText = document.getElementById('prev-btn-text');
    const mockContainer = document.getElementById('live-mock');
    const prevLogo = document.getElementById('prev-logo');
    
    // 1. Text & Counters
    function syncText(input, counter, max, target, defaultText) {
        input.addEventListener('input', function() {
            let val = this.value;
            counter.innerText = `${val.length}/${max}`;
            this.classList.remove('is-invalid');
            
            if(val.trim() === '') {
                target.innerText = defaultText;
                target.style.opacity = '0.4';
            } else {
                target.innerText = val;
                target.style.opacity = '1';
            }
        });
        input.dispatchEvent(new Event('input'));
    }
    
    syncText(inTitle, countTitle, 80, prevTitle, 'Nome do Produto');
    
    inDesc.addEventListener('input', function() {
        let val = this.value;
        countDesc.innerText = `${val.length}/200`;
    });
    function formatCurrency(val) {
        val = val.replace(/\D/g, "");
        if (!val) return "";
        val = (parseInt(val, 10) / 100).toFixed(2);
        return val.replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    function parseReal(val) {
        if(!val) return 0;
        return parseFloat(val.replace(/\./g, "").replace(",", "."));
    }
    
    if (realInput.value && parseFloat(realInput.value) > 0) {
        maskInput.value = formatCurrency((parseFloat(realInput.value) * 100).toFixed(0));
    }
    
    maskInput.addEventListener('input', function() {
        this.value = formatCurrency(this.value);
        let realVal = parseReal(this.value);
        realInput.value = realVal;
        
        this.classList.remove('is-invalid');
        
        if(realVal > 0) {
            prevPrice.innerText = 'R$ ' + this.value;
            prevBtnText.innerText = 'Pagar R$ ' + this.value;
            prevPrice.style.opacity = '1';
        } else {
            prevPrice.innerText = 'R$ 0,00';
            prevBtnText.innerText = 'Pagar R$ 0,00';
            prevPrice.style.opacity = '0.4';
        }
    });
    maskInput.dispatchEvent(new Event('input'));

    // 3. Methods
    const methodLabels = document.querySelectorAll('.op-method');
    const paymentMethodMeta = @json($paymentMethods->keyBy('code')->map(fn ($method) => [
        'label' => $method['label'],
        'icon_class' => $method['icon_class'],
    ]));

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    }

    function renderMethodTab(method, isActive) {
        const meta = paymentMethodMeta[method] || { label: method, icon_class: 'fas fa-wallet' };
        return `<div class="chk-method-tab ${isActive ? 'active' : ''}"><i class="${escapeHtml(meta.icon_class)}"></i><span>${escapeHtml(meta.label)}</span></div>`;
    }

    function syncMethods() {
        prevMethods.innerHTML = '';
        let isFirst = true;
        document.querySelectorAll('.pm-checkbox').forEach(cb => {
            const label = cb.closest('.op-method');
            if(cb.checked) {
                label.classList.add('active');
                prevMethods.innerHTML += renderMethodTab(cb.value, isFirst);
                isFirst = false;
            } else {
                label.classList.remove('active');
            }
        });
        
        if(prevMethods.innerHTML === '') {
            prevMethods.innerHTML = '<span style="color:#71717A; font-size:0.7rem;">Nenhum selecionado</span>';
        }
    }
    
    methodLabels.forEach(label => {
        label.addEventListener('click', function(e) {
            if(e.target.tagName !== 'INPUT') {
                const cb = this.querySelector('input');
                cb.checked = !cb.checked;
            }
            syncMethods();
        });
    });
    syncMethods();

    // 4. Expires
    const pills = document.querySelectorAll('.op-pill');
    const inExpires = document.getElementById('in-expires');
    const customDateWrapper = document.getElementById('custom-date-wrapper');
    const inExpiresCustom = document.getElementById('in-expires-custom');
    
    // Sync hidden input when date changes
    inExpiresCustom.addEventListener('input', function() {
        if(this.value) {
            inExpires.value = this.value + 'T23:59';
        } else {
            inExpires.value = '';
        }
    });

    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            pills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            
            let val = this.dataset.val;
            
            if(val === 'custom') {
                customDateWrapper.style.display = 'block';
                // If there's an existing custom value, set it in the visual input
                if(inExpires.value) {
                    inExpiresCustom.value = inExpires.value.substring(0, 10); 
                }
                return;
            }
            
            customDateWrapper.style.display = 'none';
            inExpiresCustom.value = '';
            
            if(!val) { 
                inExpires.value = ''; 
                return; 
            }
            
            let d = new Date();
            if(val === '24h') d.setHours(d.getHours() + 24);
            else if(val === '7d') d.setDate(d.getDate() + 7);
            
            const pad = n => n < 10 ? '0'+n : n;
            inExpires.value = d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        });
    });

    // Check on load if custom was selected (validation error state)
    if(inExpires.value && !['24h', '7d', ''].includes(document.querySelector('.op-pill.active')?.dataset.val)) {
        // Find if any preset matches the value (rare, but let's assume it's custom)
        // Here we just fallback to Custom logic if the value isn't empty and the active pill is 'custom'
        if(document.querySelector('.op-pill[data-val="custom"]').classList.contains('active')) {
             customDateWrapper.style.display = 'block';
             inExpiresCustom.value = inExpires.value.substring(0, 10);
        }
    }

    // 6. Customization Logic
    // Accordion Toggle
    window.toggleCustomization = function() {
        const body = document.getElementById('cust-body');
        const icon = document.getElementById('cust-icon');
        if(body.style.display === 'none') {
            body.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        } else {
            body.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    };

    const prevSecurityMsg = document.getElementById('prev-security-msg') || null;
    const defaultSecurityMsg = "Pagamento seguro via OriginPay";

    // Logo Upload & Preview
    const inLogo = document.getElementById('in-logo');
    const uploadStateEmpty = document.getElementById('upload-state-empty');
    const uploadStateFilled = document.getElementById('upload-state-filled');
    const uploadThumbnail = document.getElementById('upload-thumbnail');
    const uploadFilename = document.getElementById('upload-filename');
    const uploadError = document.getElementById('upload-error');
    const uploadErrorText = document.getElementById('upload-error-text');
    
    inLogo.addEventListener('change', function(e) {
        const file = e.target.files[0];
        uploadError.style.display = 'none';
        
        if(file) {
            if(file.size > 2097152) {
                uploadErrorText.innerText = "A imagem excede 2MB.";
                uploadError.style.display = 'flex';
                window.removeLogo();
                return;
            }
            if(!['image/png', 'image/jpeg', 'image/jpg', 'image/webp'].includes(file.type)) {
                uploadErrorText.innerText = "Formato inválido. Use PNG, JPG ou WEBP.";
                uploadError.style.display = 'flex';
                window.removeLogo();
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                uploadThumbnail.src = event.target.result;
                uploadFilename.innerText = file.name;
                uploadStateEmpty.style.display = 'none';
                uploadStateFilled.style.display = 'flex';
                
                // Update Live Mockup
                if(prevLogo) {
                    prevLogo.src = event.target.result;
                    prevLogo.style.width = '100%';
                    prevLogo.style.height = '100%';
                    prevLogo.parentElement.style.background = 'linear-gradient(135deg, #7C3AED, #9B5DE5)';
                }
                document.getElementById('cust-status').innerText = 'Personalizado';
            };
            reader.readAsDataURL(file);
        }
    });

    window.removeLogo = function() {
        inLogo.value = '';
        uploadStateEmpty.style.display = 'flex';
        uploadStateFilled.style.display = 'none';
        uploadThumbnail.src = '';
        uploadFilename.innerText = '';
        
        if(prevLogo) {
            prevLogo.src = "{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}";
            prevLogo.parentElement.style.background = 'transparent';
        }
    };

    // Sync Colors
    const inColorPicker = document.getElementById('in-color-picker');
    const inPrimaryColor = document.getElementById('in-primary-color');
    const btnPreview = document.querySelector('.chk-btn');

    function updatePrimaryColor(hex) {
        const btnPrev = document.getElementById('prev-btn');
        if(btnPrev) {
            btnPrev.style.background = hex;
            btnPrev.style.boxShadow = `0 4px 14px ${hex}59`; // Add 59 (hex for ~35% opacity) for shadow
        }
        document.documentElement.style.setProperty('--op-primary', hex);
        document.getElementById('cust-status').innerText = 'Personalizado';
    }

    inColorPicker.addEventListener('input', function() {
        inPrimaryColor.value = this.value.toUpperCase();
        updatePrimaryColor(this.value);
    });

    inPrimaryColor.addEventListener('input', function() {
        if(/^#[0-9A-Fa-f]{6}$/i.test(this.value)) {
            inColorPicker.value = this.value;
            updatePrimaryColor(this.value);
        }
    });

    // Background Color Logic
    const inBgTheme = document.getElementById('in-bg-theme');
    const customBgWrapper = document.getElementById('custom-bg-wrapper');
    const inBgPicker = document.getElementById('in-bg-picker');
    const inBgColor = document.getElementById('in-bg-color');
    const mockBody = document.querySelector('.chk-body');

    function updateBgTheme() {
        let theme = inBgTheme.value;
        if(theme === 'dark') {
            customBgWrapper.style.display = 'none';
            if(mockContainer) {
                mockContainer.style.background = '#111218';
                mockContainer.style.borderColor = 'rgba(124,58,237,0.15)';
                document.querySelector('.chk-title').style.color = '#F0F0F5';
                document.querySelector('.chk-price').style.color = '#F0F0F5';
            }
            document.getElementById('cust-status').innerHTML = 'Padr&atilde;o';
        } else if(theme === 'light') {
            customBgWrapper.style.display = 'none';
            if(mockContainer) {
                mockContainer.style.background = '#FFFFFF';
                mockContainer.style.borderColor = 'rgba(0,0,0,0.08)';
                document.querySelector('.chk-title').style.color = '#0F0F1A';
                document.querySelector('.chk-price').style.color = '#0F0F1A';
            }
            document.getElementById('cust-status').innerText = 'Personalizado';
        } else if(theme === 'custom') {
            customBgWrapper.style.display = 'block';
            applyCustomBg(inBgColor.value);
            document.getElementById('cust-status').innerText = 'Personalizado';
        }
    }

    function applyCustomBg(hex) {
        if(!mockContainer) return;
        mockContainer.style.background = hex;
        // Simple contrast check to adjust text colors
        let r = parseInt(hex.substr(1, 2), 16) || 0;
        let g = parseInt(hex.substr(3, 2), 16) || 0;
        let b = parseInt(hex.substr(5, 2), 16) || 0;
        let yiq = ((r*299)+(g*587)+(b*114))/1000;
        let textColor = (yiq >= 128) ? '#0F0F1A' : '#F0F0F5';
        
        document.querySelector('.chk-title').style.color = textColor;
        document.querySelector('.chk-price').style.color = textColor;
    }

    inBgTheme.addEventListener('change', updateBgTheme);

    inBgPicker.addEventListener('input', function() {
        inBgColor.value = this.value.toUpperCase();
        applyCustomBg(this.value);
    });

    inBgColor.addEventListener('input', function() {
        if(/^#[0-9A-Fa-f]{6}$/i.test(this.value)) {
            inBgPicker.value = this.value;
            applyCustomBg(this.value);
        }
    });
    // Update Title in Preview
    inTitle.addEventListener('input', function() {
        let val = this.value;
        prevTitle.innerText = val.trim() !== '' ? val : 'Seu produto';
    });

    // 5. Submit Validation (Existing, moved down slightly)
    document.getElementById('pl-form').addEventListener('submit', function(e) {
        let valid = true;
        
        if(inTitle.value.trim() === '') { inTitle.classList.add('is-invalid'); document.getElementById('err-title').style.display = 'flex'; valid = false; }
        if(parseReal(maskInput.value) <= 0) { maskInput.classList.add('is-invalid'); document.getElementById('err-amount').style.display = 'flex'; valid = false; }
        if(document.querySelectorAll('.pm-checkbox:checked').length === 0) { document.getElementById('err-methods').style.display = 'flex'; valid = false; }
        
        // Also validate hex fields if present
        if(inPrimaryColor.value && !/^#[0-9A-Fa-f]{6}$/i.test(inPrimaryColor.value)) { inPrimaryColor.classList.add('is-invalid'); valid = false; }
        if(inBgTheme.value === 'custom' && !/^#[0-9A-Fa-f]{6}$/i.test(inBgColor.value)) { inBgColor.classList.add('is-invalid'); valid = false; }

        if(!valid) {
            e.preventDefault();
            return false;
        }
        
        const btn = document.getElementById('btn-submit');
        const icon = document.getElementById('btn-submit-icon');
        const txt = document.getElementById('btn-submit-text');
        
        btn.disabled = true;
        icon.className = 'fas fa-circle-notch fa-spin';
        txt.innerText = 'Publicando...';
    });
});
</script>
@endpush
