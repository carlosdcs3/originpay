# Runbook 02: Redis Indisponível

## Sintomas
- A aplicação inteira pode estar retornando lentidão ou 500 no login se as sessões estiverem no Redis.
- Fila `high` não processa mais nada (Horizon offline).
- Métricas zeradas.

## Diagnóstico
- Acessar o servidor via SSH: `redis-cli ping` (deve retornar PONG).
- Analisar logs do sistema em `storage/logs/laravel.log` para `RedisException`.

## Mitigação
1. Se o Redis travou por OOM (Out Of Memory), reiniciar o serviço `systemctl restart redis` ou escalar servidor.
2. Ativar `EmergencyReadOnlyMode` via `.env` ou MySQL local caso não haja tempo hábil, mitigando impacto.

## Rollback
- Após restabelecimento, reiniciar Supervisor e Horizon: `php artisan horizon:terminate`.
- Confirmar integridade rodando `php artisan reconcile:webhooks`.

## Escalação
- Infraestrutura/DBA
- Suporte Cloud (AWS/DigitalOcean)
