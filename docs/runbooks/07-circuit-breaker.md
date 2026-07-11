# Runbook 07: Circuit Breaker

## Sintomas
- Alarme dispara indicando que o Circuit Breaker entrou em estado OPEN (OFFLINE).

## Diagnóstico
- O Provider "X" respondeu com 5xx/429 mais vezes do que o limite permitido no Cache em 1 minuto.

## Mitigação
- O sistema já lidou sozinho abrindo o disjuntor. Apenas os requests de saída (Withdraws) para aquele provider específico estão bloqueados. Os webhooks de Inbound continuam normais.

## Rollback
- Aguardar o Half-Open state (após alguns minutos o sistema tenta enviar 1 requisição de teste).
- Se a requisição de teste der sucesso, ele fecha o disjuntor sozinho.
- Se houver necessidade de Forçar, limpar a chave no cache: `Cache::forget('circuit_breaker_offline_PROVIDER')` ou usar o comando `php artisan emergency:disable kill_switch:provider`.

## Escalação
- Não é necessário escalar a menos que dure mais de 15 minutos (Nesse caso investigar falha severa na API Parceira).
