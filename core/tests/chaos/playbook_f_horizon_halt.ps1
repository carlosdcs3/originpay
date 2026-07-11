# Chaos Injector: Playbook F - Horizon/Fila Travada

Write-Host "⚠️  INICIANDO CHAOS INJECTION: Pausa Forçada no Horizon (Fila Travada)" -ForegroundColor Yellow
Write-Host "Comandando o Horizon a pausar todos os processos (Simulando Deadlock de Master Supervisor)..."

php artisan horizon:pause

Write-Host "Horizon Pausado! Dispare carga do K6 agora." -ForegroundColor Cyan
Write-Host "As filas no Redis devem inchar violentamente, mas a API deve continuar devolvendo 200/201 (Webhooks e Charges enfileirados)." -ForegroundColor Yellow

Write-Host "Aguardando 45 segundos de acúmulo de fila..." -ForegroundColor Cyan
Start-Sleep -Seconds 45

Write-Host "Retomando Horizon (Despausando)..." -ForegroundColor Green
php artisan horizon:continue

Write-Host "Horizon Retomado! Acompanhe o Ops Dashboard. O processamento deve esvaziar o backlog sem duplicar pagamentos ou gerar Out of Memory." -ForegroundColor Green
