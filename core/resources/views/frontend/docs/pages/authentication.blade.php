@extends('frontend.layouts.docs')

@section('title', 'Autenticação')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#bearer-token">Bearer Token</a></li>
        <li><a href="#api-keys">API Keys</a></li>
        <li><a href="#headers">Headers Requeridos</a></li>
        <li><a href="#exemplos">Exemplos</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Introdução</a>
        <i class="fas fa-chevron-right"></i>
        <span>Autenticação</span>
    </div>

    <h1>Autenticação</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 4 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">A OriginPay utiliza chaves secretas para autenticar e identificar as requisições que chegam à nossa API. Você pode gerenciar as chaves diretamente no Dashboard de Desenvolvedor.</p>

    <h2 id="bearer-token">Bearer Token</h2>
    <p>Nossa API espera a autenticação no formato padrão <strong>Bearer Authentication</strong> (também conhecido como token authentication). Para isso, as suas chaves da API devem estar anexadas a todas as requisições no cabeçalho <code>Authorization</code> da requisição HTTP.</p>

    <div class="doc-alert doc-alert-important">
        <i class="fas fa-shield-alt"></i>
        <div>
            <strong>Todas as requisições devem ocorrer em HTTPS</strong>
            <p>Requisições feitas via HTTP puro irão falhar e retornar a mensagem <code>403 Forbidden</code>.</p>
        </div>
    </div>

    <h2 id="api-keys">API Keys</h2>
    <p>Sua conta possui dois ambientes distintos, cada um operando com pares de chaves diferentes:</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li><strong style="color: #fff;">sk_test_...</strong> (Sandbox): Dinheiro simulado. Pode ser usado livremente.</li>
        <li><strong style="color: #fff;">sk_live_...</strong> (Produção): Dinheiro real. As chamadas têm implicações financeiras diretas no sistema.</li>
    </ul>

    <h2 id="headers">Headers Requeridos</h2>
    <p>Para interagir com as nossas rotas seguras, as requisições devem fornecer os seguintes cabeçalhos:</p>
    
    <div style="overflow-x:auto; margin-bottom: 24px;">
        <table style="width: 100%; text-align: left; border-collapse: collapse; color: var(--doc-text);">
            <thead>
                <tr style="border-bottom: 1px solid var(--doc-border);">
                    <th style="padding: 12px 8px; color: #fff;">Header</th>
                    <th style="padding: 12px 8px; color: #fff;">Obrigatório</th>
                    <th style="padding: 12px 8px; color: #fff;">Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">Authorization</td>
                    <td style="padding: 12px 8px;">Sim</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">Bearer token gerado no dashboard.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">Content-Type</td>
                    <td style="padding: 12px 8px;">Sim</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">Deve ser <code>application/json</code> em todas rotas POST/PUT.</td>
                </tr>
                <tr>
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">Idempotency-Key</td>
                    <td style="padding: 12px 8px;">Recomendado</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">String única (UUID) para evitar duplicidade de operações em caso de timeout de rede.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 id="exemplos">Exemplos</h2>
    <p>Veja abaixo como enviar os cabeçalhos de autenticação nas principais linguagens:</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="req-curl">cURL</div>
                <div class="doc-code-tab" data-target="req-node">Node.js</div>
                <div class="doc-code-tab" data-target="req-php">PHP</div>
                <div class="doc-code-tab" data-target="req-py">Python</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="req-curl">
<pre>
curl https://api.originpay.com/v1/customers \
  -H "Authorization: Bearer sk_test_xxxxxxxxx" \
  -H "Idempotency-Key: a1b2c3d4e5f6"
</pre>
        </div>
        <div class="doc-code-content" id="req-node" style="display:none;">
<pre>
const axios = require('axios');

axios.get('https://api.originpay.com/v1/customers', {
  headers: {
    'Authorization': 'Bearer sk_test_xxxxxxxxx',
    'Idempotency-Key': 'a1b2c3d4e5f6'
  }
}).then(response => console.log(response.data));
</pre>
        </div>
        <div class="doc-code-content" id="req-php" style="display:none;">
<pre>
$client = new \GuzzleHttp\Client();

$response = $client->request('GET', 'https://api.originpay.com/v1/customers', [
  'headers' => [
    'Authorization' => 'Bearer sk_test_xxxxxxxxx',
    'Idempotency-Key' => 'a1b2c3d4e5f6',
  ]
]);

echo $response->getBody();
</pre>
        </div>
        <div class="doc-code-content" id="req-py" style="display:none;">
<pre>
import requests

headers = {
    "Authorization": "Bearer sk_test_xxxxxxxxx",
    "Idempotency-Key": "a1b2c3d4e5f6"
}

response = requests.get("https://api.originpay.com/v1/customers", headers=headers)
print(response.json())
</pre>
        </div>
    </div>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'quickstart') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Comece em 5 minutos</span>
        </a>
        <a href="{{ route('docs.show', 'sandbox') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Sandbox e Produção <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
