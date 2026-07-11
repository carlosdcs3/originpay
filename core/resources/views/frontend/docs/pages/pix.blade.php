@extends('frontend.layouts.docs')

@section('title', 'PIX')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#criacao">Criação</a></li>
        <li><a href="#qr-code">QR Code e Copia e Cola</a></li>
        <li><a href="#fluxo">Fluxo de Pagamento</a></li>
        <li><a href="#webhooks">Webhooks de PIX</a></li>
        <li><a href="#expiracao">Expiração</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Pagamentos</a>
        <i class="fas fa-chevron-right"></i>
        <span>PIX</span>
    </div>

    <h1>Integração PIX</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 3 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">O PIX é o método de pagamento mais utilizado no Brasil. A OriginPay atua como um participante indireto, gerando chaves dinâmicas (PIX Cobrança) que garantem conciliação imediata.</p>

    <h2 id="criacao">Criação</h2>
    <p>Para gerar um PIX, passe <code>"method": "pix"</code> na criação do pagamento. Você também pode definir o tempo de expiração do código PIX gerado através do campo opcional <code>expires_in</code> (em segundos).</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="req-json">Exemplo Request</div>
            </div>
        </div>
        <div class="doc-code-content" id="req-json">
<pre>
{
  "amount": 5000,
  "method": "pix",
  "expires_in": 3600, // Expira em 1 hora
  "customer": { ... }
}
</pre>
        </div>
    </div>

    <h2 id="qr-code">QR Code e Copia e Cola</h2>
    <p>A resposta da requisição trará o objeto <code>qr_code</code>. Ele contém as duas informações que você precisa mostrar no seu frontend:</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li><strong style="color: #fff;">payload:</strong> A string bruta do PIX Copia e Cola. Pode ser colada diretamente no aplicativo do banco.</li>
        <li><strong style="color: #fff;">image_base64:</strong> Uma imagem PNG do QR Code pronta para ser exibida na tag <code>&lt;img src="data:image/png;base64,..."&gt;</code> do HTML.</li>
    </ul>

    <h2 id="fluxo">Fluxo de Pagamento</h2>
    <div class="doc-flowchart">
        <div class="doc-flowchart-node">
            1. Você chama POST /v1/payments
        </div>
        <div class="doc-flowchart-arrow"></div>
        <div class="doc-flowchart-node" style="border-color: #38bdf8;">
            2. Exibe o QR Code para o Cliente
        </div>
        <div class="doc-flowchart-arrow"></div>
        <div class="doc-flowchart-node" style="border-color: #a855f7;">
            3. Cliente paga pelo App do Banco
        </div>
        <div class="doc-flowchart-arrow"></div>
        <div class="doc-flowchart-node" style="border-color: #10b981; background: rgba(16, 185, 129, 0.1);">
            4. OriginPay envia webhook "payment.paid"
        </div>
    </div>

    <h2 id="webhooks">Webhooks de PIX</h2>
    <p>Diferente de um cartão de crédito, um PIX não é capturado na mesma hora em que a requisição é feita. Por isso, <strong>você não deve confiar apenas no retorno da API</strong>. Você deve exibir o QR Code para o usuário e aguardar. Assim que o banco do pagador confirmar o envio, nós liquidamos a transação e disparamos um webhook <code>payment.paid</code> para você.</p>
    
    <div class="doc-alert doc-alert-tip">
        <i class="fas fa-lightbulb"></i>
        <div>
            <strong>UX Recomendada</strong>
            <p>Se o seu cliente está esperando na tela do navegador, implemente um polling (ex: a cada 3 segundos) consultando o status do pagamento, ou utilize websockets atrelados ao webhook, para redirecionar o usuário assim que ele pagar no celular.</p>
        </div>
    </div>

    <h2 id="expiracao">Expiração</h2>
    <p>Por padrão, todo PIX gerado na OriginPay tem um tempo de vida útil de <strong>30 minutos</strong>. Após esse tempo, se o cliente tentar escanear o QR Code, o aplicativo do banco retornará um erro e o webhook <code>payment.failed</code> será disparado para o seu servidor.</p>

    <div class="doc-related">
        <h3>Leia também:</h3>
        <div class="doc-related-links">
            <a href="{{ route('docs.show', 'payments') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Referência Completa: POST /payments
            </a>
            <a href="{{ route('docs.show', 'webhooks') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Como escutar Webhooks
            </a>
        </div>
    </div>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'payments') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Criar Pagamento</span>
        </a>
        <a href="{{ route('docs.show', 'card') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Cartão de Crédito <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
