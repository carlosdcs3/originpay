<?php

use Illuminate\Support\Str;

if (!function_exists('uiStatusLabel')) {
    /**
     * Retorna a versão traduzida e amigável de status técnicos para exibição na UI.
     *
     * @param string|null $status
     * @return string
     */
    function uiStatusLabel(?string $status): string
    {
        if (!$status) {
            return '-';
        }

        $labels = [
            'active' => 'Ativa',
            'past_due' => 'Em atraso',
            'canceled' => 'Cancelada',
            'cancelled' => 'Cancelada',
            'incomplete' => 'Incompleta',
            'trialing' => 'Em teste',
            'paused' => 'Pausada',
            'pending' => 'Pendente',
            'processing' => 'Processando',
            'completed' => 'Concluída',
            'failed' => 'Falhou',
            'inactive' => 'Inativa',
        ];

        $lowerStatus = strtolower(trim($status));

        return $labels[$lowerStatus] ?? ucfirst(str_replace('_', ' ', $status));
    }
}
