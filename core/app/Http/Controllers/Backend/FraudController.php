<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\FraudProfile;
use App\Models\DeviceFingerprint;
use App\Models\IdentityFingerprint;
use App\Models\FraudEvent;
use Illuminate\Http\Request;

class FraudController extends Controller
{
    public function index()
    {
        $metrics = [
            'LOW' => FraudProfile::where('risk_level', 'LOW')->count(),
            'MEDIUM' => FraudProfile::where('risk_level', 'MEDIUM')->count(),
            'HIGH' => FraudProfile::where('risk_level', 'HIGH')->count(),
            'CRITICAL' => FraudProfile::where('risk_level', 'CRITICAL')->count(),
        ];

        $latestEvents = FraudEvent::with('user')->latest()->take(20)->get();

        // Mapear farms: devices com múltiplas contas
        // Not optimized for huge scale but works for the dashboard logic.
        $sharedDevicesCount = DeviceFingerprint::select('fingerprint_hash')
            ->groupBy('fingerprint_hash')
            ->havingRaw('COUNT(DISTINCT user_id) > 1')
            ->count();

        return view('backend.fraud.index', compact('metrics', 'latestEvents', 'sharedDevicesCount'));
    }
}
