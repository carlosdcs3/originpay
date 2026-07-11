<?php

namespace App\Services;

use App\Models\Dispute;

class DisputeMetricsService
{
    public function getGlobalMetrics()
    {
        return [
            'average_resolution_hours' => $this->calculateAverageResolutionTime(),
            'win_rate' => $this->calculateWinRate(),
            'loss_rate' => $this->calculateLossRate(),
            'sla_breached_count' => $this->countSlaBreaches(),
        ];
    }

    protected function calculateAverageResolutionTime()
    {
        $resolvedDisputes = Dispute::whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);

        if ($resolvedDisputes->isEmpty()) {
            return 0;
        }

        return (int) round($resolvedDisputes
            ->map(fn (Dispute $dispute) => $dispute->created_at->diffInHours($dispute->resolved_at))
            ->avg());
    }

    protected function calculateWinRate()
    {
        $total = Dispute::whereIn('status', ['won', 'lost', 'canceled'])->count();
        if ($total === 0) return 0;

        $won = Dispute::where('status', 'won')->count();
        return round(($won / $total) * 100, 2);
    }

    protected function calculateLossRate()
    {
        $total = Dispute::whereIn('status', ['won', 'lost', 'canceled'])->count();
        if ($total === 0) return 0;

        $lost = Dispute::where('status', 'lost')->count();
        return round(($lost / $total) * 100, 2);
    }

    protected function countSlaBreaches()
    {
        return Dispute::whereHas('events', function($q) {
            $q->where('event_type', 'dispute.sla.expired');
        })->count();
    }
}
