@echo off
echo ====================================================
echo        INICIANDO O SISTEMA DIGIKASH V1.0.5
echo ====================================================
echo.
echo Iniciando Servidor Web (PHP Artisan Serve)...
start "DigiKash - Web Server" cmd /k "cd core && php artisan serve --host=127.0.0.1 --port=8000"

echo Iniciando Workers (Processamento em Fila)...
start "DigiKash - Queue Worker" cmd /k "cd core && php artisan queue:work"

echo Iniciando Vite (Frontend Assets)...
start "DigiKash - Vite" cmd /k "cd core && npm run dev"

echo.
echo ====================================================
echo Os servicos estao sendo iniciados em novas janelas!
echo.
echo O sistema estara disponivel em:
echo http://127.0.0.1:8000
echo.
echo Painel do Horizon:
echo http://127.0.0.1:8000/horizon
echo ====================================================
pause
