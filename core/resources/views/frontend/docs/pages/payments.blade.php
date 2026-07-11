@extends('frontend.layouts.docs')

@section('title', 'Criar Pagamento')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#endpoint">Endpoint</a></li>
        <li><a href="#request">Request Body</a></li>
        <li><a href="#fluxo">Try this request</a></li>
        <li><a href="#responses">Responses</a></li>
        <li><a href="#status">Transaction Status</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Pagamentos</a>
        <i class="fas fa-chevron-right"></i>
        <span>Criar Pagamento</span>
    </div>

    <h1>Criar Pagamento</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 7 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">Nossa API RESTful unifica a criação de cobranças independentemente do método de pagamento (PIX ou Cartão de Crédito). Basta enviar uma requisição POST e o nosso motor cuida do resto.</p>

    <h2 id="endpoint">Endpoint</h2>

    <div class="doc-endpoint-header">
        <div class="doc-endpoint-header-item" style="grid-column: 1 / -1; flex-direction: row; align-items: center; gap: 16px; border-bottom: 1px solid var(--doc-border); padding-bottom: 16px; margin-bottom: 8px;">
            <span class="doc-badge doc-badge-post">POST</span>
            <span class="value" style="font-family: monospace;">https://api.originpay.com/v1/payments</span>
        </div>
        <div class="doc-endpoint-header-item">
            <span class="label">API Version</span>
            <span class="value">v1</span>
        </div>
        <div class="doc-endpoint-header-item">
            <span class="label">Authentication</span>
            <span class="value">Bearer Token</span>
        </div>
        <div class="doc-endpoint-header-item">
            <span class="label">Rate Limit</span>
            <span class="value">100 req/sec</span>
        </div>
        <div class="doc-endpoint-header-item">
            <span class="label">Idempotent</span>
            <span class="value">Sim</span>
        </div>
        <div class="doc-endpoint-header-item">
            <span class="label">Environment</span>
            <span class="value">
                <span class="doc-badge doc-badge-sandbox">Sandbox</span>
                <span class="doc-badge doc-badge-live" style="margin-left: 4px;">Live</span>
            </span>
        </div>
    </div>

    <h2 id="request">Request Body</h2>
    <p>Os parâmetros a seguir iniciam uma intenção de pagamento. Dependendo do método (PIX ou Cartão), campos adicionais podem ser obrigatórios.</p>

    <div class="doc-table-wrap">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Campo</th>
                    <th>Tipo</th>
                    <th>Obrigatório</th>
                    <th>Descrição</th>
                    <th>Exemplo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="code">amount</td>
                    <td>Integer</td>
                    <td><span style="color: #10b981;">Sim</span></td>
                    <td>Valor em centavos.</td>
                    <td class="code">10000</td>
                </tr>
                <tr>
                    <td class="code">method</td>
                    <td>String</td>
                    <td><span style="color: #10b981;">Sim</span></td>
                    <td>Método: <code>pix</code> ou <code>credit_card</code>.</td>
                    <td class="code">"pix"</td>
                </tr>
                <tr>
                    <td class="code">customer</td>
                    <td>Object</td>
                    <td><span style="color: #10b981;">Sim</span></td>
                    <td>Dados do pagador (<code>name</code>, <code>email</code>, <code>document</code>).</td>
                    <td class="code">{"name": "João"}</td>
                </tr>
                <tr>
                    <td class="code">card_token</td>
                    <td>String</td>
                    <td>Condicional</td>
                    <td>Obrigatório se method for <code>credit_card</code>.</td>
                    <td class="code">"tok_123"</td>
                </tr>
                <tr>
                    <td class="code">postback_url</td>
                    <td>String</td>
                    <td>Não</td>
                    <td>URL específica para receber webhook apenas desta transação.</td>
                    <td class="code">"https://..."</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 id="fluxo">Try this request</h2>
    <p>Entenda visualmente o ciclo de vida completo de uma requisição bem-sucedida.</p>

    <div class="doc-try-request">
        <div class="doc-try-box">
            <i class="fas fa-paper-plane" style="color: var(--doc-primary); margin-bottom: 8px;"></i><br>
            Request: POST /v1/payments
        </div>
        <div class="doc-try-arrow"><i class="fas fa-long-arrow-alt-down"></i></div>
        <div class="doc-try-box">
            <i class="fas fa-server" style="color: #38bdf8; margin-bottom: 8px;"></i><br>
            Response: 200 OK (QR Code)
        </div>
        <div class="doc-try-arrow"><i class="fas fa-long-arrow-alt-down"></i></div>
        <div class="doc-try-box">
            <i class="fas fa-mobile-alt" style="color: #a855f7; margin-bottom: 8px;"></i><br>
            Cliente paga o PIX
        </div>
        <div class="doc-try-arrow"><i class="fas fa-long-arrow-alt-down"></i></div>
        <div class="doc-try-box">
            <i class="fas fa-bolt" style="color: #f59e0b; margin-bottom: 8px;"></i><br>
            Webhook: payment.paid
        </div>
    </div>

    <h2 id="responses">Responses</h2>
    <p>Abaixo estão todas as possíveis respostas que esta rota pode retornar.</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="res-200">200 OK</div>
                <div class="doc-code-tab" data-target="res-400">400 Bad Request</div>
                <div class="doc-code-tab" data-target="res-401">401 Unauthorized</div>
                <div class="doc-code-tab" data-target="res-422">422 Unprocessable</div>
                <div class="doc-code-tab" data-target="res-429">429 Rate Limit</div>
                <div class="doc-code-tab" data-target="res-500">500 Server Error</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="res-200">
<pre>
{
  "status": "success",
  "request_id": "req_88jjs8k",
  "data": {
    "id": "pay_59d8s21k",
    "amount": 10000,
    "status": "pending",
    "method": "pix",
    "qr_code": {
      "payload": "00020101021226...",
      "image_base64": "iVBORw0KGgo..."
    },
    "created_at": "2026-06-25T14:30:00Z"
  }
}
</pre>
        </div>
        <div class="doc-code-content" id="res-400" style="display:none;">
<pre>
{
  "status": "error",
  "error": {
    "code": "DGK_002",
    "message": "Parâmetros inválidos.",
    "param": "amount"
  }
}
</pre>
        </div>
        <div class="doc-code-content" id="res-401" style="display:none;">
<pre>
{
  "status": "error",
  "error": {
    "code": "DGK_001",
    "message": "Bearer token ausente ou inválido.",
    "param": "authorization"
  }
}
</pre>
        </div>
        <div class="doc-code-content" id="res-422" style="display:none;">
<pre>
{
  "status": "error",
  "error": {
    "code": "DGK_005",
    "message": "O cartão de crédito foi recusado pelo banco emissor.",
    "param": "card_token"
  }
}
</pre>
        </div>
        <div class="doc-code-content" id="res-429" style="display:none;">
<pre>
{
  "status": "error",
  "error": {
    "code": "DGK_429",
    "message": "Muitas requisições. Tente novamente em 2 segundos.",
    "param": null
  }
}
</pre>
        </div>
        <div class="doc-code-content" id="res-500" style="display:none;">
<pre>
{
  "status": "error",
  "error": {
    "code": "DGK_500",
    "message": "Erro interno no servidor da OriginPay.",
    "param": null
  }
}
</pre>
        </div>
    </div>

    <h2 id="status">Transaction Status</h2>
    <p>O status determina a etapa do ciclo de vida da transação:</p>

    <div class="doc-table-wrap">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="code" style="color: #f59e0b;">pending</td>
                    <td>Aguardando o pagamento pelo cliente.</td>
                </tr>
                <tr>
                    <td class="code" style="color: #10b981;">paid</td>
                    <td>O pagamento foi confirmado e o saldo creditado.</td>
                </tr>
                <tr>
                    <td class="code" style="color: #f43f5e;">failed</td>
                    <td>A cobrança expirou ou o cartão foi recusado.</td>
                </tr>
                <tr>
                    <td class="code" style="color: #a855f7;">refunded</td>
                    <td>O valor foi estornado ao cliente.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="doc-related">
        <h3>Leia também:</h3>
        <div class="doc-related-links">
            <a href="{{ route('docs.show', 'pix') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Integração PIX Completa
            </a>
            <a href="{{ route('docs.show', 'card') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Tokenização de Cartão
            </a>
            <a href="{{ route('docs.show', 'errors') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Lidando com Erros (DGK Codes)
            </a>
        </div>
    </div>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'sandbox') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Sandbox e Produção</span>
        </a>
        <a href="{{ route('docs.show', 'pix') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Integração PIX <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
