# Customer Subscriptions Final MVP Report

## Status

Customer Subscriptions esta fechado como MVP production-ready para recorrencia merchant-facing.

O modulo foi construido separado do billing SaaS antigo da OriginPay.

## Pronto

- API v1 para criar, listar, consultar e cancelar assinaturas.
- Dominio separado em `customer_subscriptions`, `customer_subscription_items`, `subscription_invoices` e `subscription_invoice_items`.
- Primeira invoice e primeira charge criadas via `ChargeService`.
- Sincronizacao de pagamento via `ChargePaidEvent`.
- Falha/expiracao de charge sincroniza invoice/subscription.
- Renovacao automatica via `subscriptions:renew`.
- Scheduler registrado de hora em hora com `withoutOverlapping` e `onOneServer`.
- Webhooks externos para lifecycle de subscription/invoice.
- Dashboard V2 do merchant em `/user/assinaturas`.
- Multi-tenant em API, dashboard, cancelamento, renovacao e payloads.
- Idempotencia em criacao de assinatura, invoice recorrente e webhook delivery.
- Locks em renovacao por assinatura.

## Fora do MVP

- Dunning avancado.
- Proration.
- Troca de plano/item no meio do ciclo.
- Dashboard admin de recorrencia.
- Criacao manual de assinatura pelo dashboard.
- Retries inteligentes de pagamento.
- Uso completo dos status `draft` e `void`.

## Riscos Conhecidos

- `ChargeService` ainda e o ponto central de disponibilidade para criacao de cobrancas; falhas nele colocam assinatura em `past_due`.
- Scheduler precisa estar ativo em producao (`schedule:run` via cron/supervisor).
- `onOneServer` depende de cache driver compartilhado em ambiente multi-node.
- Webhook dispatcher atual entrega de forma sincrona; em alto volume pode ser desejavel migrar lifecycle webhooks para fila dedicada.

## Comandos de Producao

Rodar migrations:

```bash
php artisan migrate --force
```

Garantir scheduler:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Executar manualmente, se necessario:

```bash
php artisan subscriptions:renew
```

## Rotas API

```text
POST /api/v1/customer-subscriptions
GET  /api/v1/customer-subscriptions
GET  /api/v1/customer-subscriptions/{id}
POST /api/v1/customer-subscriptions/{id}/cancel
```

## Rotas Dashboard

```text
GET  /user/assinaturas
GET  /user/assinaturas/{id}
POST /user/assinaturas/{id}/cancel
```

## Eventos Webhook

- `customer_subscription.created`
- `customer_subscription.activated`
- `customer_subscription.canceled`
- `customer_subscription.past_due`
- `subscription_invoice.created`
- `subscription_invoice.paid`
- `subscription_invoice.failed`

## Verificacoes

- Nao usa tabela `subscriptions` antiga.
- Nao usa billing SaaS antigo.
- Nao altera `ChargeService` diretamente.
- Nao duplica charges em renewal repetido.
- Invoice nao vira `paid` sem pagamento confirmado.
- Assinatura nao vira `active` sem pagamento confirmado.
- Renewal nao gera invoice/charge duplicada no mesmo ciclo.
- Webhooks usam idempotencia por endpoint.
- Dashboard lista apenas dados do merchant autenticado.

## Resultado dos Testes

Validacoes executadas no fechamento:

- `CustomerSubscription*`: 34 testes, 158 assertions, verde.
- `ChargeServicePlatformFeeTest`: 5 testes, 18 assertions, verde.
- `GatewayWebhookValidationTest`: 6 testes, 12 assertions, verde.
- `WalletBalanceRuntimeTest`: 5 testes, 18 assertions, verde.
- `ChargePaidEventListenerTest`: 1 teste, 3 assertions, verde.

## Scheduler Confirmado

`php artisan schedule:list` mostra:

```text
0 * * * *  php artisan subscriptions:renew
```

Configurado com:

- `hourly`
- `withoutOverlapping`
- `onOneServer`

## Certificacao Final

Com base nos testes focados e na auditoria de implementacao, Customer Subscriptions esta fechado para MVP e apto para uso controlado em producao, desde que o scheduler Laravel esteja operacional no ambiente.
