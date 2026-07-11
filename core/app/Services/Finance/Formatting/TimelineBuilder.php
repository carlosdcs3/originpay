<?php

namespace App\Services\Finance\Formatting;

use Illuminate\Support\Collection;

class TimelineBuilder
{
    /**
     * Retorna uma Collection de [title, subtitle, id] para injetar no x-admin.timeline
     */
    public static function buildForTransaction($trx): Collection
    {
        $events = collect();
        
        $events->push([
            'title' => 'Transação Registrada',
            'subtitle' => $trx->created_at->format('d/m/Y H:i:s'),
            'id' => null
        ]);

        if ($trx->status === 'completed' || $trx->status === 'pending' || $trx->status === 'chargeback') {
            $events->push([
                'title' => 'Status Operacional',
                'subtitle' => 'Atualizado para ' . strtoupper($trx->status) . ' em ' . $trx->updated_at->format('d/m/Y H:i:s'),
                'id' => null
            ]);
        }
        
        return $events;
    }

    public static function buildForSettlement($settlement): Collection
    {
        $events = collect();
        
        $events->push([
            'title' => 'Repasse Agendado',
            'subtitle' => $settlement->created_at->format('d/m/Y H:i:s'),
            'id' => null
        ]);

        if ($settlement->status === \App\Enums\Finance\TransactionStatus::SUCCEEDED->value) {
            $events->push([
                'title' => 'Repasse Liquidado (Pago)',
                'subtitle' => 'Liquidação confirmada em ' . ($settlement->settled_date ? $settlement->settled_date->format('d/m/Y H:i:s') : $settlement->updated_at->format('d/m/Y H:i:s')),
                'id' => null
            ]);
        }
        
        return $events;
    }

    public static function buildForFee($fee): Collection
    {
        $events = collect();
        
        $events->push([
            'title' => 'Taxa Computada (Esperada)',
            'subtitle' => $fee->created_at->format('d/m/Y H:i:s'),
            'id' => null
        ]);

        if ($fee->status === \App\Enums\Finance\FeeStatus::CONFIRMED->value) {
            $events->push([
                'title' => 'Lucro Líquido Confirmado',
                'subtitle' => 'Gateway confirmou os custos em ' . $fee->updated_at->format('d/m/Y H:i:s'),
                'id' => null
            ]);
        } elseif ($fee->status === \App\Enums\Finance\FeeStatus::DIVERGENT->value) {
            $events->push([
                'title' => 'Divergência Encontrada',
                'subtitle' => 'Foi apontada diferença nos custos pelo Gateway em ' . $fee->updated_at->format('d/m/Y H:i:s'),
                'id' => null
            ]);
        }
        
        return $events;
    }
}
