@extends('frontend.layouts.api_reference')

@section('title', 'API Explorer (Request Builder)')

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.v1.api_reference.index') }}">API Reference</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>API Explorer</span>
    </div>

    <h1>API Explorer</h1>
    <p class="lead" style="margin-bottom: 32px;">
        Use o Request Builder para montar e visualizar requisições HTTP antes de implementá-las no seu código. <br>
        <strong>Atenção:</strong> Esta ferramenta apenas monta o payload e a assinatura esperada; ela não executa transações reais.
    </p>

    <div style="background: var(--doc-surface); border: 1px solid var(--doc-border); border-radius: 12px; padding: 24px; margin-bottom: 48px;">
        <div style="margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; color: #fff; font-weight: 500;">Selecione o Endpoint</label>
            <select id="explorerEndpoint" style="width: 100%; background: #000; border: 1px solid var(--doc-border); color: #fff; padding: 12px; border-radius: 8px; font-family: var(--doc-font); outline: none;">
                <option value="create_payment">POST /v1/payments (Create Payment)</option>
                <option value="create_payout">POST /v1/payouts (Create Payout)</option>
                <option value="get_balance">GET /v1/balance (Retrieve Balance)</option>
            </select>
        </div>

        <div id="explorerParams">
            <div class="param-group" data-for="create_payment">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--doc-muted); font-size: 0.9rem;">Amount (centavos)</label>
                    <input type="number" id="ep_amount" value="10000" style="width: 100%; background: #000; border: 1px solid var(--doc-border); color: #fff; padding: 10px; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--doc-muted); font-size: 0.9rem;">Method</label>
                    <select id="ep_method" style="width: 100%; background: #000; border: 1px solid var(--doc-border); color: #fff; padding: 10px; border-radius: 6px;">
                        <option value="pix">pix</option>
                        <option value="credit_card">credit_card</option>
                    </select>
                </div>
            </div>

            <div class="param-group" data-for="create_payout" style="display: none;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--doc-muted); font-size: 0.9rem;">Amount (centavos)</label>
                    <input type="number" id="po_amount" value="5000" style="width: 100%; background: #000; border: 1px solid var(--doc-border); color: #fff; padding: 10px; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--doc-muted); font-size: 0.9rem;">PIX Key</label>
                    <input type="text" id="po_pix_key" value="00011122233" style="width: 100%; background: #000; border: 1px solid var(--doc-border); color: #fff; padding: 10px; border-radius: 6px;">
                </div>
            </div>
            
            <div class="param-group" data-for="get_balance" style="display: none;">
                <p style="color: var(--doc-muted); font-size: 0.9rem; margin: 0;">Nenhum parâmetro no Body necessário.</p>
            </div>
        </div>

        <button onclick="updateExplorerCode()" style="background: var(--doc-primary); color: #000; font-weight: 600; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; margin-top: 24px; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="refresh-cw" style="width: 16px;"></i>
            Gerar Request
        </button>
    </div>
@endsection

@section('code_panel')
    <div style="margin-bottom: 32px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <h3 style="color: #fff; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Built Request</h3>
            <button onclick="copyExplorerReq()" style="background: rgba(255,255,255,0.1); border: none; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">Copiar requisição</button>
        </div>
        <div class="doc-code-block" style="margin: 0; background: #000; border-color: rgba(255,255,255,0.1);">
            <div class="doc-code-content" style="padding: 16px;">
<pre id="explorerCodeOutput" style="color: #a1a1aa; font-size: 0.75rem; line-height: 1.5;">curl -X POST https://api.originpay.com/v1/payments \
  -H "Authorization: Bearer sk_test_..." \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 10000,
    "method": "pix"
  }'</pre>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const endpointSelect = document.getElementById('explorerEndpoint');
    const paramGroups = document.querySelectorAll('.param-group');

    endpointSelect.addEventListener('change', (e) => {
        paramGroups.forEach(g => g.style.display = 'none');
        const target = document.querySelector(`.param-group[data-for="${e.target.value}"]`);
        if(target) target.style.display = 'block';
        updateExplorerCode();
    });

    function updateExplorerCode() {
        const type = endpointSelect.value;
        let code = '';

        if(type === 'create_payment') {
            const amt = document.getElementById('ep_amount').value;
            const met = document.getElementById('ep_method').value;
            code = `curl -X POST https://api.originpay.com/v1/payments \\
  -H "Authorization: Bearer sk_test_..." \\
  -H "Content-Type: application/json" \\
  -d '{
    "amount": ${amt},
    "method": "${met}"
  }'`;
        } else if(type === 'create_payout') {
            const amt = document.getElementById('po_amount').value;
            const pk = document.getElementById('po_pix_key').value;
            code = `curl -X POST https://api.originpay.com/v1/payouts \\
  -H "Authorization: Bearer sk_test_..." \\
  -H "Content-Type: application/json" \\
  -d '{
    "amount": ${amt},
    "pix_key": "${pk}"
  }'`;
        } else if(type === 'get_balance') {
            code = `curl -X GET https://api.originpay.com/v1/balance \\
  -H "Authorization: Bearer sk_test_..."`;
        }

        document.getElementById('explorerCodeOutput').innerText = code;
    }

    function copyExplorerReq() {
        const text = document.getElementById('explorerCodeOutput').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Requisição copiada!');
        });
    }

    // Init
    updateExplorerCode();
</script>
@endsection
