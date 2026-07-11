param(
    [Parameter(Mandatory=$true)]
    [string]$Script,
    [Parameter(Mandatory=$false)]
    [string]$Profile = "quick",
    [Parameter(Mandatory=$false)]
    [string]$Release = "baseline"
)

$DateStr = Get-Date -Format "yyyy-MM-dd_HHmmss"
$BaseName = [System.IO.Path]::GetFileNameWithoutExtension($Script)
$OutputDir = "tests\k6\results\$Release"

if (-Not (Test-Path -Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$JsonOutput = "$OutputDir\${BaseName}_${Profile}_${DateStr}.json"
$MdOutput = "$OutputDir\${BaseName}_${Profile}_${DateStr}.md"

# 1. Metadados de Execução (Git e Host)
$CommitHash = (git rev-parse --short HEAD) 2>$null
if (-not $CommitHash) { $CommitHash = "unknown" }
$Branch = (git rev-parse --abbrev-ref HEAD) 2>$null
if (-not $Branch) { $Branch = "unknown" }
$EnvName = $env:APP_ENV
if (-not $EnvName) { $EnvName = "local" }
$HostName = $env:COMPUTERNAME
$StartTime = Get-Date

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host " Iniciando Runner V2 (Certificação DigiSynk) " -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host " Script: $Script"
Write-Host " Profile: $Profile | Release: $Release"
Write-Host " Commit: $CommitHash ($Branch) | Env: $EnvName"
Write-Host " Início: $StartTime"

# Coleta Pré-Teste (CPU/RAM Baseline Host)
$PreCpu = (Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average
$PreRam = (Get-CimInstance Win32_OperatingSystem).FreePhysicalMemory / 1024

$env:PROFILE = $Profile
k6 run --summary-export=$JsonOutput $Script
$ExitCode = $LASTEXITCODE
$EndTime = Get-Date

# Coleta Pós-Teste (CPU/RAM Host)
$PostCpu = (Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average
$PostRam = (Get-CimInstance Win32_OperatingSystem).FreePhysicalMemory / 1024

if ($ExitCode -eq 0 -and (Test-Path $JsonOutput)) {
    Write-Host "`n[1/3] K6 concluído. Avaliando Regressão via baseline.json..." -ForegroundColor Cyan
    
    $Report = Get-Content $JsonOutput | ConvertFrom-Json
    $Reqs = $Report.metrics.http_reqs.values.count
    $TPS = [math]::Round($Report.metrics.http_reqs.values.rate, 2)
    $P50 = [math]::Round($Report.metrics.http_req_duration.values."p(50)", 2)
    $P95 = [math]::Round($Report.metrics.http_req_duration.values."p(95)", 2)
    $P99 = [math]::Round($Report.metrics.http_req_duration.values."p(99)", 2)
    $Errors = if ($Report.metrics.http_req_failed) { $Report.metrics.http_req_failed.values.passes } else { 0 }

    # Análise de Baseline
    $RegressionMsg = "Nenhuma baseline encontrada para $BaseName."
    $IsRegression = $false
    if (Test-Path "tests\k6\baseline.json") {
        $BaselineData = Get-Content "tests\k6\baseline.json" | ConvertFrom-Json
        if ($BaselineData.$BaseName) {
            $Target = $BaselineData.$BaseName
            $MinTps = $Target.expected_tps_min * (1 - ($Target.tolerances.tps_drop_max_pct / 100))
            $MaxP95 = $Target.expected_p95_max * (1 + ($Target.tolerances.p95_increase_max_pct / 100))
            $MaxP99 = $Target.expected_p99_max * (1 + ($Target.tolerances.p99_increase_max_pct / 100))

            if ($TPS -lt $MinTps) { $RegressionMsg += " TPS abaixo do tolerado ($TPS < $MinTps)."; $IsRegression = $true }
            if ($P95 -gt $MaxP95) { $RegressionMsg += " P95 acima do tolerado ($P95 > $MaxP95)."; $IsRegression = $true }
            if ($P99 -gt $MaxP99) { $RegressionMsg += " P99 acima do tolerado ($P99 > $MaxP99)."; $IsRegression = $true }
            
            if (-not $IsRegression) { $RegressionMsg = "Performance DENTRO dos limites da Baseline oficial." }
        }
    }

    if ($IsRegression) {
        Write-Host "ALERTA: Regressão de Performance Detectada!" -ForegroundColor Yellow
        Write-Host $RegressionMsg -ForegroundColor Yellow
    }

    Write-Host "`n[2/3] Executando Auditoria Financeira..." -ForegroundColor Cyan
    
    $ReconcileOut = php artisan finance:reconcile 2>&1
    $ReconcileExit = $LASTEXITCODE
    Write-Host $ReconcileOut

    $IntegrityOut = php artisan ledger:verify-integrity 2>&1
    $IntegrityExit = $LASTEXITCODE
    Write-Host $IntegrityOut

    $DryRunOut = php artisan wallet:rebuild-balances --dry-run 2>&1
    $DryRunExit = $LASTEXITCODE
    Write-Host $DryRunOut

    if ($ReconcileExit -ne 0 -or $IntegrityExit -ne 0 -or $DryRunExit -ne 0) {
        Write-Host "FALHA CRÍTICA: Divergência financeira encontrada após o teste de estresse!" -ForegroundColor Red
        exit 1
    }

    Write-Host "`n[3/3] Executando Smoke Test Final..." -ForegroundColor Cyan
    $HealthCheck = Invoke-WebRequest -Uri "http://localhost:8000/up" -SkipHttpErrorCheck
    if ($HealthCheck.StatusCode -ge 400) {
        Write-Host "FALHA CRÍTICA: API Health Check falhou!" -ForegroundColor Red
        exit 1
    }

    # Geração do Relatório Markdown
    $MdContent = @"
# K6 Performance Validation Report

## 1. Metadados
- **Script:** $BaseName
- **Release:** $Release
- **Perfil:** $Profile
- **Commit:** $CommitHash ($Branch)
- **Ambiente:** $EnvName
- **Host:** $HostName
- **Início:** $StartTime
- **Término:** $EndTime
- **Regressão Detectada:** $($IsRegression.ToString().ToUpper())

## 2. Métricas HTTP (K6)
- **Total Requests:** $Reqs
- **TPS:** $TPS req/s
- **P50:** ${P50}ms
- **P95:** ${P95}ms
- **P99:** ${P99}ms
- **Erros Totais:** $Errors
- **Avaliação de Baseline:** $RegressionMsg

## 3. Infraestrutura (Host Baseline)
- **CPU Média Inicial:** ${PreCpu}%
- **CPU Média Final:** ${PostCpu}%
- *(Nota: Para métricas granulares de Redis/Postgres/Horizon, consulte os logs do Datadog associados ao intervalo de tempo).*

## 4. Evidência Financeira
- **Reconciliação (finance:reconcile):** SUCESSO (Exit 0)
- **Integridade (ledger:verify-integrity):** SUCESSO (Exit 0)
- **Dry-Run (wallet:rebuild-balances):** SUCESSO (Exit 0)
- **Health Check (/up):** 200 OK

---
*Relatório gerado automaticamente por DigiSynk Runner V2.*
"@
    Set-Content -Path $MdOutput -Value $MdContent
    Write-Host "`nRelatório Consolidado salvo em: $MdOutput" -ForegroundColor Green

} else {
    Write-Host "K6 Test FAILED thresholds or crashed!" -ForegroundColor Red
    exit $ExitCode
}
