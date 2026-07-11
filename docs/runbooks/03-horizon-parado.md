# Runbook 03: Horizon Parado

## Sintomas
- Painel `/admin/system/health` mostra Horizon PAUSED ou OFFLINE.
- Jobs da fila High se acumulando além do normal (> 500).
- Webhooks chegam mas saldos não são creditados.

## Diagnóstico
- Verificar o log do Horizon: `tail -f storage/logs/horizon.log`.
- Verificar o Supervisor: `supervisorctl status horizon`.

## Mitigação
1. Tentar graceful restart: `php artisan horizon:terminate`.
2. Se o Supervisor estiver morto, reiniciar Supervisor: `systemctl restart supervisor`.
3. Se a fila crescer muito rápido e causar Out Of Memory, pausar o Gateway (Kill Switch de Webhook) temporariamente.

## Rollback
- Esperar a fila descer naturalmente. Monitorar métricas.
- Rodar `php artisan reconcile:transactions`.

## Escalação
- Equipe de Operações Backend
