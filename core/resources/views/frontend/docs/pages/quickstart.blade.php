@extends('frontend.layouts.docs')

@section('title', 'Comece em 5 minutos')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#passo-1">1. Criar conta</a></li>
        <li><a href="#passo-2">2. Gerar API Key</a></li>
        <li><a href="#passo-3">3. Criar primeira cobrança</a></li>
        <li><a href="#passo-4">4. Receber Webhook</a></li>
        <li><a href="#passo-5">5. Ir para Produção</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Introdução</a>
        <i class="fas fa-chevron-right"></i>
        <span>Comece em 5 minutos</span>
    </div>

    <h1>Comece em 5 minutos</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 5 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">Este guia prático foi criado para você testar a OriginPay do zero. Ao final, você terá processado o seu primeiro pagamento em nosso ambiente de testes (Sandbox).</p>

    <div class="doc-alert doc-alert-tip">
        <i class="fas fa-lightbulb"></i>
        <div>
            <strong>Dica de Produtividade</strong>
            <p>Sugerimos a utilização do Postman, Insomnia ou cURL para executar os comandos deste tutorial. Se preferir, você também pode usar nossos SDKs oficiais.</p>
        </div>
    </div>

    <h2 id="passo-1">1. Criar conta</h2>
    <p>O primeiro passo é acessar a OriginPay e criar uma conta gratuita. Ao criar a sua conta, você ganha acesso instantâneo ao nosso ambiente Sandbox, sem necessidade de enviar documentos KYC ou passar por aprovação.</p>
    <a href="{{ route('user.register') }}" class="btn-doc btn-doc-primary" style="margin-bottom: 24px;">Criar minha conta gratuita</a>

    <h2 id="passo-2">2. Gerar API Key</h2>
    <p>Para interagir com as nossas APIs, você precisa de uma chave de autenticação (API Key). Esta chave identifica a sua plataforma nos nossos sistemas.</p>
    <ol style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>Acesse o <strong>Dashboard</strong>.</li>
        <li>No menu lateral, vá em <strong>Integrações (API)</strong>.</li>
        <li>Abaixe até encontrar a seção <strong>API Keys (Sandbox)</strong>.</li>
        <li>Clique em <strong>Gerar nova chave</strong> ou copie a chave existente, que deve ter o formato <code>sk_test_...</code>.</li>
    </ol>

    <div class="doc-alert doc-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Mantenha sua chave segura</strong>
            <p>Nunca exponha a sua chave secreta <code>sk_test_xxxxxxxxx</code> em aplicações frontend, como no código fonte do seu site, apps mobile ou repositórios públicos no GitHub.</p>
        </div>
    </div>

    <h2 id="passo-3">3. Criar primeira cobrança</h2>
    <p>Agora vamos usar a API para criar uma cobrança PIX genérica no valor de R$ 100,00.</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="req-curl">cURL</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="req-curl">
<pre>
curl -X POST https://api.originpay.com/v1/payments \
  -H "Authorization: Bearer sk_test_xxxxxxxxx" \
  -H "Idempotency-Key: my_first_payment_001" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 10000,
    "method": "pix",
    "customer": {
      "name": "João Silva",
      "document": "111.111.111-11",
      "email": "joao@exemplo.com"
    }
  }'
</pre>
        </div>
    </div>

    <p>A resposta da API será semelhante a isto:</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="res-json">JSON</div>
            </div>
        </div>
        <div class="doc-code-content" id="res-json">
<pre>
{
  "status": "success",
  "request_id": "req_xyz987",
  "data": {
    "id": "pay_12345",
    "amount": 10000,
    "status": "pending",
    "qr_code": {
      "payload": "00020101021226...5802BR5910Joao Silva...",
      "image_base64": "iVBORw0KGgoAAAANSUhEUgAA..."
    }
  }
}
</pre>
        </div>
    </div>

    <h2 id="passo-4">4. Receber Webhook</h2>
    <p>A API gerou um PIX válido (embora falso, em ambiente Sandbox). Para testar o pagamento sem precisar gastar dinheiro, nós oferecemos a ferramenta de <strong>Simulação de Webhook</strong>.</p>
    <p>No Dashboard Sandbox, acesse a fatura criada e clique em <strong>"Simular Pagamento"</strong>. A OriginPay irá disparar instantaneamente um evento HTTP POST <code>payment.paid</code> para a URL cadastrada por você nos Webhooks, contendo a notificação oficial da liquidação.</p>

    <h2 id="passo-5">5. Ir para Produção</h2>
    <p>O seu fluxo técnico no Sandbox já é idêntico ao de Produção. O código não precisa ser alterado estruturalmente, exceto pela URL (se utilizar SDK ele gerencia a troca) e pela chave de API.</p>
    <p>Para ativar sua conta e transacionar de verdade:</p>
    <ol style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8;">
        <li>Na dashboard, preencha o seu <strong>Cadastro KYC</strong> (Know Your Customer).</li>
        <li>Aguarde a validação pela nossa equipe de Compliance (geralmente concluída no mesmo dia).</li>
        <li>Gere uma nova API Key no menu Produção (a chave será <code>sk_live_...</code>).</li>
        <li>Substitua a sua chave de testes pela nova chave de produção no seu sistema.</li>
    </ol>

    <div class="doc-pagination">
        <a href="{{ route('docs.index') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Visão Geral</span>
        </a>
        <a href="{{ route('docs.show', 'authentication') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Autenticação <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
