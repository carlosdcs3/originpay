<?php

namespace App\Services\Fraud;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class FraudEngineService
{
    const THRESHOLD_BLOCK = 80;

    /**
     * Calcula o risco da transacao.
     * Retorna um score de 0 a 100.
     */
    public function evaluateRisk(array $payload, string $ip, int $userId): array
    {
        $score = 0;
        $reasons = [];

        // 1. Frequencia anormal por CPF/Documento (mesmo documento em < 10min)
        if (isset($payload['customer_document'])) {
            $docKey = 'fraud:doc:' . md5($payload['customer_document']);
            $docCount = $this->incrementWithTtl($docKey, 600);

            if ($docCount > 3) {
                $score += 30;
                $reasons[] = 'Alta frequencia de uso do documento.';
            }
        }

        // 2. Frequencia anormal por IP
        $ipKey = 'fraud:ip:' . $ip;
        $ipCount = $this->incrementWithTtl($ipKey, 600);

        if ($ipCount > 10) {
            $score += 40;
            $reasons[] = 'Alta frequencia de tentativas no mesmo IP.';
        }

        // 3. Merchant com risco elevado ou usuario recem criado.
        $cbKey = "fraud:user:{$userId}:chargebacks";
        $cbCount = $this->getCount($cbKey);

        if ($cbCount > 2) {
            $score += 50;
            $reasons[] = 'Lojista possui historico recente de chargeback elevado.';
        }

        return [
            'score' => min($score, 100),
            'reasons' => $reasons,
            'is_blocked' => $score >= self::THRESHOLD_BLOCK,
        ];
    }

    public function recordChargeback(int $userId): void
    {
        $cbKey = "fraud:user:{$userId}:chargebacks";
        $this->incrementWithTtl($cbKey, 86400 * 7);
    }

    private function incrementWithTtl(string $key, int $ttlSeconds): int
    {
        try {
            $count = (int) Redis::incr($key);

            if ($count === 1) {
                Redis::expire($key, $ttlSeconds);
            }

            return $count;
        } catch (Throwable $exception) {
            Log::warning('Fraud Redis counter unavailable; continuing without distributed counter.', [
                'key' => $key,
                'reason' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    private function getCount(string $key): int
    {
        try {
            return (int) Redis::get($key);
        } catch (Throwable $exception) {
            Log::warning('Fraud Redis lookup unavailable; continuing without historical counter.', [
                'key' => $key,
                'reason' => $exception->getMessage(),
            ]);

            return 0;
        }
    }
}
