<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAnomaly;
use App\Services\FinancialHealthScoreService;

class AnomalyAdminController extends Controller
{
    public function index(FinancialHealthScoreService $healthService)
    {
        $query = FinancialAnomaly::query();

        if (request()->has('status')) {
            if (request('status') === 'resolved') {
                $query->whereNotNull('resolved_at');
            } else {
                $query->whereNull('resolved_at');
            }
        } else {
            // Default to open anomalies
            $query->whereNull('resolved_at');
        }

        if (request()->has('severity')) {
            $query->where('severity', request('severity'));
        }

        $anomalies = $query->orderBy('detected_at', 'desc')->paginate(20);

        // Score
        $health = $healthService->calculateScore();

        // Metrics
        $resolvedLast7Days = FinancialAnomaly::where('resolved_at', '>=', now()->subDays(7))->count();
        $counts = FinancialAnomaly::selectRaw('severity, COUNT(*) as count')
            ->whereNull('resolved_at')
            ->groupBy('severity')
            ->pluck('count', 'severity');

        return view('backend.anomalies.index', compact('anomalies', 'health', 'resolvedLast7Days', 'counts'));
    }

    public function resolve(Request $request, $id)
    {
        $request->validate(['resolution_notes' => 'required|string']);

        $anomaly = FinancialAnomaly::findOrFail($id);
        
        if ($anomaly->isResolved()) {
            return back()->with('error', 'Anomaly is already resolved.');
        }

        $anomaly->resolved_at = now();
        $anomaly->resolved_by = auth()->guard('admin')->id() ?? auth()->id() ?? 1;
        $anomaly->resolution_notes = $request->resolution_notes;
        $anomaly->save();

        return back()->with('success', 'Anomaly marked as resolved.');
    }
}
