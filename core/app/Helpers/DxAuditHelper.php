<?php

if (!function_exists('logDxAudit')) {
    function logDxAudit($action, $details = [])
    {
        try {
            \App\Models\DxAuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_id' => request()->attributes->get('request_id') ?? 'evt_'.uniqid(),
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            // Failsafe
            \Illuminate\Support\Facades\Log::error('Falha ao auditar DX: ' . $e->getMessage());
        }
    }
}
