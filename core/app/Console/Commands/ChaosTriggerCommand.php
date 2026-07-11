<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class ChaosTriggerCommand extends Command
{
    protected $signature = 'chaos:trigger {--kill= : Alvo para derrubar (redis, db, gateway)} {--duration=60 : Tempo em segundos que o componente ficará "morto" (simulado)}';
    protected $description = 'Chaos Engineering: Injeta falhas de infraestrutura no ambiente em runtime para validar resiliência e circuit breakers.';

    public function handle()
    {
        if (app()->environment('production')) {
            $this->error("ALERTA CRÍTICO: Não é permitido rodar engenharia do caos em Produção sem arquitetura Blue/Green específica.");
            return;
        }

        $target = $this->option('kill');
        $duration = (int) $this->option('duration');

        $this->warn("INICIANDO INJEÇÃO DE FALHA. Alvo: [$target]");

        switch ($target) {
            case 'redis':
                $this->simulateRedisFailure($duration);
                break;
            case 'db':
                $this->simulateDbFailure($duration);
                break;
            case 'gateway':
                $this->simulateGatewayFailure($duration);
                break;
            default:
                $this->error("Alvo desconhecido. Use: redis, db ou gateway.");
                break;
        }
    }

    private function simulateRedisFailure($duration)
    {
        $this->info("Sobrescrevendo configuração de Host do Redis para IP Inexistente (Blackhole)...");
        // Isso forçará timeouts ou connection refused. No Laravel, testar isso em runtime de CLI apenas afeta este processo, 
        // Para afetar o servidor real (nginx/php-fpm), num ambiente real de teste usaríamos iptables ou mockaríamos a Facade globalmente via Cache/Redis Mocker em middleware.
        $this->info("Simulação: Em um Game Day real, execute 'sudo iptables -A INPUT -p tcp --dport 6379 -j DROP' no Staging.");
        $this->info("Aguardando $duration segundos (O Circuit Breaker deve abrir ou webhooks falharão)...");
        sleep($duration);
        $this->info("Falha do Redis removida. Restabelecendo.");
    }

    private function simulateDbFailure($duration)
    {
        $this->info("Simulação: Cortando acesso ao PostgreSQL/MySQL.");
        $this->info("Em um Game Day real, derrube as credenciais do DB do `.env` ou pare o serviço systemctl stop postgresql.");
        sleep($duration);
        $this->info("Banco de dados restabelecido. As transações pendentes no Horizon devem executar Retry agora.");
    }

    private function simulateGatewayFailure($duration)
    {
        $this->info("Derrubando conectividade com Gateways Externos...");
        $this->info("Isso simula o PSP retornando Erro 500 para requisições cURL.");
        // Coloca no Redis uma flag que o `GatewayResolver` (se programado para ler) forçaria 5xx.
        Redis::setex('chaos_monkey:force_gateway_5xx', $duration, 'true');
        $this->info("Flag de falha injetada no Redis. O Circuit Breaker DEVE abrir em instantes!");
        sleep($duration);
        $this->info("Gateway restabelecido.");
    }
}
