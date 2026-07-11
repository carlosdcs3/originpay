@extends('frontend.layouts.docs')

@section('title', 'SDKs Oficiais')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#node">Node.js (TypeScript)</a></li>
        <li><a href="#php">PHP</a></li>
        <li><a href="#python">Python</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Desenvolvedores</a>
        <i class="fas fa-chevron-right"></i>
        <span>SDKs Oficiais</span>
    </div>

    <h1>Bibliotecas e SDKs</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 3 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">Recomendamos utilizar nossas bibliotecas oficiais para interagir com a API da OriginPay. Elas já lidam com autenticação, retentativas (retries) em caso de falha de rede e paginação.</p>

    <h2 id="node">Node.js (TypeScript)</h2>
    <p>O pacote para Node suporta TypeScript nativamente (tipos já inclusos) e funciona tanto em CommonJS quanto ES Modules.</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="pkg-npm">NPM</div>
                <div class="doc-code-tab" data-target="pkg-yarn">Yarn</div>
                <div class="doc-code-tab" data-target="pkg-pnpm">pnpm</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="pkg-npm">
<pre>npm install originpay</pre>
        </div>
        <div class="doc-code-content" id="pkg-yarn" style="display:none;">
<pre>yarn add originpay</pre>
        </div>
        <div class="doc-code-content" id="pkg-pnpm" style="display:none;">
<pre>pnpm add originpay</pre>
        </div>
    </div>

    <p><strong>Primeira requisição:</strong></p>
    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="ex-node">index.ts</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="ex-node">
<pre>
import { OriginPay } from 'originpay';

const originpay = new OriginPay('sk_test_xxxxxxxxx');

async function createPayment() {
  const payment = await originpay.payments.create({
    amount: 5000,
    method: 'pix',
    customer: {
      name: 'Maria Souza',
      document: '222.222.222-22'
    }
  });

  console.log(payment.qr_code.payload);
}
</pre>
        </div>
    </div>

    <h2 id="php">PHP</h2>
    <p>O SDK para PHP requer versão 8.1 ou superior e suporta a especificação PSR-18 para clientes HTTP e PSR-7 para HTTP Messages.</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="pkg-comp">Composer</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="pkg-comp">
<pre>composer require originpay/originpay-php</pre>
        </div>
    </div>

    <p><strong>Primeira requisição:</strong></p>
    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="ex-php">index.php</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="ex-php">
<pre>
&lt;?php
require 'vendor/autoload.php';

$client = new \OriginPay\Client('sk_test_xxxxxxxxx');

$payment = $client->payments->create([
    'amount' => 5000,
    'method' => 'pix',
    'customer' => [
        'name' => 'Maria Souza',
        'document' => '222.222.222-22'
    ]
]);

echo $payment->qr_code->payload;
</pre>
        </div>
    </div>

    <h2 id="python">Python</h2>
    <p>Nosso pacote oficial Python é compatível com Python 3.8+ e oferece suporte nativo à Asyncio.</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="pkg-pip">pip</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="pkg-pip">
<pre>pip install originpay</pre>
        </div>
    </div>

    <p><strong>Primeira requisição:</strong></p>
    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="ex-py">main.py</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="ex-py">
<pre>
import originpay

originpay.api_key = "sk_test_xxxxxxxxx"

payment = originpay.Payment.create(
    amount=5000,
    method="pix",
    customer={
        "name": "Maria Souza",
        "document": "222.222.222-22"
    }
)

print(payment.qr_code.payload)
</pre>
        </div>
    </div>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'openapi') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">OpenAPI Spec</span>
        </a>
        <a href="{{ route('docs.show', 'mcp') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">MCP Server <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
