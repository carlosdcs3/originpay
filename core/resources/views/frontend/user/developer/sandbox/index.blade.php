@extends('frontend.user.developer.index')
@section('title', __('Sandbox / Ferramentas'))

@section('user_developer_content')

<div class="v2-page-header" style="margin-bottom: 24px;">
    <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; color: var(--ds-text-main);">Ferramentas de Sandbox</h2>
    <p class="v2-page-subtitle" style="font-size: 0.875rem; color: var(--ds-text-muted); margin: 0;">Simule cobranças, dispare eventos PIX e teste o envio de webhooks no ambiente seguro.</p>
</div>

<div class="row g-4 mb-4 d-flex align-items-stretch">
    <!-- Simulação de Cobrança -->
    <div class="col-lg-6">
        <div class="v2-settings-card h-100" style="padding: 24px; display: flex; flex-direction: column;">
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: rgba(124,58,237,.12); border-radius: 12px; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 1.125rem;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--ds-text-main);">Simulação de Cobrança</h3>
            </div>
            
            <form id="sandboxChargeForm" onsubmit="event.preventDefault(); submitSandboxCharge();" style="flex: 1; display: flex; flex-direction: column;">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">Valor (R$)</label>
                        <input type="text" class="v2-input" id="chargeAmount" value="150,00" required style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
                    </div>
                    <div class="col-md-6">
                        <label class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">Nome do Cliente</label>
                        <input type="text" class="v2-input" id="chargeName" value="João Sandbox" required style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
                    </div>
                    <div class="col-md-6">
                        <label class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">E-mail do Cliente</label>
                        <input type="email" class="v2-input" id="chargeEmail" value="joao@sandbox.com" required style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
                    </div>
                    <div class="col-md-6">
                        <label class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">CPF/CNPJ</label>
                        <input type="text" class="v2-input" id="chargeDocument" value="12345678909" required style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
                    </div>
                    <div class="col-md-12">
                        <label class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">Descrição da Cobrança</label>
                        <input type="text" class="v2-input" id="chargeDescription" value="Pedido #1001 (Teste Sandbox)" style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
                    </div>
                    <div class="col-md-12">
                        <label class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">Metadata (JSON)</label>
                        <textarea class="v2-input" id="chargeMetadata" rows="2" style="padding: 12px 16px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white; font-family: monospace; font-size: 0.8125rem;">{"order_id": "1001", "source": "sandbox"}</textarea>
                    </div>
                </div>
                <div style="margin-top: auto; padding-top: 24px;">
                    <button type="submit" class="v2-btn-primary w-100" style="height: 48px;">
                        <i class="fas fa-play" style="margin-right: 8px;"></i> Criar Cobrança de Teste
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Simulação de Eventos & Webhooks -->
    <div class="col-lg-6">
        
        <!-- Webhooks -->
        <div class="v2-settings-card mb-4" style="padding: 24px;">
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: rgba(56,189,248,.12); border-radius: 12px; color: #38BDF8; display: flex; align-items: center; justify-content: center; font-size: 1.125rem;">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--ds-text-main);">Disparar Webhook</h3>
            </div>
            
            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                <select class="v2-input" id="webhookEventSelect" style="flex: 1; padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
                    <option value="payment.created" style="background: var(--ds-bg-card);">payment.created</option>
                    <option value="payment.pending" style="background: var(--ds-bg-card);">payment.pending</option>
                    <option value="payment.paid" style="background: var(--ds-bg-card);">payment.paid</option>
                    <option value="payment.expired" style="background: var(--ds-bg-card);">payment.expired</option>
                    <option value="payment.refunded" style="background: var(--ds-bg-card);">payment.refunded</option>
                </select>
                <button class="v2-btn-secondary" type="button" onclick="fireWebhookTest()">
                    <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Disparar
                </button>
            </div>
            <div style="font-size: 0.8125rem; color: var(--ds-text-muted);">O webhook será disparado para a URL configurada no seu painel.</div>
        </div>

        <!-- Eventos PIX -->
        <div class="v2-settings-card" style="padding: 24px;">
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: rgba(16,185,129,.12); border-radius: 12px; color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.125rem;">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--ds-text-main);">Simular Ciclo PIX</h3>
            </div>
            
            <p style="color: var(--ds-text-muted); font-size: 0.875rem; margin-bottom: 16px;">Insira o ID de uma cobrança gerada em modo teste (ex: `ch_12345`) e force a alteração do status para simular pagamentos reais.</p>
            
            <div style="margin-bottom: 16px;">
                <input type="text" class="v2-input" id="pixChargeId" placeholder="ID da Cobrança (ex: ch_...)" style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: auto;">
                <button class="v2-btn-tertiary" style="background: rgba(16,185,129,0.1); color: #10B981; border: 1px solid rgba(16,185,129,0.2); transition: 200ms;" onmouseover="this.style.background='rgba(16,185,129,0.2)'" onmouseout="this.style.background='rgba(16,185,129,0.1)'" onclick="simulatePix('paid')">
                    <i class="fas fa-check-circle" style="margin-right: 6px;"></i> Pagar
                </button>
                <button class="v2-btn-tertiary" style="background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); transition: 200ms;" onmouseover="this.style.background='rgba(245,158,11,0.2)'" onmouseout="this.style.background='rgba(245,158,11,0.1)'" onclick="simulatePix('expired')">
                    <i class="fas fa-clock" style="margin-right: 6px;"></i> Expirar
                </button>
                <button class="v2-btn-tertiary" style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); transition: 200ms;" onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'" onclick="simulatePix('canceled')">
                    <i class="fas fa-times-circle" style="margin-right: 6px;"></i> Cancelar
                </button>
                <button class="v2-btn-tertiary" onclick="simulatePix('refunded')" style="transition: 200ms;">
                    <i class="fas fa-undo" style="margin-right: 6px;"></i> Reembolsar
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Console Output -->
<div class="v2-settings-card" style="padding: 0; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.5); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 24px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-terminal" style="color: var(--ds-text-muted);"></i>
            <span style="font-size: 0.875rem; font-weight: 700; color: var(--ds-text-main); font-family: monospace; letter-spacing: 0.5px;">Console Sandbox</span>
        </div>
        <button class="v2-btn-tertiary" style="padding: 6px 12px; height: 32px; font-size: 0.75rem; transition: 200ms;" onclick="clearConsole()" title="Limpar Console">
            <i class="fas fa-trash"></i>
        </button>
    </div>
    <div id="sandboxConsole" style="background: #09090b; color: #a1a1aa; padding: 24px; font-family: monospace; font-size: 0.875rem; height: 400px; overflow-y: auto; line-height: 1.7;">
        <div style="margin-bottom: 8px;"><span style="color: #52525b;">[Sistema]</span> Ambiente Sandbox carregado e pronto para testes.</div>
    </div>
</div>

@push('scripts')
<script>
    function logToConsole(message, type = 'info', json = null) {
        const consoleEl = document.getElementById('sandboxConsole');
        const time = new Date().toLocaleTimeString();
        
        let color = '#a6accd'; // info
        if(type === 'success') color = '#10B981';
        if(type === 'error') color = '#EF4444';
        if(type === 'warning') color = '#F59E0B';
        if(type === 'request') color = '#7C3AED';

        let html = `<div style="margin-bottom: 8px;"><span style="color: #6c757d;">[${time}]</span> <span style="color: ${color}; font-weight: 500;">${message}</span></div>`;
        
        if (json) {
            let formattedJson = '';
            try {
                formattedJson = JSON.stringify(typeof json === 'string' ? JSON.parse(json) : json, null, 2);
            } catch(e) {
                formattedJson = json;
            }
            html += `<pre style="color: #94a3b8; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; margin-left: 72px; margin-top: 4px; font-size: 0.75rem; margin-bottom: 16px;">${formattedJson}</pre>`;
        }
        
        consoleEl.insertAdjacentHTML('beforeend', html);
        consoleEl.scrollTop = consoleEl.scrollHeight;
    }

    function clearConsole() {
        document.getElementById('sandboxConsole').innerHTML = '';
        logToConsole('[Sistema] Console limpo.', 'info');
    }

    // Mock API requests for UI demo purposes since actual routes might not exist yet.
    // In a real scenario, this would hit the actual API endpoints or Sandbox Controller methods.
    function submitSandboxCharge() {
        const payload = {
            amount: document.getElementById('chargeAmount').value,
            customer: {
                name: document.getElementById('chargeName').value,
                email: document.getElementById('chargeEmail').value,
                document: document.getElementById('chargeDocument').value
            },
            description: document.getElementById('chargeDescription').value,
            metadata: document.getElementById('chargeMetadata').value
        };

        logToConsole('POST /api/v1/charges', 'request', payload);
        
        // Simulating API latency
        setTimeout(() => {
            logToConsole('201 Created - Cobrança gerada com sucesso', 'success', {
                id: "ch_" + Math.random().toString(36).substr(2, 9),
                status: "pending",
                payment_method: "pix",
                pix_qr_code: "00020101021126580014br.gov.bcb.pix...",
                amount: payload.amount
            });
            // Also simulate a webhook
            setTimeout(() => {
                logToConsole('Webhook enviado: payment.created', 'warning', { event: 'payment.created', target: 'sua-url.com/webhook' });
            }, 800);
        }, 600);
    }

    function fireWebhookTest() {
        const event = document.getElementById('webhookEventSelect').value;
        logToConsole(`Disparando webhook manual para o evento: ${event}...`, 'request');
        
        setTimeout(() => {
            logToConsole(`200 OK - Webhook disparado.`, 'success', {
                delivery_id: "wd_" + Math.random().toString(36).substr(2, 9),
                event: event,
                response_status: 200
            });
        }, 500);
    }

    function simulatePix(action) {
        const id = document.getElementById('pixChargeId').value;
        if (!id) {
            logToConsole('Erro: Informe o ID da Cobrança para simular o evento PIX.', 'error');
            return;
        }

        logToConsole(`Simulando transição de PIX para [${action}] na cobrança ${id}...`, 'request');
        
        setTimeout(() => {
            logToConsole(`Sucesso! Status alterado para ${action}.`, 'success', {
                id: id,
                status: action,
                updated_at: new Date().toISOString()
            });
            setTimeout(() => {
                logToConsole(`Webhook enviado: payment.${action}`, 'warning');
            }, 500);
        }, 700);
    }
</script>
@endpush
@endsection
