# Runbook 01: Gateway Offline

## Sintomas
- Erros de timeout (504, 502) no Gateway Metrics.
- Circuit Breaker acionado para um provedor.
- Fila de falhas no Sentry alertando `ConnectionException`.

## Diagnóstico
- Checar /admin/system/health para verificar se o Gateway específico está reportando erro.
- Executar `php artisan emergency:status`.

## Mitigação
1. Habilitar o Kill Switch do Gateway afetado: `php artisan emergency:enable kill_switch:new_provider`.
2. Habilitar o modo de Manutenção Financeira para impedir que clientes iniciem novos saques que fiquem travados.

## Rollback
- Após a volta do provedor, desativar Kill Switch: `php artisan emergency:disable kill_switch:new_provider`.
- Reprocessar webhooks da DLQ do período da queda: `php artisan emergency:replay --provider=NEW_PROVIDER --time_range="last 2 hours"`.

## Escalação
- Eng. de Confiabilidade (SRE)
- Suporte L3 do Gateway Afetado.
