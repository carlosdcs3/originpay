<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycForm;
use App\Models\PlatformIncident;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    /**
     * Dashboard Central de Compliance
     */
    public function index(Request $request)
    {
        // 1. Fila de KYC
        try {
            $pendingKyc = KycForm::where('status', 0)->latest()->limit(10)->get();
        } catch (\Exception $e) {
            \Log::warning('Compliance: KycForm query failed.', ['error' => $e->getMessage()]);
            $pendingKyc = collect([]);
        }

        // 2. Histórico de Bloqueios
        try {
            $recentFraudBlocks = class_exists(\App\Models\FraudLog::class) 
                                    ? \App\Models\FraudLog::latest()->limit(10)->get() 
                                    : collect([]); 
        } catch (\Exception $e) {
            \Log::warning('Compliance: FraudLog query failed.', ['error' => $e->getMessage()]);
            $recentFraudBlocks = collect([]);
        }

        // 3. Incidentes de Segurança
        try {
            $securityIncidents = PlatformIncident::where('severity', 'critical')
                                    ->orWhere('title', 'like', '%Replay%')
                                    ->latest()
                                    ->limit(5)
                                    ->get();
        } catch (\Exception $e) {
            \Log::warning('Compliance: PlatformIncident query failed.', ['error' => $e->getMessage()]);
            $securityIncidents = collect([]);
        }

        return view('backend.compliance.dashboard', compact('pendingKyc', 'recentFraudBlocks', 'securityIncidents'));
    }
}
