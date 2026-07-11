# Chaos Injector: Playbook E - Storage/S3 Indisponível

Write-Host "⚠️  INICIANDO CHAOS INJECTION: Indisponibilidade de Storage (S3 / Local)" -ForegroundColor Yellow
Write-Host "Quebrando as credenciais AWS_ S3 e mudando as permissões do storage local..."

$EnvFile = ".env"
if (Test-Path $EnvFile) {
    # Backup
    Copy-Item $EnvFile "$EnvFile.chaos_backup_s3"
    
    # Substitui variáveis vitais do S3
    (Get-Content $EnvFile) -replace 'AWS_ACCESS_KEY_ID=.*', 'AWS_ACCESS_KEY_ID=CHAOS_FAKE_KEY' | Set-Content $EnvFile
    (Get-Content $EnvFile) -replace 'AWS_BUCKET=.*', 'AWS_BUCKET=non-existent-chaos-bucket' | Set-Content $EnvFile
    
    Write-Host "AWS S3 Sabotado! Limpando cache do config..." -ForegroundColor Cyan
    php artisan config:clear
    
    # Renomeia pasta public local para simular falha de disco em upload KYC local
    if (Test-Path "storage/app/public") {
        Rename-Item "storage/app/public" "storage/app/public_chaos_broken"
    }

    Write-Host "Aguardando 30 segundos (Tente fazer upload de KYC ou exportar relatórios pela API)..." -ForegroundColor Yellow
    Start-Sleep -Seconds 30
    
    Write-Host "Restaurando configurações de Storage..." -ForegroundColor Cyan
    Copy-Item "$EnvFile.chaos_backup_s3" $EnvFile -Force
    Remove-Item "$EnvFile.chaos_backup_s3"
    php artisan config:clear
    
    if (Test-Path "storage/app/public_chaos_broken") {
        Rename-Item "storage/app/public_chaos_broken" "public"
    }
    
    Write-Host "Storage Restaurado. Verifique se o núcleo financeiro ignorou essa queda e continuou processando saldos!" -ForegroundColor Green
} else {
    Write-Host "Arquivo .env não encontrado no diretório atual." -ForegroundColor Red
}
