# Chaos Injector: Playbook A - Queda do Banco Primário (PostgreSQL)

Write-Host "⚠️  INICIANDO CHAOS INJECTION: Queda do Banco Primário" -ForegroundColor Yellow
Write-Host "O container do PostgreSQL ('digisynk-db-1' ou similar) será forçado a parar."

# Tenta parar via docker
$DbContainer = docker ps -qf "name=db"
if ($DbContainer) {
    Write-Host "Container DB Encontrado: $DbContainer. Executando kill..." -ForegroundColor Cyan
    docker kill $DbContainer
    Write-Host "DB Morto. Acompanhe a taxa de erro no K6 (deve ir para 100% de falha 500/503)." -ForegroundColor Yellow
    
    # Simula tempo de downtime antes do failover
    Start-Sleep -Seconds 30
    
    Write-Host "Iniciando processo de Failover / Restart do Nó..." -ForegroundColor Cyan
    docker start $DbContainer
    Write-Host "DB Reiniciado. Verifique a recuperação da API e ausência de Ledger corrompido!" -ForegroundColor Green
} else {
    Write-Host "Nenhum container Docker com nome 'db' encontrado. Caso esteja rodando RDS nativo, use AWS CLI para reboot: 'aws rds reboot-db-instance --db-instance-identifier digisynk-staging'" -ForegroundColor Red
}
