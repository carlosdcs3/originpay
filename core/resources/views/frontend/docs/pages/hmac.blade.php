@extends('frontend.layouts.docs')

@section('title', 'Assinatura HMAC')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#como-funciona">Como funciona</a></li>
        <li><a href="#headers">Headers</a></li>
        <li><a href="#exemplos">Exemplos de Validação</a></li>
        <li><a href="#replay">Prevenção de Replay Attack</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Webhooks</a>
        <i class="fas fa-chevron-right"></i>
        <span>Assinatura HMAC</span>
    </div>

    <h1>Assinatura HMAC</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 4 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">Para garantir que os Webhooks recebidos no seu servidor foram realmente enviados pela OriginPay, nós assinamos o payload criptograficamente usando HMAC-SHA256.</p>

    <div class="doc-alert doc-alert-important">
        <i class="fas fa-shield-alt"></i>
        <div>
            <strong>Requisito de Segurança Crítico</strong>
            <p>Se você não validar a assinatura, um hacker mal intencionado pode descobrir a URL do seu webhook e enviar uma requisição forjada dizendo que um pagamento foi realizado, liberando o serviço de graça no seu sistema.</p>
        </div>
    </div>

    <h2 id="como-funciona">Como funciona</h2>
    <p>A assinatura garante que o payload do corpo da requisição não foi adulterado no meio do caminho (Integridade) e que quem enviou possui a mesma chave secreta que você (Autenticidade).</p>
    <p>A chave secreta do Webhook é gerada quando você cadastra a URL no Dashboard. Ela começa com <code>whsec_...</code> e é diferente da sua chave de API.</p>

    <h2 id="headers">Headers</h2>
    <p>Junto com cada Webhook, a OriginPay envia os seguintes headers de segurança:</p>
    
    <div style="overflow-x:auto; margin-bottom: 24px;">
        <table style="width: 100%; text-align: left; border-collapse: collapse; color: var(--doc-text);">
            <thead>
                <tr style="border-bottom: 1px solid var(--doc-border);">
                    <th style="padding: 12px 8px; color: #fff;">Header</th>
                    <th style="padding: 12px 8px; color: #fff;">Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">OriginPay-Signature</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">O hash resultante do cálculo HMAC-SHA256 (codificado em HEX) gerado através do Timestamp contatenedo ao Payload.</td>
                </tr>
                <tr>
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">OriginPay-Timestamp</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">Timestamp Unix (em segundos) do momento exato em que a requisição foi disparada.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 id="exemplos">Exemplos de Validação</h2>
    <p>O algoritmo para gerar a assinatura e validar com a que nós mandamos é:</p>
    <ol style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>Pegue a string exata (RAW Body) do JSON recebido. Não formate nem processe.</li>
        <li>Concatene o valor do header <code>OriginPay-Timestamp</code> + <code>.</code> + <code>RAW Body</code>.</li>
        <li>Calcule o HMAC-SHA256 da string concatenada usando a sua chave <code>whsec_xxxxxxxxx</code>.</li>
        <li>Compare o resultado em string HEX com o valor recebido no header <code>OriginPay-Signature</code>.</li>
    </ol>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="req-node">Node.js (Express)</div>
                <div class="doc-code-tab" data-target="req-php">PHP</div>
                <div class="doc-code-tab" data-target="req-py">Python</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="req-node">
<pre>
const crypto = require('crypto');
const express = require('express');
const app = express();

const endpointSecret = "whsec_test_123456789";

app.post('/webhook', express.raw({type: 'application/json'}), (request, response) => {
  const sig = request.headers['originpay-signature'];
  const timestamp = request.headers['originpay-timestamp'];
  const payload = request.body.toString('utf8');

  // Concatena: timestamp + ponto + payload bruto
  const signedPayload = `${timestamp}.${payload}`;

  // Calcula o HMAC
  const expectedSignature = crypto
    .createHmac('sha256', endpointSecret)
    .update(signedPayload, 'utf8')
    .digest('hex');

  if (expectedSignature !== sig) {
    return response.status(400).send(`Webhook Error: Invalid Signature`);
  }

  console.log("Assinatura Válida!", JSON.parse(payload));
  response.send();
});
</pre>
        </div>
        <div class="doc-code-content" id="req-php" style="display:none;">
<pre>
$endpoint_secret = "whsec_test_123456789";

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_DIGISYNK_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_DIGISYNK_TIMESTAMP'] ?? '';

$signed_payload = $timestamp . '.' . $payload;

$expected_signature = hash_hmac('sha256', $signed_payload, $endpoint_secret);

if (!hash_equals($expected_signature, $sig_header)) {
    http_response_code(400);
    exit("Webhook Error: Invalid Signature");
}

echo "Assinatura Valida!";
http_response_code(200);
</pre>
        </div>
        <div class="doc-code-content" id="req-py" style="display:none;">
<pre>
import hmac
import hashlib
from flask import Flask, request, abort

app = Flask(__name__)
endpoint_secret = "whsec_test_123456789"

@app.route('/webhook', methods=['POST'])
def webhook():
    payload = request.get_data(as_text=True)
    sig_header = request.headers.get('OriginPay-Signature')
    timestamp = request.headers.get('OriginPay-Timestamp')

    signed_payload = f"{timestamp}.{payload}"
    
    expected_signature = hmac.new(
        endpoint_secret.encode('utf-8'),
        signed_payload.encode('utf-8'),
        hashlib.sha256
    ).hexdigest()

    if not hmac.compare_digest(expected_signature, sig_header):
        abort(400)

    print("Assinatura Valida!")
    return 'OK', 200
</pre>
        </div>
    </div>

    <h2 id="replay">Prevenção de Replay Attack</h2>
    <p>Recomendamos que, além de validar a assinatura criptográfica, o seu código também verifique a idade do cabeçalho <code>OriginPay-Timestamp</code>. Se a diferença entre a hora atual do seu servidor e o timestamp for maior que 5 minutos, você deve rejeitar a requisição.</p>
    <p>Isso impede que um hacker intercepte uma requisição HTTP válida e a reenvie depois de muito tempo (Ataque de Replay).</p>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'webhooks') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Eventos e Retries</span>
        </a>
        <a href="{{ route('docs.show', 'openapi') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">OpenAPI Spec <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
