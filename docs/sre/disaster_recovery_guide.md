# Guia Oficial de Disaster Recovery (DigiSynk SRE)

Este guia deve ser utilizado em cenários de **Perda Total (Disaster Recovery)** ou durante simulados operacionais (Game Days) de restauração do banco de dados relacional.

## Premissas de um Evento DR
Se o banco de dados principal (Master e Replica) falhar catastroficamente e corromper os dados em disco, a única saída segura que mantém o **Ledger Financeiro Consistente** é o Restore Point-In-Time (Snapshot).

### Passo 1: Bloqueio do Tráfego (Kill Switch)
Imediatamente corte o tráfego da API para evitar Split Brain ou processamentos paralelos sujos em caches que sobreviveram:
1. Derrube o Horizon/Workers: `php artisan horizon:terminate`
2. No Load Balancer / API Gateway, ative a página de Manutenção (503 Service Unavailable). `php artisan down`

### Passo 2: Subida do Novo Cluster (RDS Restore)
1. No console AWS, localize o último Snapshot automatizado.
2. Inicie a restauração gerando um novo Endpoint.
3. Atualize os `.env` (ou Secrets Manager) com o novo Host.

### Passo 3: Limpeza de Estado Transiente (Redis)
Como o banco de dados voltou no tempo (ex: 5 minutos atrás), o Redis pode conter chaves `idempotency:` ou `wallet_locks:` de eventos que *não estão mais no banco de dados*.
**AÇÃO OBRIGATÓRIA:**
- Rode `redis-cli FLUSHALL` (Somente no DB 0/Cache da Aplicação). Se você não limpar o Redis, o sistema rejeitará transações como "duplicadas" mesmo sem elas existirem no novo banco.

### Passo 4: Auditoria Criptográfica (O MOMENTO DA VERDADE)
Antes de religar o Load Balancer e liberar acessos, a equipe deve confirmar matematicamente que o Snapshot subiu intacto e que as carteiras não foram fraudadas.

```bash
# 1. Valida se a fita magnética bate com a assinatura SHA256
php artisan ledger:verify-integrity

# 2. Varre o banco cruzando Entradas vs PSPs (se houver divergência, acione Conciliação Manual)
php artisan finance:reconcile

# 3. Faz uma prova real dos saldos em memória vs saldos comitados no Ledger
php artisan wallet:rebuild-balances --dry-run
```

Se **qualquer um** dos comandos acima falhar ou retornar _Exit Code > 0_:
- **NÃO SUBA A API.**
- Acione Nível Crítico e inicie investigação no banco, pois o Snapshot pode estar corrompido.

### Passo 5: Religamento Controlado
Se o Passo 4 for um Sucesso (Tudo Verde):
1. Ligue o Horizon: `php artisan horizon`
2. Desligue a manutenção da API: `php artisan up`
3. Acompanhe os gráficos de `Retries` para webhooks atrasados. A Idempotência nativa lidará com o tráfego de reentrada de forma segura.
