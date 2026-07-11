# 00 — Arquitetura de Produção da OriginPay

Este diretório contém especificações obrigatórias de produção.

## Arquitetura oficial de gateways

A arquitetura definitiva para PSPs/gateways está em:

- `docs/production/09-gateway-architecture.md`

Decisão oficial:

- `app/Gateway` é a autoridade do projeto para novos gateways, métodos de pagamento e integrações PSP.
- `app/Payment` e `app/Payment/Modern` são legado/migração.
- Nenhum novo PSP deve ser implementado fora da arquitetura definida em `09-gateway-architecture.md`.

## Referências de auditoria

- `docs/audit/R2.5-gateway-architecture.md`
- `docs/audit/R2-webhooks-dlq-idempotency.md`

## Regras permanentes

1. Provider não altera saldo diretamente.
2. Webhook inválido não altera estado financeiro.
3. Evento financeiro precisa de idempotência e correlation id.
4. Novas integrações devem passar por contratos, DTOs, pipeline HTTP, webhook, settlement, ledger, observabilidade e testes definidos em `09-gateway-architecture.md`.
