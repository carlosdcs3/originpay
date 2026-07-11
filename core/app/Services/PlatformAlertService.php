<?php

namespace App\Services;

use App\Models\PlatformAlert;

class PlatformAlertService
{
    /**
     * Registra um novo alerta na plataforma.
     */
    public function trigger(
        string $category,
        string $severity,
        string $title,
        ?string $description = null,
        ?string $source = null,
        ?string $recommendedAction = null,
        ?string $relatedLink = null
    ): PlatformAlert {
        return PlatformAlert::create([
            'category'           => $category,
            'severity'           => $severity,
            'source'             => $source,
            'title'              => $title,
            'description'        => $description,
            'recommended_action' => $recommendedAction,
            'related_link'       => $relatedLink,
            'status'             => PlatformAlert::STATUS_ACTIVE,
        ]);
    }

    /**
     * Atalho para criar alertas do tipo Info.
     */
    public function info(string $category, string $title, array $data = []): PlatformAlert
    {
        return $this->trigger(
            $category,
            PlatformAlert::SEVERITY_INFO,
            $title,
            $data['description'] ?? null,
            $data['source'] ?? null,
            $data['recommended_action'] ?? null,
            $data['related_link'] ?? null
        );
    }

    /**
     * Atalho para criar alertas do tipo Warning.
     */
    public function warning(string $category, string $title, array $data = []): PlatformAlert
    {
        return $this->trigger(
            $category,
            PlatformAlert::SEVERITY_WARNING,
            $title,
            $data['description'] ?? null,
            $data['source'] ?? null,
            $data['recommended_action'] ?? null,
            $data['related_link'] ?? null
        );
    }

    /**
     * Atalho para criar alertas do tipo Critical.
     */
    public function critical(string $category, string $title, array $data = []): PlatformAlert
    {
        return $this->trigger(
            $category,
            PlatformAlert::SEVERITY_CRITICAL,
            $title,
            $data['description'] ?? null,
            $data['source'] ?? null,
            $data['recommended_action'] ?? null,
            $data['related_link'] ?? null
        );
    }

    /**
     * Marca um alerta como resolvido.
     */
    public function resolve(int $alertId): bool
    {
        $alert = PlatformAlert::find($alertId);
        if ($alert && $alert->status !== PlatformAlert::STATUS_RESOLVED) {
            $alert->update([
                'status' => PlatformAlert::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Retorna os alertas ativos agrupados por severidade.
     */
    public function getActiveAlertsSummary(): array
    {
        $alerts = PlatformAlert::where('status', PlatformAlert::STATUS_ACTIVE)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return [
            'critical' => $alerts[PlatformAlert::SEVERITY_CRITICAL] ?? 0,
            'warning'  => $alerts[PlatformAlert::SEVERITY_WARNING] ?? 0,
            'info'     => $alerts[PlatformAlert::SEVERITY_INFO] ?? 0,
            'total'    => array_sum($alerts)
        ];
    }
    
    /**
     * Retorna os alertas ativos recentes (paginado).
     */
    public function getActiveAlerts(int $limit = 10)
    {
        return PlatformAlert::where('status', PlatformAlert::STATUS_ACTIVE)
            ->orderByRaw("CASE WHEN severity = 'Critical' THEN 1 WHEN severity = 'Warning' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
