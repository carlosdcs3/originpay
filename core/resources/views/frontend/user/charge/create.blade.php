@extends('frontend.layouts.user-v2')
@section('title', __('Nova Cobrança'))

@section('content')

<style>
/* Method Selector */
.method-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px; }
.method-card { background: var(--ds-surface-elevated); border: 1px solid var(--ds-border); border-radius: 12px; padding: 16px; text-align: center; cursor: pointer; transition: all 0.2s; position: relative; }
.method-card:hover { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.03); }
.method-card.selected { border-color: var(--ds-primary); background: rgba(124, 58, 237, 0.08); }
.method-card .check-icon { display: none; position: absolute; top: 10px; right: 10px; font-size: 1.1rem; color: var(--ds-primary); }
.method-card.selected .check-icon { display: block; }
.method-icon { font-size: 1.6rem; margin-bottom: 8px; color: var(--ds-text-muted); }
.method-card.selected .method-icon { color: var(--ds-primary); }
.method-title { font-size: 0.85rem; font-weight: 600; color: var(--ds-text-primary); margin-bottom: 4px; }
.method-desc { font-size: 0.7rem; color: var(--ds-text-muted); }

/* Optional Section Toggle */
.advanced-toggle { background: none; border: none; color: var(--ds-primary); font-size: 0.8rem; font-weight: 600; cursor: pointer; padding: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 6px; }
.advanced-toggle:hover { text-decoration: underline; }
.advanced-section { display: none; padding: 16px; background: rgba(255,255,255,0.02); border: 1px dashed var(--ds-border); border-radius: 12px; margin-bottom: 20px; }
.charge-feedback { display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 10px; border: 1px solid transparent; font-size: 0.8rem; line-height: 1.45; }
.charge-feedback.is-error { display: block; background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.22); color: #FCA5A5; }
.charge-feedback.is-success { display: block; background: rgba(0,229,200,0.08); border-color: rgba(0,229,200,0.2); color: var(--ds-pix); }

/* Preview */
.preview-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 0.85rem; }
.preview-row:last-child { border-bottom: none; }
.preview-label { color: var(--ds-text-muted); }
.preview-val { color: var(--ds-text-primary); font-weight: 500; text-align: right; }
.badge-status { padding: 4px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 600; background: rgba(255,255,255,0.1); color: var(--ds-text-muted); }
.badge-status.active { background: rgba(0,229,200,0.1); color: var(--ds-pix); }

/* Success State */
.success-box { text-align: center; padding: 20px 0; display: none; }
.success-icon { font-size: 3rem; color: var(--ds-pix); margin-bottom: 16px; }
.qr-box { background: #fff; padding: 16px; border-radius: 12px; display: inline-block; margin-bottom: 16px; }
.qr-box img { max-width: 180px; }
.copy-paste-box { background: rgba(0,0,0,0.3); border: 1px dashed var(--ds-border); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 0.75rem; word-break: break-all; color: var(--ds-text-muted); margin-bottom: 16px; }

/* Inputs and Overrides */
.form-control, .form-select { background-color: rgba(0,0,0,0.2) !important; color: #e2e8f0 !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 10px; padding: 12px 16px; appearance: auto; }
.form-control:focus, .form-select:focus { background-color: rgba(0,0,0,0.3) !important; border-color: var(--ds-primary) !important; box-shadow: none !important; }
.form-control::placeholder, .form-select::placeholder { color: #64748b !important; }
.form-select option { background-color: #12141c; color: #e2e8f0; }

.ds-btn-outline { background: transparent; border: 1px solid var(--ds-border); color: var(--ds-text-primary); padding: 12px 16px; border-radius: 12px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.ds-btn-outline:hover { background: rgba(255,255,255,0.05); }

/* Custom Checkbox */
.custom-checkbox {
    width: 20px;
    height: 20px;
    appearance: none;
    background-color: rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
}
.custom-checkbox:checked {
    background-color: var(--ds-primary);
    border-color: var(--ds-primary);
}
.custom-checkbox:checked::after {
    content: '\f00c';
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    color: #12141c;
    font-size: 0.7rem;
}

/* Recent Charges Widget */
.recent-charges { margin-top: 30px; }
.recent-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 10px; margin-bottom: 8px; font-size: 0.85rem; transition: background 0.2s; }
.recent-item:hover { background: rgba(255,255,255,0.02); }
.recent-item .info { display: flex; align-items: center; gap: 12px; }
.recent-item .icon { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; color: var(--ds-text-muted); }
</style>

<div class="v2-page-header" style="margin:0 0 18px;justify-content:space-between;align-items:center;">
    <div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;color:var(--ds-text-muted);font-size:.76rem;font-weight:700;">
            <span>Pagamentos</span><i class="fas fa-chevron-right" style="font-size:.58rem;"></i><span>Cobranças</span><i class="fas fa-chevron-right" style="font-size:.58rem;"></i><span>Nova cobrança</span>
        </div>
        <h1 class="v2-page-title" style="margin-bottom:2px;">Nova cobrança</h1>
        <p class="v2-page-subtitle" style="margin:0;">Cobre um cliente já cadastrado ou cadastre um novo cliente e gere a cobrança imediatamente.</p>
    </div>
    <a href="{{ route('user.charge.index') }}" class="v2-btn-secondary" style="height:36px;padding:0 14px;text-decoration:none;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="ds-tx-grid">

    {{-- ── FORM CARD ──────────────────────────────────────────── --}}
    <div class="ds-card" id="form-container">
        <div class="ds-card-header">
            <span class="v2-card-title">
                <i class="fas fa-file-invoice-dollar" style="color:var(--ds-primary);margin-right:6px;"></i>
                Criar Nova Cobrança
            </span>
        </div>
        
        <div class="ds-card-body padded">
            <div id="charge-feedback" class="charge-feedback" role="status" aria-live="polite"></div>
            <form id="chargeForm" onsubmit="submitCharge(event)">
                @csrf
                
                <h5 style="font-size:0.85rem; font-weight:600; color:var(--ds-text-muted); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.05em;">1. Método de Pagamento</h5>
                <div class="method-grid">
                    @forelse($paymentMethods as $method)
                        <div class="method-card {{ $loop->first ? 'selected' : '' }}" onclick="selectMethod('{{ $method['code'] }}', '{{ e($method['label']) }}')" id="method-{{ $method['code'] }}">
                            <i class="fas fa-check-circle check-icon"></i>
                            <div class="method-icon">
                                <i class="{{ $method['icon_class'] }}"></i>
                            </div>
                            <div class="method-title">{{ $method['label'] }}</div>
                            <div class="method-desc">{{ $method['description'] }}</div>
                        </div>
                    @empty
                        <div style="grid-column: 1 / -1; padding: 16px; background: rgba(255,77,106,0.1); border: 1px dashed #FF4D6A; border-radius: 12px; color: #FF4D6A; text-align: center;">
                            <i class="fas fa-exclamation-triangle"></i> Nenhum metodo de pagamento esta disponivel. Entre em contato com o administrador.
                        </div>
                    @endforelse
                </div>
                <input type="hidden" name="method" id="input-method" value="{{ $paymentMethods->first()['code'] ?? '' }}">
                <input type="hidden" id="input-method-name" value="{{ $paymentMethods->first()['label'] ?? '' }}">

                <h5 style="font-size:0.85rem; font-weight:600; color:var(--ds-text-muted); margin-bottom:12px; margin-top:24px; text-transform:uppercase; letter-spacing:0.05em;">2. Detalhes da Cobrança</h5>
                
                <div class="ds-form-group">
                    <label class="ds-label">Valor</label>
                    <div class="ds-input-group">
                        <span class="ds-input-addon">R$</span>
                        <input type="text" name="amount" id="input-amount" class="v2-input" placeholder="0,00" required oninput="updatePreview()">
                    </div>
                </div>

                <div class="ds-form-group">
                    <label class="ds-label">Descrição / Referência</label>
                    <input type="text" name="description" id="input-desc" class="v2-input" placeholder="Ex: Assinatura Premium, Fatura #1234" required oninput="updatePreview()">
                </div>

                <button type="button" class="advanced-toggle" onclick="toggleAdvanced()">
                    <i class="fas fa-plus-circle"></i> Adicionar dados do cliente (Opcional)
                </button>
                
                <div class="advanced-section" id="advanced-section">
                    <div class="ds-form-group">
                        <label class="ds-label">Nome do Cliente</label>
                        <input type="text" name="customer_name" id="input-customer" class="v2-input" placeholder="Nome completo" oninput="updatePreview()">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div class="ds-form-group">
                            <label class="ds-label">CPF/CNPJ</label>
                            <input type="text" name="customer_document" class="v2-input" placeholder="000.000.000-00">
                        </div>
                        <div class="ds-form-group">
                            <label class="ds-label">E-mail</label>
                            <input type="email" name="customer_email" class="v2-input" placeholder="email@cliente.com">
                        </div>
                    </div>
                </div>

                <div class="ds-form-group">
                    <label class="ds-label">Expiração</label>
                    <select name="expires_at" id="input-expires" class="v2-input" onchange="updatePreview()">
                        <option value="1h">1 hora</option>
                        <option value="6h">6 horas</option>
                        <option value="24h" selected>24 horas</option>
                        <option value="3d">3 dias</option>
                        <option value="7d">7 dias</option>
                    </select>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="ds-btn-submit w-100" id="submitBtn" @disabled($paymentMethods->isEmpty())>
                        <i class="fas fa-check"></i> Criar Cobrança
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── RIGHT COLUMN: SUMMARY & SUCCESS ───────────────────── --}}
    <div>
        <div class="ds-card" id="preview-card" style="position: sticky; top: 100px;">
            <div class="ds-card-header" style="justify-content: space-between;">
                <span class="v2-card-title">Resumo</span>
                <div class="badge-status" id="badge-status">Aguardando</div>
            </div>
            
            <div class="ds-card-body padded">
                {{-- Default Preview State --}}
                <div id="preview-state">
                    <div style="text-align:center; margin-bottom:20px;">
                        <div style="font-size:2rem; font-weight:700; color:var(--ds-text-primary);" id="preview-amount-display">R$ 0,00</div>
                    </div>
                    
                    <div class="preview-row">
                        <span class="preview-label">Método</span>
                        <span class="preview-val" id="preview-method-display">PIX</span>
                    </div>
                    <div class="preview-row">
                        <span class="preview-label">Descrição</span>
                        <span class="preview-val" id="preview-desc-display">-</span>
                    </div>
                    <div class="preview-row">
                        <span class="preview-label">Cliente</span>
                        <span class="preview-val" id="preview-customer-display">Não informado</span>
                    </div>
                    <div class="preview-row">
                        <span class="preview-label">Expiração</span>
                        <span class="preview-val" id="preview-expires-display">Em 24 horas</span>
                    </div>
                    
                    <div style="margin-top:20px; padding-top:16px; border-top:1px dashed var(--ds-border);">
                        <div class="preview-row">
                            <span class="preview-label">Tarifa</span>
                            <span class="preview-val" style="color:var(--ds-text-muted);">Calculada ao criar</span>
                        </div>
                        <div class="preview-row" style="font-weight:700;">
                            <span class="preview-label" style="color:var(--ds-text-primary);">Liquidacao</span>
                            <span class="preview-val" style="color:var(--ds-pix);" id="preview-net-display">Disponivel apos criar</span>
                        </div>
                    </div>
                </div>

                {{-- Success State --}}
                <div class="success-box" id="success-state">
                    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                    <h5 style="color:var(--ds-text-primary);font-weight:700;margin-bottom:8px;">Cobrança Criada!</h5>
                    <p style="font-size:0.8rem;color:var(--ds-text-muted);margin-bottom:20px;">Pronto, agora é só compartilhar com o pagador.</p>

                    <div id="success-pix-area" style="display:none;">
                        <div class="qr-box">
                            <img id="success-qr" alt="QR Code" style="display:none;">
                        </div>
                        <p style="font-size:0.75rem; font-weight:600; color:var(--ds-text-primary); margin-bottom:4px;">PIX Copia e Cola</p>
                        <div class="copy-paste-box" id="success-copy-paste"></div>
                        <button class="ds-btn-outline w-100 mb-3" onclick="copyToClipboard('success-copy-paste')">
                            <i class="far fa-copy"></i> Copiar Código PIX
                        </button>
                    </div>

                    <div id="success-link-area" style="display:none;">
                        <button class="ds-btn-submit w-100 mb-3" onclick="copyToClipboard('success-payment-link')">
                            <i class="fas fa-link"></i> Copiar Link de Pagamento
                        </button>
                        <input type="hidden" id="success-payment-link" value="">
                    </div>

                    <button class="ds-btn-outline w-100" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Nova Cobrança
                    </button>
                </div>
            </div>
        </div>

        {{-- Últimas Cobranças (dados reais) --}}
        <div class="recent-charges">
            <h5 style="font-size:0.85rem; font-weight:600; color:var(--ds-text-muted); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.05em;">Últimas Cobranças</h5>

            @forelse($recentCharges as $rc)
            <div class="recent-item">
                <div class="info">
                    <div class="icon" style="color:var(--ds-primary);background:rgba(124,58,237,0.1);">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <div style="font-weight:600; color:var(--ds-text-primary);">{{ siteCurrency() }} {{ number_format($rc->amount, 2, ',', '.') }}</div>
                        <div style="font-size:0.7rem; color:var(--ds-text-muted);">{{ $rc->customer_name ?: $rc->created_at->format('d/m H:i') }}</div>
                    </div>
                </div>
                @if($rc->status->value === 'paid')
                    <div class="badge-status active">Pago</div>
                @elseif($rc->status->value === 'waiting_payment')
                    <div class="badge-status" style="background:rgba(245,158,11,0.1);color:#F59E0B;">Aguardando</div>
                @elseif(in_array($rc->status->value, ['expired','cancelled']))
                    <div class="badge-status" style="background:rgba(239,68,68,0.1);color:#EF4444;">Expirado</div>
                @else
                    <div class="badge-status">{{ ucfirst($rc->status->value) }}</div>
                @endif
            </div>
            @empty
            <div style="text-align:center;padding:20px;color:rgba(255,255,255,0.25);font-size:0.82rem;">
                <i class="fas fa-file-invoice-dollar" style="display:block;font-size:1.5rem;margin-bottom:8px;opacity:0.3;"></i>
                Nenhuma cobrança ainda
            </div>
            @endforelse

            <a href="{{ route('user.charge.index') }}" style="display:block; text-align:center; font-size:0.8rem; color:var(--ds-primary); margin-top:12px; font-weight:500;">Ver Todas →</a>
        </div>
    </div>
</div>

<script>
    function setChargeFeedback(type, message) {
        const feedback = document.getElementById('charge-feedback');
        if (!feedback) {
            return;
        }

        feedback.className = 'charge-feedback';
        feedback.textContent = message || '';

        if (!message) {
            return;
        }

        feedback.classList.add(type === 'success' ? 'is-success' : 'is-error');
    }

    function normalizeAmountValue(value) {
        const normalized = (value || '')
            .replace(/\s/g, '')
            .replace(/\./g, '')
            .replace(',', '.')
            .replace(/[^0-9.]/g, '');

        return normalized || '0';
    }

    function selectMethod(methodType, methodName) {
        document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
        const card = document.getElementById('method-' + methodType);
        if(card) card.classList.add('selected');
        document.getElementById('input-method').value = methodType;   // ex: 'pix', 'card'
        document.getElementById('input-method-name').value = methodName;
        updatePreview();
    }

    function toggleAdvanced() {
        const sec = document.getElementById('advanced-section');
        sec.style.display = sec.style.display === 'block' ? 'none' : 'block';
    }

    function updatePreview() {
        const amt = document.getElementById('input-amount').value || '0,00';
        const desc = document.getElementById('input-desc').value || '-';
        const cust = document.getElementById('input-customer').value || 'Nao informado';
        const expElement = document.getElementById('input-expires');
        const exp = expElement.options[expElement.selectedIndex].text;
        
        const methodName = document.getElementById('input-method-name').value || '-';

        document.getElementById('preview-amount-display').innerText = 'R$ ' + amt;
        document.getElementById('preview-net-display').innerText = 'Disponivel apos criar';
        document.getElementById('preview-desc-display').innerText = desc;
        document.getElementById('preview-customer-display').innerText = cust;
        document.getElementById('preview-expires-display').innerText = 'Em ' + exp;
        document.getElementById('preview-method-display').innerText = methodName;
    }

    async function submitCharge(e) {
        e.preventDefault();

        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerHTML;
        const form = document.getElementById('chargeForm');
        const badge = document.getElementById('badge-status');
        const formData = new FormData(form);

        setChargeFeedback(null, '');
        formData.set('amount', normalizeAmountValue(document.getElementById('input-amount').value));

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        btn.disabled = true;
        badge.innerText = 'Processando...';

        try {
            const response = await fetch("{{ route('user.charge.store') }}", {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || data.error || 'Nao foi possivel criar a cobranca.');
            }

            document.getElementById('form-container').style.opacity = '0.5';
            document.getElementById('form-container').style.pointerEvents = 'none';
            document.getElementById('preview-state').style.display = 'none';
            document.getElementById('success-state').style.display = 'block';
            badge.innerText = 'Criada';

            const qrArea = document.getElementById('success-pix-area');
            const qrImage = document.getElementById('success-qr');
            const copyPaste = document.getElementById('success-copy-paste');
            const linkArea = document.getElementById('success-link-area');
            const linkInput = document.getElementById('success-payment-link');

            qrArea.style.display = 'none';
            linkArea.style.display = 'none';
            qrImage.removeAttribute('src');
            qrImage.style.display = 'none';
            copyPaste.innerText = '';
            linkInput.value = '';

            if (data.charge.qr_code && data.charge.copy_paste) {
                qrArea.style.display = 'block';
                qrImage.src = data.charge.qr_code;
                qrImage.style.display = 'block';
                copyPaste.innerText = data.charge.copy_paste;
            }

            const shareableLink = data.charge.public_payment_link || data.charge.payment_link;
            if (shareableLink) {
                linkArea.style.display = 'block';
                linkInput.value = shareableLink;
            }

            if (typeof notifyEvs === 'function') {
                notifyEvs('success', data.message || 'Cobranca criada com sucesso.');
            }
        } catch (err) {
            const message = err.message || 'Erro de comunicacao.';
            setChargeFeedback('error', message);
            badge.innerText = 'Revisar';
            if (typeof notifyEvs === 'function') {
                notifyEvs('error', message);
            }
            console.error(err);
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    function resetForm() {
        document.getElementById('form-container').style.opacity = '1';
        document.getElementById('form-container').style.pointerEvents = 'auto';
        document.getElementById('chargeForm').reset();
        
        document.getElementById('preview-state').style.display = 'block';
        document.getElementById('success-state').style.display = 'none';
        document.getElementById('success-pix-area').style.display = 'none';
        document.getElementById('success-link-area').style.display = 'none';
        document.getElementById('badge-status').innerText = 'Aguardando';
        document.getElementById('success-qr').removeAttribute('src');
        document.getElementById('success-qr').style.display = 'none';
        document.getElementById('success-copy-paste').innerText = '';
        document.getElementById('success-payment-link').value = '';
        setChargeFeedback(null, '');
        updatePreview();
        
        const firstMethodCard = document.querySelector('.method-card');
        if(firstMethodCard) {
            firstMethodCard.click();
        }
    }

    async function copyToClipboard(elementId) {
        const el = document.getElementById(elementId);
        const text = el.tagName === 'INPUT' ? el.value : el.innerText;

        try {
            await navigator.clipboard.writeText(text);
            if (typeof notifyEvs === 'function') {
                notifyEvs('success', 'Conteudo copiado com sucesso.');
            }
        } catch (err) {
            if (typeof notifyEvs === 'function') {
                notifyEvs('error', 'Nao foi possivel copiar o conteudo.');
            }
            console.error(err);
        }
    }
</script>

@endsection
