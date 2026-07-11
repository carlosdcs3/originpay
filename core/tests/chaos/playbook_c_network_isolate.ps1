# Chaos Injector: Playbook C - PSP Timeout (Network Isolation)

Write-Host "⚠️  INICIANDO CHAOS INJECTION: Isolamento de Rede do Gateway PSP" -ForegroundColor Yellow
Write-Host "Injetando IP de Blackhole (10.255.255.1) no .env para forçar Timeout Externo..."

$EnvFile = ".env"
if (Test-Path $EnvFile) {
    # Backup
    Copy-Item $EnvFile "$EnvFile.chaos_backup"
    
    # Substitui a URL do Gateway principal para um buraco negro (IP não roteável que causará timeout)
    (Get-Content $EnvFile) -replace 'GATEWAY_BASE_URL=.*', 'GATEWAY_BASE_URL=http://10.255.255.1:8080' | Set-Content $EnvFile
    
    Write-Host "Timeout Injetado! Limpando cache do config..." -ForegroundColor Cyan
    php artisan config:clear
    
    Write-Host "Aguardando 60 segundos com o sistema tentando se comunicar com o Gateway (Testando Circuit Breaker e Rolling Reserve)..." -ForegroundColor Yellow
    Start-Sleep -Seconds 60
    
    Write-Host "Restaurando configurações de rede..." -ForegroundColor Cyan
    Copy-Item "$EnvFile.chaos_backup" $EnvFile -Force
    Remove-Item "$EnvFile.chaos_backup"
    php artisan config:clear
    
    Write-Host "Rede Restaurada. Verifique se os Workers entraram em Fallback corretamente e se o saldo de saques não sumiu!" -ForegroundColor Green
} else {
    Write-Host "Arquivo .env não encontrado no diretório atual." -ForegroundColor Red
}
