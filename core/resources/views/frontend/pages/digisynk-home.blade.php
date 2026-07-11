@extends('frontend.layouts.landing')

@section('title', 'OriginPay — Infraestrutura financeira para pagamentos, Pix e liquidações')
@section('description', 'Infraestrutura financeira para criar cobranças Pix, entregar webhooks, acompanhar liquidações e operar pagamentos com segurança.')

@section('content')
@php
    $pixPct = isset($rates['pix']['percent']) ? (float) $rates['pix']['percent'] : 1.5;
    $pixFixed = isset($rates['pix']['fixed']) ? (float) $rates['pix']['fixed'] : 0.30;
    $boletoFixed = isset($rates['boleto']['fixed']) ? (float) $rates['boleto']['fixed'] : 3.99;
    $cryptoPct = isset($rates['crypto']['percent']) ? (float) $rates['crypto']['percent'] : 2;
    $formatPercent = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $formatMoney = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
@endphp

<main class="op-redesign op-ledger-landing" id="home">
    <section class="op-core-hero op-ledger-hero" aria-labelledby="landing-title">
        <div class="container op-core-hero__grid">
            <div class="op-core-hero__copy reveal">
                <p class="op-core-kicker"><span></span> Infraestrutura financeira para negócios digitais</p>
                <h1 id="landing-title">O núcleo financeiro para pagamentos, Pix e liquidações.</h1>
                <p class="op-core-lead">Crie cobranças, entregue eventos em tempo real e acompanhe o estado do dinheiro em uma camada simples de integrar, segura para operar e preparada para escalar.</p>
                <div class="op-core-actions"><a href="{{ route('user.register') }}" class="btn btn-primary">Criar conta gratuita <i class="fas fa-arrow-right" aria-hidden="true"></i></a><a href="{{ route('docs.index') }}" class="btn btn-secondary">Ver documentação <i class="fas fa-code" aria-hidden="true"></i></a></div>
                <ul class="op-core-proof" aria-label="Capacidades da plataforma"><li>Pix em tempo real</li><li>Webhooks assinados</li><li>Sandbox isolado</li><li>Conciliação rastreável</li></ul>
            </div>
            <div class="op-ledger-board reveal" aria-label="Painel operacional OriginPay em tempo real">
                <div class="op-ledger-top"><span>OriginPay Core</span><strong><i></i> produção simulada</strong></div>
                <div class="op-receipt op-receipt--main"><div><span>PIX recebido</span><strong>+ R$ 1.297,90</strong></div><small>TXID OP9F42A73 · E2E E182361202607091841</small></div>
                <div class="op-ledger-lines" aria-label="Timeline operacional da transação">
                    <div class="op-ledger-line is-done"><time>18:41:22</time><span>pix.charge.created</span><strong>QR emitido</strong></div>
                    <div class="op-ledger-line is-done"><time>18:41:38</time><span>payment.confirmed</span><strong>R$ 1.297,90</strong></div>
                    <div class="op-ledger-line is-active"><time>18:41:39</time><span>webhook.delivered</span><strong>38ms</strong></div>
                    <div class="op-ledger-line is-done"><time>18:41:40</time><span>balance.updated</span><strong>R$ 28.941,72</strong></div>
                    <div class="op-ledger-line"><time>D+0</time><span>settlement.completed</span><strong>conciliação OK</strong></div>
                </div>
                <div class="op-ledger-summary"><div><span>Saldo disponível</span><strong>R$ 28.941,72</strong></div><div><span>Liquidação</span><strong>Concluída</strong></div><div><span>Webhook</span><strong>✓ Entregue</strong></div></div>
            </div>
        </div>
    </section>

    <section class="op-event-rail" aria-label="Eventos da infraestrutura"><div class="container"><div class="op-event-ledger"><span>18:41:22 · POST /v1/pix/charges · 200 OK</span><span>18:41:38 · payment.confirmed · +R$ 1.297,90</span><span>18:41:39 · webhook.delivered · 38ms</span><span>D+0 · settlement.completed · conciliação OK</span></div></div></section>

    <section class="op-manifest section" id="ecossistema" aria-labelledby="manifest-title"><div class="container op-manifest__grid"><div class="op-section-heading op-section-heading--left reveal-left"><span>Ledger operacional</span><h2 id="manifest-title">Pagamentos não deveriam ser a parte frágil da sua operação.</h2></div><div class="op-ledger-editorial reveal-right"><p>A OriginPay conecta cobrança, confirmação, evento e liquidação em uma sequência previsível. Cada transação tem status, contexto e trilha operacional para produto, suporte e financeiro trabalharem sem planilhas paralelas.</p><dl><div><dt>Sem rastreio</dt><dd>Webhook perdido</dd></div><div><dt>Com OriginPay</dt><dd>Evento entregue, saldo atualizado e liquidação conciliada</dd></div></dl></div></div></section>

    <section class="op-money-flow section" aria-labelledby="flow-title"><div class="container"><div class="op-section-heading reveal"><span>Como o dinheiro se move</span><h2 id="flow-title">Um fluxo claro do checkout ao saldo disponível.</h2><p>Menos abstração para o negócio. Mais previsibilidade para engenharia e financeiro.</p></div><div class="op-operation-flow">@foreach ([['Recebimento','Cliente inicia Pix'],['Processamento','Cobrança confirmada'],['Webhook','Sistema notificado'],['Liquidação','Movimento conciliado'],['Saldo','Extrato atualizado'],['Saque','Disponível para repasse']] as $step)<article class="op-operation-step reveal"><small>{{ $step[0] }}</small><strong>{{ $step[1] }}</strong></article>@endforeach</div></div></section>

    <section class="op-dev-section section" id="desenvolvedores" aria-labelledby="dev-title"><div class="container op-dev-section__grid"><div class="op-dev-copy reveal-left"><span>API e eventos</span><h2 id="dev-title">Integração técnica sem perder controle financeiro.</h2><p>Endpoints objetivos, webhooks assinados e sandbox para validar cobranças, falhas e callbacks antes da produção.</p><ul class="op-check-list"><li><i class="fas fa-check" aria-hidden="true"></i> REST API com exemplos por caso de uso.</li><li><i class="fas fa-check" aria-hidden="true"></i> Histórico de tentativas e reenvio de webhooks.</li><li><i class="fas fa-check" aria-hidden="true"></i> Estados financeiros legíveis para conciliação.</li></ul><a href="{{ route('docs.index') }}" class="op-text-link">Abrir documentação <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div><div class="op-doc-panel reveal-right"><div class="op-doc-meta"><span class="method">POST</span><code>/v1/pix/charges</code><strong>200 OK</strong></div><pre><code>{
  "amount": 35000,
  "method": "pix",
  "txid": "OP9F42A73",
  "webhook_url": "https://app.com/hooks"
}</code></pre><div class="op-webhook-log"><span>18:41:39</span><code>payment.confirmed</code><strong>assinatura HMAC válida</strong></div></div></div></section>

    <section class="op-layers section" id="seguranca" aria-labelledby="layers-title"><div class="container"><div class="op-section-heading reveal"><span>Operação em camadas</span><h2 id="layers-title">Cobrança, eventos, controle e segurança no mesmo sistema.</h2></div><div class="op-stack-ledger"><article><span>Cobrança</span><strong>Pix, boleto, links e métodos digitais com status padronizado.</strong></article><article><span>Eventos</span><strong>Webhooks assinados, retries e histórico para automações críticas.</strong></article><article><span>Controle</span><strong>Dashboard, extrato e conciliação para entender cada movimento.</strong></article><article><span>Segurança</span><strong>Logs, trilhas de auditoria e práticas para reduzir risco operacional.</strong></article></div></div></section>

    <section class="op-ops-section section" aria-labelledby="ops-title"><div class="container op-ops-layout"><div class="op-section-heading op-section-heading--left reveal-left"><span>Dashboard operacional</span><h2 id="ops-title">Controle para times que precisam responder rápido.</h2><p>Eventos, saldos e exceções em uma visão que reduz dependência de suporte técnico para entender o estado financeiro.</p></div><div class="op-reconcile-panel reveal-right"><div class="op-reconcile-head"><span>Conciliação de hoje</span><strong>R$ 84.920,40</strong></div><div class="op-reconcile-row"><span>Entradas Pix</span><b>1.284</b><em>confirmadas</em></div><div class="op-reconcile-row"><span>Webhooks em retry</span><b>7</b><em>ação necessária</em></div><div class="op-reconcile-row"><span>Liquidação pronta</span><b>R$ 42.180,12</b><em>D+0</em></div></div></div></section>

    <section class="op-pricing-section section" id="precos" aria-labelledby="pricing-title"><div class="container"><div class="op-section-heading reveal"><span>Preços</span><h2 id="pricing-title">Comece sem mensalidade obrigatória.</h2><p>Taxas por transação e condições comerciais para operações de maior volume.</p></div><div class="op-pricing-layout"><div class="op-settlement-note reveal-left" aria-label="Taxas públicas por método"><div class="op-note-title"><span>Nota de liquidação</span><strong>Venda Pix</strong></div><div class="op-note-row"><span>Valor bruto</span><strong>R$ 100,00</strong></div><div class="op-note-row"><span>Taxa OriginPay</span><strong>{{ $formatPercent($pixPct) }}% + {{ $formatMoney($pixFixed) }}</strong></div><div class="op-note-row"><span>Boleto</span><strong>{{ $formatMoney($boletoFixed) }}</strong></div><div class="op-note-row"><span>LTC / SOL</span><strong>{{ $formatPercent($cryptoPct) }}%</strong></div><p>Dashboard, links de pagamento, API, webhooks, antifraude e relatórios inclusos.</p><a href="mailto:comercial@originpay.com.br" class="op-text-link">Falar sobre alto volume <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div><div class="op-fee-simulator op-liquidation-simulator reveal-right"><div class="op-simulator-header"><span>Simulador</span><strong>Nota de liquidação</strong></div><label class="op-money-input" for="opPricingAmount"><span>R$</span><input id="opPricingAmount" type="number" min="1" step="1" value="100" data-pix-percent="{{ $pixPct }}" data-pix-fixed="{{ $pixFixed }}" data-boleto-fixed="{{ $boletoFixed }}" data-crypto-percent="{{ $cryptoPct }}" aria-label="Valor da venda simulada"></label><div class="op-simulator-results"><div><span>Taxa Pix</span><strong id="opPixFee">R$ 1,80</strong><small id="opPixNet">saldo disponível R$ 98,20</small></div><div><span>Taxa boleto</span><strong id="opBoletoFee">R$ 3,99</strong><small id="opBoletoNet">saldo disponível R$ 96,01</small></div><div><span>Taxa LTC / SOL</span><strong id="opCryptoFee">R$ 2,00</strong><small id="opCryptoNet">saldo disponível R$ 98,00</small></div></div><p>Valores estimados para comparação rápida.</p></div></div></div></section>

    <section class="op-faq-section section" aria-labelledby="faq-title"><div class="container op-faq-section__grid"><div class="op-section-heading op-section-heading--left reveal-left"><span>Auditoria operacional</span><h2 id="faq-title">Perguntas reais de integração e operação.</h2><p>Respostas objetivas para avaliar TXID, webhook, liquidação, saldo, saque e API.</p></div><div class="op-faq-list">@foreach ([['Como acompanho o TXID e o EndToEnd ID?','Cada cobrança Pix mantém identificadores de rastreio para consulta, conciliação e suporte.'],['O que acontece se meu webhook falhar?','A entrega fica registrada com tentativas, status e possibilidade de reenvio conforme política de retry.'],['Quando a liquidação aparece no saldo?','Pagamentos confirmados atualizam o extrato e seguem as regras do método e da política comercial ativa.'],['A API ajuda na conciliação?','Sim. Estados de pagamento, taxas, webhooks, saldos e movimentações podem ser consumidos para reduzir reconciliação manual.']] as $faq)<div class="op-faq-item reveal"><div class="op-faq-question">{{ $faq[0] }}<div class="op-faq-chevron"><i class="fas fa-chevron-down" aria-hidden="true"></i></div></div><div class="op-faq-answer"><div class="op-faq-answer-inner">{{ $faq[1] }}</div></div></div>@endforeach</div></div></section>

    <section class="op-final-cta" aria-labelledby="cta-title"><div class="container"><div class="op-production-panel reveal"><div class="op-prod-console"><code>originpay init</code><span>✓ Sandbox disponível</span><span>✓ API pronta</span><span>✓ Ambiente criado</span><span>✓ Chaves disponíveis</span></div><div><h2 id="cta-title">Construa sua próxima cobrança sobre um core financeiro previsível.</h2><p>Crie sua conta, teste o sandbox e conecte pagamentos com eventos rastreáveis.</p><div class="op-final-cta__actions"><a href="{{ route('user.register') }}" class="btn btn-primary">Criar primeira cobrança <i class="fas fa-arrow-right" aria-hidden="true"></i></a><a href="{{ route('docs.index') }}" class="btn btn-secondary">Ver documentação</a></div></div></div></div></section>
</main>
@endsection
