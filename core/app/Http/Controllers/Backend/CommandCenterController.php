<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CommandCenterController extends Controller
{
    /**
     * Display the fullscreen Command Center view.
     */
    public function index()
    {
        // 1. Health Status
        $activeIncidents = class_exists(\App\Models\PlatformAlert::class) ? \App\Models\PlatformAlert::where('status', 'active')->count() : 0;
        $platformStatus = $activeIncidents > 2 ? 'critical' : ($activeIncidents > 0 ? 'degraded' : 'healthy');

        // 2. Queue Status
        $dlqCount = class_exists(\App\Models\WebhookDeadLetter::class) ? \App\Models\WebhookDeadLetter::count() : 0;
        $jobsCount = \Illuminate\Support\Facades\DB::table('jobs')->count();
        $failedJobsCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

        // 3. Gateways
        $gatewaysHealth = \App\Models\PaymentGateway::select('id', 'name', 'code', 'status as is_active')->get()->map(function($gw) {
            $score = \Illuminate\Support\Facades\Cache::get("gateway:health_score:{$gw->code}");
            $gw->health_score = is_numeric($score) ? (int) $score : null;
            $gw->latency = null;
            $gw->success_rate = null;
            return $gw;
        });

        // 4. Live KPIs (Last hour)
        $start = now()->subHour();
        $trxVolumeHour = \App\Models\Transaction::where('status', \App\Enums\TrxStatus::COMPLETED)->where('created_at', '>=', $start)->sum('amount');
        $trxCountHour = \App\Models\Transaction::where('status', \App\Enums\TrxStatus::COMPLETED)->where('created_at', '>=', $start)->count();
        $failedReconciliations = class_exists(\App\Models\FinancialReconciliation::class)
            ? \App\Models\FinancialReconciliation::where('status', 'FAILED')->count()
            : null;

        // 5. Recent Alerts / Events
        $recentAlerts = class_exists(\App\Models\PlatformAlert::class)
            ? \App\Models\PlatformAlert::latest()->limit(5)->get()
            : collect();

        return view('backend.operations.command_center', compact(
            'activeIncidents', 'platformStatus', 'dlqCount', 'jobsCount', 'failedJobsCount',
            'gatewaysHealth', 'trxVolumeHour', 'trxCountHour', 'recentAlerts', 'failedReconciliations'
        ));
    }
}
