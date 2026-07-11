<?php

namespace App\Services;

use App\Models\PlatformIncident;
use App\Models\PlatformAlert;

class IncidentManagerService
{
    /**
     * Abre um novo incidente e opcionalmente associa a alertas existentes.
     */
    public function openIncident(string $title, string $severity, ?int $createdBy = null): PlatformIncident
    {
        return PlatformIncident::create([
            'title' => $title,
            'severity' => $severity,
            'status' => PlatformIncident::STATUS_OPEN,
            'started_at' => now(),
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    /**
     * Atualiza o status do incidente.
     */
    public function updateStatus(int $incidentId, string $status): bool
    {
        $incident = PlatformIncident::find($incidentId);
        if (!$incident) return false;
        
        $incident->status = $status;
        $incident->save();
        
        return true;
    }

    /**
     * Marca o incidente como resolvido, registrando a causa raiz e resolução.
     */
    public function resolveIncident(int $incidentId, string $rootCause, string $resolution, ?int $resolvedBy = null): bool
    {
        $incident = PlatformIncident::find($incidentId);
        if (!$incident || $incident->status === PlatformIncident::STATUS_RESOLVED) {
            return false;
        }

        $resolvedAt = now();
        $durationMs = $incident->started_at ? $resolvedAt->diffInMilliseconds($incident->started_at) : null;

        $incident->update([
            'status' => PlatformIncident::STATUS_RESOLVED,
            'resolved_at' => $resolvedAt,
            'duration_ms' => $durationMs,
            'root_cause' => $rootCause,
            'resolution' => $resolution,
            'resolved_by' => $resolvedBy ?? auth()->id(),
        ]);

        return true;
    }
    
    /**
     * Obtém os incidentes ativos (não resolvidos).
     */
    public function getActiveIncidents()
    {
        return PlatformIncident::where('status', '!=', PlatformIncident::STATUS_RESOLVED)
            ->orderBy('started_at', 'desc')
            ->get();
    }
}
