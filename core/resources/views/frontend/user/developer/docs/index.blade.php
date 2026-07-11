@extends('frontend.user.developer.index')
@section('title', __('Documentação da API'))

@section('user_developer_content')

{{-- Page Header --}}
<div class="v2-page-header" style="margin-bottom: 32px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px; color: var(--ds-text-main);">Documentação da API</h2>
            <p style="font-size: 0.9375rem; color: var(--ds-text-muted); margin: 0;">Referência completa de endpoints, autenticação e exemplos de código.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <span class="v2-badge" style="background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); font-size: 0.8125rem; padding: 4px 12px;">v1.0</span>
            <span class="v2-badge v2-badge-default" style="font-size: 0.8125rem; padding: 4px 12px;">REST + JSON</span>
        </div>
    </div>
</div>

{{-- Quick Nav --}}
<div class="v2-settings-card" style="padding: 16px 24px; margin-bottom: 32px; border-radius: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--ds-text-muted); margin-right: 4px;">Navegar:</span>
        <a href="#intro" style="font-size: 0.8125rem; color: var(--ds-text-muted); text-decoration: none; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--ds-border-light); transition: all 200ms;" onmouseover="this.style.color='var(--ds-text-main)'; this.style.borderColor='var(--ds-primary-light)'" onmouseout="this.style.color='var(--ds-text-muted)'; this.style.borderColor='var(--ds-border-light)'">Introdução</a>
        <a href="#auth" style="font-size: 0.8125rem; color: var(--ds-text-muted); text-decoration: none; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--ds-border-light); transition: all 200ms;" onmouseover="this.style.color='var(--ds-text-main)'; this.style.borderColor='var(--ds-primary-light)'" onmouseout="this.style.color='var(--ds-text-muted)'; this.style.borderColor='var(--ds-border-light)'">Autenticação</a>
        <a href="#charges" style="font-size: 0.8125rem; color: var(--ds-text-muted); text-decoration: none; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--ds-border-light); transition: all 200ms;" onmouseover="this.style.color='var(--ds-text-main)'; this.style.borderColor='var(--ds-primary-light)'" onmouseout="this.style.color='var(--ds-text-muted)'; this.style.borderColor='var(--ds-border-light)'">Cobranças</a>
        <a href="#subscriptions" style="font-size: 0.8125rem; color: var(--ds-text-muted); text-decoration: none; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--ds-border-light); transition: all 200ms;" onmouseover="this.style.color='var(--ds-text-main)'; this.style.borderColor='var(--ds-primary-light)'" onmouseout="this.style.color='var(--ds-text-muted)'; this.style.borderColor='var(--ds-border-light)'">Assinaturas</a>
        <a href="#webhooks" style="font-size: 0.8125rem; color: var(--ds-text-muted); text-decoration: none; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--ds-border-light); transition: all 200ms;" onmouseover="this.style.color='var(--ds-text-main)'; this.style.borderColor='var(--ds-primary-light)'" onmouseout="this.style.color='var(--ds-text-muted)'; this.style.borderColor='var(--ds-border-light)'">Webhooks</a>
        <a href="#errors" style="font-size: 0.8125rem; color: var(--ds-text-muted); text-decoration: none; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--ds-border-light); transition: all 200ms;" onmouseover="this.style.color='var(--ds-text-main)'; this.style.borderColor='var(--ds-primary-light)'" onmouseout="this.style.color='var(--ds-text-muted)'; this.style.borderColor='var(--ds-border-light)'">Erros</a>
    </div>
</div>

{{-- ─── SECTION: Introdução ─────────────────────────────────────────────── --}}
<div id="intro" class="v2-settings-card" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 36px; height: 36px; background: rgba(124,58,237,0.12); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #7C3AED; font-size: 1rem; flex-shrink: 0;">
            <i class="fas fa-book-open"></i>
        </div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main);">Introdução</h3>
    </div>
    <p style="color: var(--ds-text-muted); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 20px;">
        A API OriginPay permite gerenciar cobranças, clientes e webhooks de forma programática. Nossa API segue as convenções REST — endpoints com URLs previsíveis, parâmetros passados como form-encoded ou JSON, e respostas sempre em formato <strong style="color: var(--ds-text-main);">JSON</strong>.
    </p>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--ds-border-light); border-radius: 10px; padding: 16px;">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); margin-bottom: 8px; letter-spacing: 0.04em;">Base URL</div>
            <code style="font-size: 0.875rem; color: #a5b4fc; font-family: monospace;">https://api.originpay.com/v1</code>
        </div>
        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--ds-border-light); border-radius: 10px; padding: 16px;">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); margin-bottom: 8px; letter-spacing: 0.04em;">Formato</div>
            <code style="font-size: 0.875rem; color: #a5b4fc; font-family: monospace;">application/json</code>
        </div>
        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--ds-border-light); border-radius: 10px; padding: 16px;">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); margin-bottom: 8px; letter-spacing: 0.04em;">Versão</div>
            <code style="font-size: 0.875rem; color: #a5b4fc; font-family: monospace;">v1 (estável)</code>
        </div>
    </div>
</div>

{{-- ─── SECTION: Autenticação ───────────────────────────────────────────── --}}
<div id="auth" class="v2-settings-card" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 36px; height: 36px; background: rgba(245,158,11,0.12); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 1rem; flex-shrink: 0;">
            <i class="fas fa-lock"></i>
        </div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main);">Autenticação</h3>
    </div>

    <p style="color: var(--ds-text-muted); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 20px;">
        Autentique suas requisições enviando a sua <strong style="color: var(--ds-text-main);">Secret Key</strong> no header <code style="color: var(--ds-primary-light); background: rgba(124,58,237,0.12); padding: 2px 8px; border-radius: 4px; font-size: 0.875rem;">Authorization</code> usando o schema <code style="color: var(--ds-primary-light); background: rgba(124,58,237,0.12); padding: 2px 8px; border-radius: 4px; font-size: 0.875rem;">Bearer</code>.
    </p>

    <div style="background: rgba(245,158,11,0.05); border: 1px solid rgba(245,158,11,0.2); color: #fbbf24; border-radius: 10px; padding: 14px 16px; display: flex; align-items: flex-start; gap: 10px; font-size: 0.875rem; line-height: 1.6; margin-bottom: 24px;">
        <i class="fas fa-exclamation-triangle" style="margin-top: 2px; flex-shrink: 0;"></i>
        <div>Nunca exponha sua <strong>Secret Key</strong> no front-end da sua aplicação. Mantenha-a segura exclusivamente no backend.</div>
    </div>

    {{-- Code Block --}}
    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: 0.04em; margin-bottom: 8px;">Exemplo de header</div>
    <div style="position: relative; background: #09090b; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden;">
        <button class="v2-btn-tertiary" style="position: absolute; top: 10px; right: 10px; padding: 4px; height: 30px; width: 30px; display: flex; align-items: center; justify-content: center;" onclick="copyCode(this)" title="Copiar">
            <i class="fas fa-copy" style="font-size: 0.8rem;"></i>
        </button>
        <pre style="margin: 0; padding: 20px 24px; color: #a5b4fc; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.875rem; line-height: 1.7; overflow-x: auto;">Authorization: Bearer sk_live_<span style="color: #6b7280;">sua_chave_privada</span>
Content-Type: application/json</pre>
    </div>
</div>

{{-- ─── SECTION: Criar Cobrança ─────────────────────────────────────────── --}}
<div id="charges" class="v2-settings-card" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 36px; height: 36px; background: rgba(16,185,129,0.12); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1rem; flex-shrink: 0;">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main);">Criar Cobrança</h3>
    </div>
    <p style="color: var(--ds-text-muted); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 20px;">
        Cria uma nova cobrança PIX. O objeto de resposta conterá o código copia-e-cola e a URL do QR Code.
    </p>

    {{-- Endpoint badge --}}
    <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--ds-border-light); padding: 12px 16px; border-radius: 10px; font-family: monospace; font-size: 0.9375rem; margin-bottom: 24px;">
        <span class="v2-badge" style="background: rgba(56,189,248,0.12); color: #38bdf8; border: 1px solid rgba(56,189,248,0.25); font-size: 0.8125rem; letter-spacing: 0.03em;">POST</span>
        <span style="color: var(--ds-text-main);">/v1/charges</span>
    </div>

    {{-- Parameters Table --}}
    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: 0.04em; margin-bottom: 12px;">Parâmetros</div>
    <div style="border: 1px solid var(--ds-border-light); border-radius: 10px; overflow: hidden; margin-bottom: 24px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--ds-border-light);">
                    <th style="padding: 10px 16px; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: 0.04em; white-space: nowrap;">Parâmetro</th>
                    <th style="padding: 10px 16px; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: 0.04em; white-space: nowrap;">Tipo</th>
                    <th style="padding: 10px 16px; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: 0.04em;">Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 14px 16px; vertical-align: top;"><code style="color: #e2e8f0; font-size: 0.875rem;">amount</code></td>
                    <td style="padding: 14px 16px; vertical-align: top;"><span class="v2-badge v2-badge-default">Integer</span></td>
                    <td style="padding: 14px 16px; color: var(--ds-text-muted); line-height: 1.5;">Valor em centavos. Ex: <code style="color: var(--ds-primary-light);">15000</code> = R$ 150,00. <span class="v2-badge v2-badge-error" style="margin-left: 4px;">Obrigatório</span></td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 14px 16px; vertical-align: top;"><code style="color: #e2e8f0; font-size: 0.875rem;">payment_method</code></td>
                    <td style="padding: 14px 16px; vertical-align: top;"><span class="v2-badge v2-badge-default">String</span></td>
                    <td style="padding: 14px 16px; color: var(--ds-text-muted); line-height: 1.5;">Método de pagamento. Aceita: <code style="color: var(--ds-primary-light);">pix</code>. <span class="v2-badge v2-badge-error" style="margin-left: 4px;">Obrigatório</span></td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 14px 16px; vertical-align: top;"><code style="color: #e2e8f0; font-size: 0.875rem;">customer.name</code></td>
                    <td style="padding: 14px 16px; vertical-align: top;"><span class="v2-badge v2-badge-default">String</span></td>
                    <td style="padding: 14px 16px; color: var(--ds-text-muted); line-height: 1.5;">Nome completo do pagador. <span class="v2-badge v2-badge-error" style="margin-left: 4px;">Obrigatório</span></td>
                </tr>
                <tr>
                    <td style="padding: 14px 16px; vertical-align: top;"><code style="color: #e2e8f0; font-size: 0.875rem;">customer.document</code></td>
                    <td style="padding: 14px 16px; vertical-align: top;"><span class="v2-badge v2-badge-default">String</span></td>
                    <td style="padding: 14px 16px; color: var(--ds-text-muted); line-height: 1.5;">CPF ou CNPJ (somente números). <span class="v2-badge v2-badge-error" style="margin-left: 4px;">Obrigatório</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Code Tabs --}}
    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: 0.04em; margin-bottom: 12px;">Exemplos de código</div>
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist" style="gap: 6px;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pill-curl" data-bs-toggle="pill" data-bs-target="#pills-curl" type="button" style="padding: 5px 14px; font-size: 0.8125rem; background: transparent; color: var(--ds-text-muted); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; transition: 200ms;">cURL</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pill-php" data-bs-toggle="pill" data-bs-target="#pills-php" type="button" style="padding: 5px 14px; font-size: 0.8125rem; background: transparent; color: var(--ds-text-muted); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; transition: 200ms;">PHP</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pill-node" data-bs-toggle="pill" data-bs-target="#pills-node" type="button" style="padding: 5px 14px; font-size: 0.8125rem; background: transparent; color: var(--ds-text-muted); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; transition: 200ms;">Node.js</button>
        </li>
    </ul>
    <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-curl" role="tabpanel">
            <div style="position: relative; background: #09090b; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden;">
                <button class="v2-btn-tertiary" style="position: absolute; top: 10px; right: 10px; padding: 4px; height: 30px; width: 30px; display: flex; align-items: center; justify-content: center;" onclick="copyCode(this)" title="Copiar"><i class="fas fa-copy" style="font-size: 0.8rem;"></i></button>
                <pre style="margin: 0; padding: 20px 24px; color: #94a3b8; font-family: monospace; font-size: 0.875rem; line-height: 1.7; white-space: pre-wrap; overflow-x: auto;"><span style="color: #6b7280;"># Criar uma cobrança PIX</span>
curl -X POST https://api.originpay.com/v1/charges \
  -H <span style="color: #86efac;">"Authorization: Bearer sk_test_123"</span> \
  -H <span style="color: #86efac;">"Content-Type: application/json"</span> \
  -d <span style="color: #86efac;">'{
    "amount": 15000,
    "payment_method": "pix",
    "customer": {
      "name": "João Silva",
      "document": "12345678909"
    }
  }'</span></pre>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-php" role="tabpanel">
            <div style="position: relative; background: #09090b; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden;">
                <button class="v2-btn-tertiary" style="position: absolute; top: 10px; right: 10px; padding: 4px; height: 30px; width: 30px; display: flex; align-items: center; justify-content: center;" onclick="copyCode(this)" title="Copiar"><i class="fas fa-copy" style="font-size: 0.8rem;"></i></button>
                <pre style="margin: 0; padding: 20px 24px; color: #94a3b8; font-family: monospace; font-size: 0.875rem; line-height: 1.7; white-space: pre-wrap; overflow-x: auto;"><span style="color: #818cf8;">$client</span> = <span style="color: #7dd3fc;">new</span> \GuzzleHttp\Client();

<span style="color: #818cf8;">$response</span> = <span style="color: #818cf8;">$client</span>-><span style="color: #34d399;">request</span>(<span style="color: #86efac;">'POST'</span>, <span style="color: #86efac;">'https://api.originpay.com/v1/charges'</span>, [
  <span style="color: #86efac;">'headers'</span> => [
    <span style="color: #86efac;">'Authorization'</span> => <span style="color: #86efac;">'Bearer sk_test_123'</span>,
    <span style="color: #86efac;">'Content-Type'</span>  => <span style="color: #86efac;">'application/json'</span>,
  ],
  <span style="color: #86efac;">'json'</span> => [
    <span style="color: #86efac;">'amount'</span>         => <span style="color: #f9a8d4;">15000</span>,
    <span style="color: #86efac;">'payment_method'</span> => <span style="color: #86efac;">'pix'</span>,
    <span style="color: #86efac;">'customer'</span>       => [<span style="color: #86efac;">'name'</span> => <span style="color: #86efac;">'João Silva'</span>, <span style="color: #86efac;">'document'</span> => <span style="color: #86efac;">'12345678909'</span>],
  ],
]);

<span style="color: #818cf8;">$data</span> = <span style="color: #7dd3fc;">json_decode</span>(<span style="color: #818cf8;">$response</span>-><span style="color: #34d399;">getBody</span>(), <span style="color: #f9a8d4;">true</span>);</pre>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-node" role="tabpanel">
            <div style="position: relative; background: #09090b; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden;">
                <button class="v2-btn-tertiary" style="position: absolute; top: 10px; right: 10px; padding: 4px; height: 30px; width: 30px; display: flex; align-items: center; justify-content: center;" onclick="copyCode(this)" title="Copiar"><i class="fas fa-copy" style="font-size: 0.8rem;"></i></button>
                <pre style="margin: 0; padding: 20px 24px; color: #94a3b8; font-family: monospace; font-size: 0.875rem; line-height: 1.7; white-space: pre-wrap; overflow-x: auto;"><span style="color: #7dd3fc;">const</span> response = <span style="color: #7dd3fc;">await</span> fetch(<span style="color: #86efac;">'https://api.originpay.com/v1/charges'</span>, {
  method: <span style="color: #86efac;">'POST'</span>,
  headers: {
    <span style="color: #86efac;">'Authorization'</span>: <span style="color: #86efac;">'Bearer sk_test_123'</span>,
    <span style="color: #86efac;">'Content-Type'</span>:  <span style="color: #86efac;">'application/json'</span>,
  },
  body: JSON.<span style="color: #34d399;">stringify</span>({
    amount:         <span style="color: #f9a8d4;">15000</span>,
    payment_method: <span style="color: #86efac;">'pix'</span>,
    customer:       { name: <span style="color: #86efac;">'João Silva'</span>, document: <span style="color: #86efac;">'12345678909'</span> },
  }),
});

<span style="color: #7dd3fc;">const</span> data = <span style="color: #7dd3fc;">await</span> response.<span style="color: #34d399;">json</span>();</pre>
            </div>
        </div>
    </div>

    {{-- Response Example --}}
    <div style="margin-top: 20px;">
        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: 0.04em; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
            Response
            <span class="v2-badge v2-badge-success" style="font-size: 0.75rem;">201 Created</span>
        </div>
        <div style="position: relative; background: #09090b; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden;">
            <pre style="margin: 0; padding: 20px 24px; color: #a78bfa; font-family: monospace; font-size: 0.875rem; line-height: 1.7; overflow-x: auto;">{
  <span style="color: #6b7280;">"id"</span>:             <span style="color: #86efac;">"ch_1a2b3c4d5e"</span>,
  <span style="color: #6b7280;">"object"</span>:         <span style="color: #86efac;">"charge"</span>,
  <span style="color: #6b7280;">"status"</span>:         <span style="color: #fbbf24;">"pending"</span>,
  <span style="color: #6b7280;">"amount"</span>:         <span style="color: #f9a8d4;">15000</span>,
  <span style="color: #6b7280;">"payment_method"</span>: <span style="color: #86efac;">"pix"</span>,
  <span style="color: #6b7280;">"pix_qr_code"</span>:    <span style="color: #86efac;">"00020101021126580014br.gov.bcb.pix..."</span>,
  <span style="color: #6b7280;">"pix_qr_code_url"</span>:<span style="color: #86efac;">"https://api.originpay.com/qr/..."</span>,
  <span style="color: #6b7280;">"created_at"</span>:     <span style="color: #86efac;">"2024-03-21T10:30:00Z"</span>
}</pre>
        </div>
    </div>
</div>

{{-- Customer Subscriptions --}}
<div id="subscriptions" class="v2-settings-card" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 36px; height: 36px; background: rgba(124,58,237,0.12); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #a78bfa; font-size: 1rem; flex-shrink: 0;">
            <i class="fas fa-sync-alt"></i>
        </div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main);">Assinaturas recorrentes</h3>
    </div>
    <p style="color: var(--ds-text-muted); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 16px;">
        O MVP de Customer Subscriptions permite criar, listar, consultar e cancelar assinaturas recorrentes via API v1, com invoices, primeira cobrança, renovação automática e webhooks de ciclo de vida.
    </p>
    <div style="display: flex; flex-direction: column; gap: 10px; border: 1px solid var(--ds-border-light); border-radius: 10px; padding: 16px;">
        <code style="color: #a5b4fc; font-size: 0.875rem;">POST /api/v1/customer-subscriptions</code>
        <code style="color: #a5b4fc; font-size: 0.875rem;">GET /api/v1/customer-subscriptions</code>
        <code style="color: #a5b4fc; font-size: 0.875rem;">GET /api/v1/customer-subscriptions/{id}</code>
        <code style="color: #a5b4fc; font-size: 0.875rem;">POST /api/v1/customer-subscriptions/{id}/cancel</code>
    </div>
    <p style="color: var(--ds-text-muted); font-size: 0.875rem; line-height: 1.6; margin: 16px 0 0;">
        Referências técnicas: <code>docs/customer-subscriptions-mvp.md</code> e <code>docs/customer-subscriptions-final-report.md</code>.
    </p>
</div>

{{-- ─── SECTION: Webhooks ───────────────────────────────────────────────── --}}
<div id="webhooks" class="v2-settings-card" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 36px; height: 36px; background: rgba(56,189,248,0.12); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1rem; flex-shrink: 0;">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main);">Webhooks</h3>
    </div>
    <p style="color: var(--ds-text-muted); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 20px;">
        Os webhooks notificam sua aplicação via <code style="color: var(--ds-primary-light); background: rgba(124,58,237,0.12); padding: 2px 8px; border-radius: 4px; font-size: 0.875rem;">POST</code> sempre que um evento relevante ocorrer na sua conta.
    </p>
    <div style="display: flex; flex-direction: column; gap: 1px; border: 1px solid var(--ds-border-light); border-radius: 10px; overflow: hidden;">
        @php
        $events = [
            ['name' => 'payment.created',  'color' => '#a5b4fc', 'bg' => 'rgba(165,180,252,0.1)', 'desc' => 'Uma nova cobrança foi gerada.'],
            ['name' => 'payment.paid',     'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)',  'desc' => 'Uma cobrança foi paga com sucesso.'],
            ['name' => 'payment.expired',  'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)',   'desc' => 'Uma cobrança expirou.'],
            ['name' => 'payment.refunded', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)', 'desc' => 'Um reembolso foi processado.'],
        ];
        @endphp
        @foreach($events as $i => $event)
        <div style="display: flex; align-items: center; gap: 16px; padding: 14px 20px; background: {{ $i % 2 === 0 ? 'transparent' : 'rgba(255,255,255,0.01)' }}; border-bottom: {{ $i < count($events) - 1 ? '1px solid rgba(255,255,255,0.04)' : 'none' }};">
            <code style="background: {{ $event['bg'] }}; color: {{ $event['color'] }}; padding: 3px 10px; border-radius: 6px; font-size: 0.8125rem; white-space: nowrap; font-weight: 600;">{{ $event['name'] }}</code>
            <span style="color: var(--ds-text-muted); font-size: 0.875rem;">{{ $event['desc'] }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- ─── SECTION: Erros ──────────────────────────────────────────────────── --}}
<div id="errors" class="v2-settings-card" style="padding: 32px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 36px; height: 36px; background: rgba(239,68,68,0.12); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1rem; flex-shrink: 0;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main);">Códigos de Erro</h3>
    </div>
    <p style="color: var(--ds-text-muted); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 20px;">
        A API usa os códigos de status HTTP convencionais para indicar sucesso ou falha de uma requisição.
    </p>
    <div style="display: flex; flex-direction: column; gap: 1px; border: 1px solid var(--ds-border-light); border-radius: 10px; overflow: hidden;">
        @php
        $codes = [
            ['code' => '200', 'label' => 'OK',                   'class' => 'v2-badge-success', 'desc' => 'A requisição foi processada com sucesso.'],
            ['code' => '201', 'label' => 'Created',              'class' => 'v2-badge-success', 'desc' => 'Um novo recurso foi criado com sucesso.'],
            ['code' => '400', 'label' => 'Bad Request',          'class' => 'v2-badge-error',   'desc' => 'Parâmetros inválidos ou ausentes na requisição.'],
            ['code' => '401', 'label' => 'Unauthorized',         'class' => 'v2-badge-error',   'desc' => 'API Key ausente ou inválida.'],
            ['code' => '422', 'label' => 'Unprocessable Entity', 'class' => 'v2-badge-error',   'desc' => 'Erros de validação nos dados enviados.'],
            ['code' => '500', 'label' => 'Server Error',         'class' => 'v2-badge-error',   'desc' => 'Erro interno — tente novamente em instantes.'],
        ];
        @endphp
        @foreach($codes as $i => $c)
        <div style="display: flex; align-items: center; gap: 16px; padding: 14px 20px; background: {{ $i % 2 === 0 ? 'transparent' : 'rgba(255,255,255,0.01)' }}; border-bottom: {{ $i < count($codes) - 1 ? '1px solid rgba(255,255,255,0.04)' : 'none' }};">
            <span class="v2-badge {{ $c['class'] }}" style="font-family: monospace; font-size: 0.8125rem; min-width: 40px; text-align: center;">{{ $c['code'] }}</span>
            <code style="color: var(--ds-text-main); font-size: 0.875rem; min-width: 150px;">{{ $c['label'] }}</code>
            <span style="color: var(--ds-text-muted); font-size: 0.875rem;">{{ $c['desc'] }}</span>
        </div>
        @endforeach
    </div>
</div>

<style>
.nav-pills .nav-link.active, .nav-pills .show > .nav-link {
    background-color: rgba(124,58,237,0.15) !important;
    color: #a78bfa !important;
    border-color: rgba(124,58,237,0.3) !important;
}
</style>

@push('scripts')
<script>
function copyCode(btn) {
    const pre = btn.nextElementSibling;
    const text = pre ? pre.innerText : '';
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check" style="color:#10b981;font-size:0.8rem;"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    });
}
</script>
@endpush
@endsection
