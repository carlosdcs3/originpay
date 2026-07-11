@extends('frontend.layouts.docs')

@section('title', 'Cartão')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#credito">Crédito à vista</a></li>
        <li><a href="#parcelamento">Parcelamento</a></li>
        <li><a href="#pci">PCI Compliance</a></li>
        <li><a href="#fluxo">Fluxo de Tokenização</a></li>
        <li><a href="#fluxo-transacao">Autorização vs Captura</a></li>
        <li><a href="#recusa">Recusa e Chargeback</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Pagamentos</a>
        <i class="fas fa-chevron-right"></i>
        <span>Cartão</span>
    </div>

    <h1>Pagamento com Cartão</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 4 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">A OriginPay suporta transações com cartões de crédito das principais bandeiras do mercado (Visa, Mastercard, Amex, Elo, Hipercard). O processamento obedece às mais rígidas normas PCI-DSS.</p>

    <h2 id="pci">PCI Compliance</h2>
    <div class="doc-alert doc-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Requisito PCI</strong>
            <p>Os dados sensíveis do cartão (número, CVV) NUNCA devem passar pelo seu backend. Você deve sempre tokenizar o cartão no frontend utilizando o nosso Javascript SDK antes de criar a cobrança.</p>
        </div>
    </div>

    <h2 id="fluxo">Fluxo de Tokenização</h2>
    <div class="doc-flowchart">
        <div class="doc-flowchart-node" style="border-color: #f59e0b;">
            1. Frontend coleta os dados do cartão
        </div>
        <div class="doc-flowchart-arrow"></div>
        <div class="doc-flowchart-node" style="border-color: #38bdf8;">
            2. Frontend gera o tok_123 com a Chave Pública
        </div>
        <div class="doc-flowchart-arrow"></div>
        <div class="doc-flowchart-node" style="border-color: #a855f7;">
            3. Frontend envia o Token pro seu Backend
        </div>
        <div class="doc-flowchart-arrow"></div>
        <div class="doc-flowchart-node" style="border-color: #10b981; background: rgba(16, 185, 129, 0.1);">
            4. Seu Backend processa o pagamento
        </div>
    </div>

    <h2 id="credito">Crédito à vista</h2>
    <p>Para criar um pagamento com cartão, passe <code>"method": "credit_card"</code> e envie o token seguro retornado pelo nosso SDK no campo <code>card_token</code>.</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="req-json">Exemplo Request</div>
            </div>
        </div>
        <div class="doc-code-content" id="req-json">
<pre>
{
  "amount": 15050, // R$ 150,50
  "method": "credit_card",
  "card_token": "tok_1N3k8aL...",
  "installments": 1,
  "customer": { ... }
}
</pre>
        </div>
    </div>

    <h2 id="parcelamento">Parcelamento</h2>
    <p>O campo <code>installments</code> define o número de parcelas. O valor mínimo de cada parcela é definido nas configurações da sua conta (geralmente R$ 5,00). Você pode repassar o custo financeiro do parcelamento (Juros) para o cliente final alterando sua configuração no Dashboard.</p>

    <h2 id="fluxo-transacao">Autorização vs Captura</h2>
    <p>Por padrão, a OriginPay realiza a <strong>Autorização e Captura Automática</strong> em uma única etapa. Isso significa que, se a requisição retornar sucesso, o dinheiro já foi efetivamente descontado da fatura do cliente e creditado na sua conta.</p>
    
    <p>No entanto, você pode passar o parâmetro <code>capture: false</code> na criação do pagamento. Nesse caso, a OriginPay irá apenas <strong>reservar (congelar)</strong> o saldo no limite do cartão do cliente por até 5 dias (Pré-Autorização). Posteriormente, você precisará chamar a rota <code>/payments/{id}/capture</code> para liquidar os fundos definitivamente.</p>

    <h2 id="recusa">Recusa e Chargeback</h2>
    <p><strong>Recusa:</strong> Caso o banco não autorize a transação (falta de limite, suspeita de fraude), a API retornará imediatamente o erro e o pagamento ficará <code>failed</code>. Você receberá o código do motivo da recusa (ex: `insufficient_funds`).</p>
    
    <p><strong>Chargeback:</strong> Ocorre quando o cliente não reconhece a compra na fatura e contesta junto ao banco. Quando isso acontece, disparamos o webhook <code>chargeback.opened</code>. O valor é bloqueado na sua conta até que a disputa seja resolvida (com o envio de provas documentais, também disponível via API).</p>

    <div class="doc-related">
        <h3>Leia também:</h3>
        <div class="doc-related-links">
            <a href="{{ route('docs.show', 'payments') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Referência Completa: POST /payments
            </a>
            <a href="{{ route('docs.show', 'sdks') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Bibliotecas Frontend
            </a>
        </div>
    </div>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'pix') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">PIX</span>
        </a>
        <a href="{{ route('docs.show', 'webhooks') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Eventos de Webhook <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
