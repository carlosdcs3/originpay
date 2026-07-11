# Customer Subscriptions MVP

## Escopo

Customer Subscriptions e o dominio merchant-facing de recorrencia da OriginPay.
Ele nao usa o billing SaaS antigo, nao usa a tabela `subscriptions` antiga e nao altera `ChargeService`.

## Tabelas

- `customer_subscriptions`
- `customer_subscription_items`
- `subscription_invoices`
- `subscription_invoice_items`

## API

Todas as rotas usam autenticacao da API v1 existente.

### Criar assinatura

`POST /api/v1/customer-subscriptions`

Header recomendado:

```http
Idempotency-Key: sub_merchant_order_123
Authorization: Bearer sk_live_xxx
```

Payload:

```json
{
  "customer": {
    "name": "Cliente Recorrente",
    "email": "cliente@example.com",
    "document": "12345678909"
  },
  "amount": 100.00,
  "currency": "BRL",
  "payment_method": "pix",
  "interval": "month",
  "interval_count": 1,
  "description": "Assinatura mensal",
  "start_at": "2026-01-31T10:00:00-03:00",
  "metadata": {
    "external_reference": "sub_ext_123"
  }
}
```

Comportamento:

- cria `customer_subscription`
- cria primeira `subscription_invoice` como `open`
- cria primeira `Charge` via `ChargeService`
- vincula `subscription_invoice.charge_id`
- assinatura fica `pending` ate pagamento confirmado

### Listar

`GET /api/v1/customer-subscriptions`

Retorna apenas assinaturas do merchant autenticado.

### Consultar

`GET /api/v1/customer-subscriptions/{id}`

`id` pode ser UUID publico da assinatura.

### Cancelar

`POST /api/v1/customer-subscriptions/{id}/cancel`

Payload:

```json
{
  "cancel_at_period_end": true
}
```

Se `cancel_at_period_end` for `false` ou omitido, cancela imediatamente.

## Status

### Subscription

- `pending`: assinatura criada, aguardando pagamento confirmado
- `active`: pagamento confirmado e ciclo ativo
- `past_due`: assinatura ativa teve falha em renovacao/cobranca
- `canceled`: cancelada
- `incomplete`: primeira cobranca falhou ou expirou antes da ativacao

### Invoice

- `draft`: reservado para fases futuras
- `open`: invoice criada aguardando pagamento
- `paid`: pagamento confirmado via evento de charge paga
- `failed`: cobranca falhou, expirou, foi cancelada ou reembolsada antes do pagamento
- `void`: reservado para fases futuras

## Renovacao

Command:

```bash
php artisan subscriptions:renew
```

Scheduler:

```text
hourly, withoutOverlapping, onOneServer
```

Regra:

- processa somente `active` e `past_due`
- exige `next_billing_at <= now()`
- ignora `canceled`
- se `cancel_at_period_end` estiver ativo e o periodo chegou ao fim, cancela sem gerar nova invoice
- usa lock de banco por assinatura
- usa idempotencia por assinatura + inicio do periodo
- toda nova cobranca passa por `ChargeService`

## Webhooks Emitidos

- `customer_subscription.created`
- `customer_subscription.activated`
- `customer_subscription.canceled`
- `customer_subscription.past_due`
- `subscription_invoice.created`
- `subscription_invoice.paid`
- `subscription_invoice.failed`

Payload base:

```json
{
  "id": "evt_xxx",
  "type": "subscription_invoice.paid",
  "created": "2026-06-28T10:00:00-03:00",
  "environment": "live",
  "data": {
    "subscription": {
      "id": "uuid",
      "status": "active",
      "amount": 100.0,
      "currency": "BRL",
      "payment_method": "pix",
      "interval": "month",
      "interval_count": 1,
      "current_period_start": "2026-06-01T00:00:00-03:00",
      "current_period_end": "2026-07-01T00:00:00-03:00",
      "next_billing_at": "2026-07-01T00:00:00-03:00",
      "metadata": {}
    },
    "invoice": {
      "id": "uuid",
      "status": "paid",
      "amount_due": 100.0,
      "amount_paid": 100.0,
      "currency": "BRL",
      "period_start": "2026-06-01T00:00:00-03:00",
      "period_end": "2026-07-01T00:00:00-03:00",
      "metadata": {}
    },
    "charge": {
      "id": "uuid",
      "status": "paid",
      "amount": 100.0,
      "currency": "BRL"
    },
    "customer": {
      "name": "Cliente Recorrente",
      "email": "cliente@example.com",
      "document": "12345678909"
    }
  }
}
```

Webhook delivery usa idempotencia por endpoint e evento de dominio.

## Dashboard do Merchant

Rota:

```text
GET /user/assinaturas
GET /user/assinaturas/{id}
POST /user/assinaturas/{id}/cancel
```

O dashboard mostra apenas dados do merchant autenticado.

## Fora do MVP

- dunning avancado
- retries inteligentes por tentativa de pagamento
- proration
- alteracao de plano/item no meio do ciclo
- dashboard administrativo
- criacao de assinatura via tela web
- webhooks de teste especificos para subscriptions
- suporte completo a `void`
- multiplos itens com precificacao variavel via API publica
