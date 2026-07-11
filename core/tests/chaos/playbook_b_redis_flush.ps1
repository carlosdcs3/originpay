# Chaos Injector: Playbook B - Perda de Redis (Evaporação do Cache e Locks)

Write-Host "⚠️  INICIANDO CHAOS INJECTION: FLUSHALL no Redis" -ForegroundColor Yellow
Write-Host "ATENÇÃO: Este comando limpará todo o banco do Redis atual. NUNCA execute em Redis compartilhado!" -ForegroundColor Red

# Tenta expurgar via docker
$RedisContainer = docker ps -qf "name=redis"
if ($RedisContainer) {
    Write-Host "Container Redis Encontrado: $RedisContainer. Executando FLUSHALL..." -ForegroundColor Cyan
    docker exec $RedisContainer redis-cli FLUSHALL
    Write-Host "Redis Expurgado! Observe se webhooks concorrentes causam duplicidade (devem ser bloqueados pelo PostgreSQL ProcessedEvent)." -ForegroundColor Yellow
} else {
    Write-Host "Nenhum container Docker com nome 'redis' encontrado. Executando via redis-cli local..." -ForegroundColor Yellow
    redis-cli FLUSHALL
    if ($LASTEXITCODE -ne 0) {
        Write-Host "redis-cli falhou. Certifique-se de estar apontando para o Redis correto." -ForegroundColor Red
    } else {
        Write-Host "Redis Expurgado localmente!" -ForegroundColor Green
    }
}
