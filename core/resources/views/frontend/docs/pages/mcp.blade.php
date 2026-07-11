@extends('frontend.layouts.docs')

@section('title', 'MCP Server')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#o-que-e">O que é MCP</a></li>
        <li><a href="#configuracao">Como Configurar</a></li>
        <li><a href="#autenticacao">Autenticação</a></li>
        <li><a href="#ferramentas">Ferramentas Disponíveis</a></li>
        <li><a href="#llms">Uso com LLMs</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Desenvolvedores</a>
        <i class="fas fa-chevron-right"></i>
        <span>MCP Server</span>
    </div>

    <h1>Model Context Protocol (MCP)</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 4 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">Integre a infraestrutura da OriginPay diretamente aos seus agentes de Inteligência Artificial e LLMs de forma segura e padronizada utilizando o protocolo MCP (Model Context Protocol).</p>

    <h2 id="o-que-e">O que é MCP?</h2>
    <p>Criado pela Anthropic (Claude), o Model Context Protocol (MCP) é um padrão aberto que permite que assistentes de IA se conectem de forma segura a fontes de dados locais e remotas.</p>
    <p>O <strong>OriginPay MCP Server</strong> expõe as ferramentas da nossa API como "Tools" que podem ser chamadas autonomamente por LLMs para gerenciar pagamentos, verificar extratos e estornar transações em tempo real, sempre respeitando as permissões da sua API Key.</p>

    <h2 id="configuracao">Como Configurar</h2>
    <p>Você pode rodar o servidor MCP da OriginPay localmente via npx, ou configurá-lo no arquivo de configuração do seu cliente MCP (como o app Desktop do Claude).</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="mcp-config">claude_desktop_config.json</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="mcp-config">
<pre>
{
  "mcpServers": {
    "originpay": {
      "command": "npx",
      "args": [
        "-y",
        "@originpay/mcp-server"
      ],
      "env": {
        "DIGISYNK_API_KEY": "sk_test_xxxxxxxxx"
      }
    }
  }
}
</pre>
        </div>
    </div>

    <h2 id="autenticacao">Autenticação</h2>
    <p>A autenticação do servidor MCP é feita inteiramente via variável de ambiente <code>DIGISYNK_API_KEY</code>. Certifique-se de que o processo rodando o MCP tenha acesso a essa variável. Recomendamos fortemente o uso de chaves restritas (com permissões específicas, ex: apenas leitura) caso vá compartilhar seu ambiente com múltiplos agentes.</p>

    <h2 id="ferramentas">Ferramentas Disponíveis</h2>
    <p>Uma vez conectado, o seu agente de IA terá acesso às seguintes ferramentas de forma nativa:</p>

    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li><strong style="color: #fff;">create_payment(amount, method, customer)</strong>: Gera uma nova intenção de pagamento.</li>
        <li><strong style="color: #fff;">get_payment_status(payment_id)</strong>: Retorna o status atual de uma transação.</li>
        <li><strong style="color: #fff;">refund_payment(payment_id, amount)</strong>: Solicita o estorno total ou parcial de uma transação.</li>
        <li><strong style="color: #fff;">get_balance()</strong>: Retorna o saldo disponível na carteira.</li>
    </ul>

    <h2 id="llms">Uso com LLMs</h2>
    <p>Se você estiver desenvolvendo um chatbot de atendimento financeiro para a sua empresa usando a API do Claude ou da OpenAI (via bibliotecas que suportam MCP), basta apontar para o servidor rodando. Exemplo de prompt que a IA conseguirá resolver:</p>

    <div class="doc-alert doc-alert-info">
        <i class="fas fa-robot"></i>
        <div>
            <strong>Exemplo de Interação</strong>
            <p style="font-style: italic;">"O cliente joao@exemplo.com está pedindo reembolso do pagamento pay_123. Por favor, verifique se o status está pago e realize o estorno total."</p>
            <p style="margin-top: 8px;">A IA usará a ferramenta <code>get_payment_status</code> e em seguida <code>refund_payment</code> automaticamente.</p>
        </div>
    </div>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'sdks') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">SDKs Oficiais</span>
        </a>
        <a href="{{ route('docs.show', 'errors') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Códigos de Erro <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
