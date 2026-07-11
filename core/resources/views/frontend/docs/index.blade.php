@extends('frontend.layouts.docs')

@section('title', 'Visão Geral')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#o-que-e">O que é a OriginPay</a></li>
        <li><a href="#como-funciona">Como Funciona</a></li>
        <li><a href="#conceitos">Conceitos Essenciais</a></li>
        <li><a href="#ambientes">Ambientes</a></li>
        <li><a href="#garantias">API Guarantees</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <span>Visão Geral</span>
    </div>

    <h1>Visão Geral</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 3 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">A OriginPay é a infraestrutura financeira definitiva para plataformas digitais. Fornecemos um conjunto completo de APIs para integrar pagamentos via PIX e Cartão com máxima conversão e segurança.</p>

    <h2 id="o-que-e">O que é a OriginPay</h2>
    <p>Construída com foco na experiência do desenvolvedor (DX), nossa plataforma abstrai toda a complexidade regulatória e bancária do Brasil. Você não precisa se preocupar com liquidação, conciliação de arquivos de retorno bancário ou regras complexas de repasse de pagamentos (split).</p>
    <p>Oferecemos integrações via API RESTful, SDKs oficiais nas principais linguagens, ferramentas de Sandbox isoladas e Webhooks robustos para notificações em tempo real.</p>

    <h2 id="como-funciona">Como Funciona</h2>
    <p>O fluxo geral de operação na OriginPay é composto por três etapas principais:</p>

    <div class="doc-grid" style="grid-template-columns: 1fr; gap: 24px; margin-bottom: 32px;">
        <div class="doc-alert doc-alert-info" style="margin:0;">
            <i class="fas fa-1"></i>
            <div>
                <strong>Criação da Cobrança</strong>
                <p>Você utiliza a API para gerar uma transação (PIX ou Cartão). A resposta devolve as instruções de pagamento (como o Payload do PIX copia e cola).</p>
            </div>
        </div>
        <div class="doc-alert doc-alert-info" style="margin:0;">
            <i class="fas fa-2"></i>
            <div>
                <strong>Confirmação de Pagamento</strong>
                <p>O cliente final realiza o pagamento no aplicativo do banco dele. Nossa infraestrutura detecta a liquidação instantaneamente junto ao Banco Central ou Adquirente.</p>
            </div>
        </div>
        <div class="doc-alert doc-alert-info" style="margin:0;">
            <i class="fas fa-3"></i>
            <div>
                <strong>Notificação via Webhook</strong>
                <p>Nós disparamos um evento HTTP (Webhook) para o seu servidor informando que a fatura foi paga. O seu sistema, de forma automática, libera o produto ou serviço para o cliente final.</p>
            </div>
        </div>
    </div>

    <h2 id="conceitos">Conceitos Essenciais</h2>
    <p>Antes de começar a integração, é importante entender os termos que utilizamos em nossa documentação:</p>

    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li><strong style="color: #fff;">Payment:</strong> Representa a intenção de cobrança. Pode ter o status `pending`, `paid`, `failed`, ou `refunded`.</li>
        <li><strong style="color: #fff;">Idempotência:</strong> A garantia de que uma mesma requisição não será processada duas vezes se houver falha de rede ou timeout.</li>
        <li><strong style="color: #fff;">Webhook:</strong> Uma rota (URL) no seu servidor que recebe notificações de eventos em tempo real da OriginPay.</li>
        <li><strong style="color: #fff;">Chargeback:</strong> Quando o portador do cartão contesta a compra junto ao emissor do cartão.</li>
    </ul>

    <h2 id="ambientes">Ambientes</h2>
    <p>A OriginPay oferece dois ambientes isolados para os seus aplicativos operarem com segurança.</p>

    <div class="doc-grid">
        <a href="{{ route('docs.show', 'sandbox') }}" class="doc-card">
            <div class="doc-card-icon"><i class="fas fa-flask"></i></div>
            <h3>Sandbox</h3>
            <p>Utilize para desenvolvimento e testes automatizados. O dinheiro não é real.</p>
        </a>
        <div class="doc-card" style="cursor: default;">
            <div class="doc-card-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-rocket"></i></div>
            <h3>Produção</h3>
            <p>Utilizado por clientes reais transacionando dinheiro real. As chaves de API começam com `sk_live_`.</p>
        </div>
    </div>

    <h2 id="garantias">API Guarantees</h2>
    <p>Nossa plataforma foi desenhada para altíssima disponibilidade (99.99% Uptime) e confiabilidade matemática em operações financeiras. Ao construir na OriginPay, você herda as seguintes garantias arquiteturais:</p>

    <div class="doc-table-wrap">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Garantia</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Idempotência</strong></td>
                    <td>Todas as rotas POST (mutáveis) suportam o header <code>Idempotency-Key</code>. Se a sua rede cair e você tentar cobrar novamente com a mesma chave em até 24h, nós retornaremos a cobrança anterior sem debitar o cliente duas vezes.</td>
                </tr>
                <tr>
                    <td><strong>Rate Limits</strong></td>
                    <td>Nossa API suporta até 1.000 requisições por segundo por IP, com graceful fallback via HTTP 429 e header <code>Retry-After</code> explícito.</td>
                </tr>
                <tr>
                    <td><strong>Versionamento Seguro</strong></td>
                    <td>Sempre que introduzirmos breaking changes, lançaremos uma nova versão (ex: <code>/v2/</code>). Versões antigas continuam sendo suportadas por pelo menos 24 meses após a depreciação.</td>
                </tr>
                <tr>
                    <td><strong>Webhooks Confiáveis</strong></td>
                    <td>Backoff Exponencial implementado nativamente. Se o seu servidor falhar em retornar HTTP 200, nós tentaremos novamente 8 vezes ao longo de 3 dias.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="doc-pagination">
        <div></div> {{-- Empty placeholder for the left item --}}
        <a href="{{ route('docs.show', 'quickstart') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Comece em 5 minutos <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
