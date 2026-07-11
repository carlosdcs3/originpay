# Runbook 05: DLQ Crescente

## Sintomas
- Alarme disparado: DLQ Overflow (> 100 itens).
- Clientes reclamando que o pix já saiu do banco deles mas o saldo não caiu na plataforma.

## Diagnóstico
- Acessar `/admin/webhooks` e abrir a Aba DLQ.
- Observar a coluna `error_message`.
- Se o erro for generalizado (ex: Chave PIX da empresa revogada, ou timeout da nossa API contábil), parar imediatamente o processamento de novas chamadas para não inundar mais.

## Mitigação
1. Identificar o erro (Bug no código? Timeout externo?).
2. Corrigir o problema base.

## Rollback
- Selecionar o Batch de Webhooks na DLQ e acionar **Reprocessar Lote**. O `ReplayWebhookJob` executará cada um deles com 2 segundos de distância.

## Escalação
- Equipe de Suporte (L2) para comunicação aos clientes.
- Dev Backend.
