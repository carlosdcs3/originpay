@extends('frontend.layouts.docs')

@section('title', 'Códigos de Erro')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#formato">Formato do Erro</a></li>
        <li><a href="#http">HTTP Status</a></li>
        <li><a href="#tabela">Tabela de Códigos</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Referência</a>
        <i class="fas fa-chevron-right"></i>
        <span>Códigos de Erro</span>
    </div>

    <h1>Códigos de Erro</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 3 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">A OriginPay utiliza códigos HTTP convencionais para indicar sucesso ou falha numa requisição. Em caso de falha, o corpo da resposta sempre trará um objeto JSON detalhando o erro com um código específico.</p>

    <h2 id="formato">Formato do Erro</h2>
    <p>Abaixo está um exemplo da estrutura JSON retornada quando uma validação falha ou quando ocorre um erro de negócio.</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="req-json">Exemplo de Erro (400 Bad Request)</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="req-json">
<pre>
{
  "status": "error",
  "error": {
    "code": "DGK_003",
    "message": "Saldo insuficiente para completar a transferência.",
    "param": "amount"
  }
}
</pre>
        </div>
    </div>

    <h2 id="http">HTTP Status</h2>
    <p>Nós utilizamos os seguintes códigos HTTP:</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li><strong style="color: #10b981;">200 - OK:</strong> A requisição foi bem sucedida.</li>
        <li><strong style="color: #10b981;">201 - Created:</strong> A requisição foi bem sucedida e um recurso (ex: pagamento) foi criado.</li>
        <li><strong style="color: #f59e0b;">400 - Bad Request:</strong> Faltam parâmetros obrigatórios ou os dados são inválidos.</li>
        <li><strong style="color: #f43f5e;">401 - Unauthorized:</strong> API Key ausente, inválida ou de ambiente errado.</li>
        <li><strong style="color: #f43f5e;">403 - Forbidden:</strong> Sua chave não tem permissão para acessar o recurso.</li>
        <li><strong style="color: #f43f5e;">404 - Not Found:</strong> O recurso (ex: ID do pagamento) não existe.</li>
        <li><strong style="color: #f43f5e;">429 - Too Many Requests:</strong> Você excedeu o Rate Limit.</li>
        <li><strong style="color: #f43f5e;">500 - Server Error:</strong> Algo deu errado nos servidores da OriginPay.</li>
    </ul>

    <h2 id="tabela">Tabela de Códigos (DGK)</h2>
    <p>Estes são os códigos internos devolvidos dentro do objeto <code>error.code</code> que o seu sistema pode tratar programaticamente:</p>

    <div class="doc-table-wrap">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição, Causa e Solução</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="code" style="color: #f43f5e; font-weight: bold; vertical-align: top;">DGK_001</td>
                    <td>
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 8px;">Chave de Autenticação Inválida</strong>
                        <p style="margin-bottom: 4px;"><strong>Causa:</strong> Chave revogada, ambiente errado (test vs live) ou header mal formatado.</p>
                        <p style="margin-bottom: 8px;"><strong>Solução:</strong> Verifique se você enviou <code>Authorization: Bearer sk_...</code>. Acesse o Dashboard e gere uma nova chave caso necessário.</p>
                        <span class="doc-badge doc-badge-get">Todos os Endpoints</span>
                    </td>
                </tr>
                <tr>
                    <td class="code" style="color: #f43f5e; font-weight: bold; vertical-align: top;">DGK_002</td>
                    <td>
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 8px;">Validação de Dados Falhou</strong>
                        <p style="margin-bottom: 4px;"><strong>Causa:</strong> Tipo de dado incorreto (ex: enviou string no lugar de integer) ou campo faltando.</p>
                        <p style="margin-bottom: 8px;"><strong>Solução:</strong> Consulte o campo <code>param</code> da resposta para identificar qual atributo enviar corretamente de acordo com o Schema.</p>
                        <span class="doc-badge doc-badge-post">POST Endpoints</span>
                    </td>
                </tr>
                <tr>
                    <td class="code" style="color: #f43f5e; font-weight: bold; vertical-align: top;">DGK_003</td>
                    <td>
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 8px;">Saldo Insuficiente</strong>
                        <p style="margin-bottom: 4px;"><strong>Causa:</strong> O Payout solicitado é maior do que o Saldo Disponível na sua conta digital.</p>
                        <p style="margin-bottom: 8px;"><strong>Solução:</strong> Cancele o Payout ou adicione fundos via TED/PIX in.</p>
                        <span class="doc-badge doc-badge-post">/v1/payouts</span>
                    </td>
                </tr>
                <tr>
                    <td class="code" style="color: #f43f5e; font-weight: bold; vertical-align: top;">DGK_004</td>
                    <td>
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 8px;">Idempotency Key Duplicada</strong>
                        <p style="margin-bottom: 4px;"><strong>Causa:</strong> Outra requisição POST foi efetuada nas últimas 24h usando o exato mesmo valor em <code>Idempotency-Key</code> mas com um payload diferente.</p>
                        <p style="margin-bottom: 8px;"><strong>Solução:</strong> Gere um novo UUIDv4 para requisições diferentes.</p>
                        <span class="doc-badge doc-badge-post">POST Endpoints</span>
                    </td>
                </tr>
                <tr>
                    <td class="code" style="color: #f43f5e; font-weight: bold; vertical-align: top;">DGK_005</td>
                    <td>
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 8px;">Cartão Recusado</strong>
                        <p style="margin-bottom: 4px;"><strong>Causa:</strong> Falta de limite, suspeita de fraude pelo emissor, ou cartão bloqueado.</p>
                        <p style="margin-bottom: 8px;"><strong>Solução:</strong> Solicite ao usuário que troque o cartão ou o meio de pagamento para PIX.</p>
                        <span class="doc-badge doc-badge-post">/v1/payments</span>
                    </td>
                </tr>
                <tr>
                    <td class="code" style="color: #f43f5e; font-weight: bold; vertical-align: top;">DGK_006</td>
                    <td>
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 8px;">Falha no Bacen / PIX Rejeitado</strong>
                        <p style="margin-bottom: 4px;"><strong>Causa:</strong> Chave PIX destino para Payout não existe ou sistema SPI inoperante.</p>
                        <p style="margin-bottom: 8px;"><strong>Solução:</strong> Valide se a chave informada está correta e pertence ao documento (CPF/CNPJ) informado.</p>
                        <span class="doc-badge doc-badge-post">/v1/payouts</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="doc-related">
        <h3>Leia também:</h3>
        <div class="doc-related-links">
            <a href="{{ route('docs.show', 'openapi') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Especificação OpenAPI
            </a>
            <a href="{{ route('docs.show', 'sandbox') }}" class="doc-related-link">
                <i class="fas fa-file-alt" style="color: var(--doc-primary);"></i>
                Como simular erros no Sandbox
            </a>
        </div>
    </div>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'mcp') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">MCP Server</span>
        </a>
        <div style="flex:1;"></div>
    </div>
@endsection
