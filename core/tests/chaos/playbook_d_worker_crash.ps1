# Chaos Injector: Playbook D - Worker Crash (OOM/SIGKILL)

Write-Host "⚠️  INICIANDO CHAOS INJECTION: Assassinato Repentino de Workers (Horizon/Queue)" -ForegroundColor Yellow
Write-Host "Localizando processos do Laravel Queue Worker ou Horizon..."

# Caça os PIDs de processos PHP rodando 'queue:work' ou 'horizon'
$PIDs = Get-WmiObject Win32_Process -Filter "Name='php.exe' AND CommandLine LIKE '%queue:work%'" | Select-Object -ExpandProperty ProcessId
$PIDs += Get-WmiObject Win32_Process -Filter "Name='php.exe' AND CommandLine LIKE '%horizon%'" | Select-Object -ExpandProperty ProcessId

if ($PIDs) {
    foreach ($pid in $PIDs) {
        Write-Host "Matando processo PHP Worker (PID: $pid) com força bruta..." -ForegroundColor Cyan
        Stop-Process -Id $pid -Force
    }
    Write-Host "Workers mortos! Se houver transações em voo, elas devem falhar sem commitar pela metade." -ForegroundColor Yellow
    Write-Host "O Supervisor (se configurado) ou o próprio Horizon Master deverá reerguer os workers automaticamente em breve." -ForegroundColor Green
} else {
    Write-Host "Nenhum worker local encontrado. Caso esteja rodando Docker, o comando executado seria: docker kill digisynk-worker-1" -ForegroundColor Yellow
    $WorkerContainer = docker ps -qf "name=worker"
    if ($WorkerContainer) {
        docker kill $WorkerContainer
        Write-Host "Worker Docker Morto!" -ForegroundColor Green
    }
}
