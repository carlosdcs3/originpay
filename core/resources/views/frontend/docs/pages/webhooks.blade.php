@extends('frontend.layouts.docs')

@section('title', 'Webhooks')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#como-funciona">Como funciona</a></li>
        <li><a href="#eventos">Eventos Disponíveis</a></li>
        <li><a href="#payload">Payload do Webhook</a></li>
        <li><a href="#retentativas">Retentativas (Retries)</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Webhooks</a>
        <i class="fas fa-chevron-right"></i>
        <span>Eventos e Retries</span>
    </div>

    <h1>Eventos de Webhook</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 5 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">Webhooks são requisições HTTP POST que a OriginPay faz para o seu servidor informando que algo aconteceu. Eles são a forma mais confiável de saber quando um pagamento PIX foi compensado ou quando um chargeback ocorreu.</p>

    <h2 id="como-funciona">Como funciona</h2>
    <p>Em vez de você fazer polling (perguntar repetidas vezes para a API se um pagamento já foi pago), nós avisamos você. Para isso:</p>
    <ol style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>Você cria uma rota no seu backend preparada para receber requisições POST (ex: <code>https://api.seusite.com/webhooks/originpay</code>).</li>
        <li>Você cadastra essa URL no Dashboard da OriginPay.</li>
        <li>Sempre que houver uma transição de estado numa entidade sua, nós faremos um POST para a sua URL contendo um Payload JSON.</li>
        <li>Seu servidor deve responder rapidamente com HTTP 200 para confirmar o recebimento.</li>
    </ol>

    <div class="doc-alert doc-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Retorne HTTP 200 (OK) rapidamente</strong>
            <p>Se o seu processamento for demorado, coloque a notificação numa fila (RabbitMQ, SQS, Redis) e retorne HTTP 200 imediatamente. Se você demorar mais de 10 segundos para responder, a OriginPay considerará como falha (timeout) e re-enviará o webhook.</p>
        </div>
    </div>

    <h2 id="eventos">Eventos Disponíveis</h2>
    <p>Você pode configurar sua URL para escutar eventos específicos ou todos eles.</p>

    <div style="overflow-x:auto; margin-bottom: 24px;">
        <table style="width: 100%; text-align: left; border-collapse: collapse; color: var(--doc-text);">
            <thead>
                <tr style="border-bottom: 1px solid var(--doc-border);">
                    <th style="padding: 12px 8px; color: #fff;">Tipo do Evento</th>
                    <th style="padding: 12px 8px; color: #fff;">Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">payment.created</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">Uma nova intenção de pagamento foi gerada.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">payment.paid</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">O pagamento foi confirmado e o dinheiro liquidado.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">payment.failed</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">O cartão foi recusado ou o PIX expirou.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">payment.refunded</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">O lojista estornou a transação.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">chargeback.opened</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">O cliente abriu uma disputa (Chargeback). O valor foi bloqueado.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">chargeback.won</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">O lojista venceu a disputa e o dinheiro foi devolvido à carteira.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">chargeback.lost</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">O lojista perdeu a disputa. O estorno foi mantido ao cliente.</td>
                </tr>
                <tr style="border-bottom: 1px dashed rgba(255,255,255,0.05);">
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">payout.created</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">Foi solicitada uma transferência para a conta bancária do lojista.</td>
                </tr>
                <tr>
                    <td style="padding: 12px 8px; font-family: monospace; color: var(--doc-primary);">payout.paid</td>
                    <td style="padding: 12px 8px; color: var(--doc-muted); font-size: 0.9rem;">A transferência bancária (TED/PIX out) foi concluída com sucesso.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 id="payload">Payload do Webhook</h2>
    <p>O JSON enviado via POST seguirá o seguinte padrão estrutural. O objeto <code>data</code> conterá a entidade que originou o evento (ex: os dados do pagamento).</p>

    <div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-tabs">
                <div class="doc-code-tab active" data-target="req-json">Webhook Payload (Exemplo)</div>
            </div>
            <button class="doc-code-copy"><i class="far fa-copy"></i> Copiar</button>
        </div>
        <div class="doc-code-content" id="req-json">
<pre>
{
  "event": "payment.paid",
  "id": "evt_9x8c7v6b5n",
  "created_at": "2026-06-25T14:35:10Z",
  "data": {
    "id": "pay_59d8s21k",
    "amount": 10000,
    "status": "paid",
    "method": "pix",
    "customer": {
      "name": "João Silva",
      "email": "joao@exemplo.com",
      "document": "11111111111"
    }
  }
}
</pre>
        </div>
    </div>

    <h2 id="retentativas">Retentativas (Retries)</h2>
    <p>A web nem sempre é perfeita. Seu servidor pode cair ou sua conexão pode estar instável no momento do disparo. Por isso, implementamos uma lógica agressiva de Backoff Exponencial.</p>
    <p>Se a sua URL retornar qualquer HTTP Status diferente de <code>2xx</code> (ou dar timeout), nós tentaremos entregar o mesmo payload novamente nos seguintes intervalos aproximados:</p>

    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>2 minutos</li>
        <li>15 minutos</li>
        <li>1 hora</li>
        <li>4 horas</li>
        <li>12 horas</li>
        <li>24 horas</li>
        <li>48 horas</li>
        <li>72 horas</li>
    </ul>

    <p>Após a última tentativa (aproximadamente 3 dias e meio), o evento é descartado e não será mais reenviado automaticamente. Você pode reenviar manualmente no Dashboard de Webhooks.</p>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'card') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Cartão de Crédito</span>
        </a>
        <a href="{{ route('docs.show', 'hmac') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Assinatura HMAC <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
