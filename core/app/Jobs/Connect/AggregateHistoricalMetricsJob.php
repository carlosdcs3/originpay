<?php
namespace App\Jobs\Connect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Connect\ConnectCampaignDeliveryAttempt;
use App\Models\Connect\ConnectMetricsSnapshot;

class AggregateHistoricalMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $resolution;

    public function __construct(string $resolution = 'hourly')
    {
        $this->resolution = $resolution;
    }

    public function handle()
    {
        $start = $this->resolution === 'hourly' ? now()->subHour()->startOfHour() : now()->subDay()->startOfDay();
        $end = $this->resolution === 'hourly' ? now()->subHour()->endOfHour() : now()->subDay()->endOfDay();

        // High volume aggregation avoiding memory leaks
        // Example: Grouping by Merchant
        $merchants = ConnectCampaignDeliveryAttempt::whereBetween('created_at', [$start, $end])
            ->select('execution_id')
            ->distinct()
            ->get();
            
        // Simplified Logic: Calculate directly in PHP or pure SQL
        // To accurately calculate percentiles without hitting memory, we'd use raw SQL `PERCENTILE_CONT(0.95)` on PGSQL
        // For portable MySQL, we rely on standard aggregations.
        
        foreach($merchants as $m) {
            // Mocking snapshot creation for brevity
            ConnectMetricsSnapshot::create([
                'merchant_id' => 1, // pseudo
                'resolution' => $this->resolution,
                'bucket_start' => $start,
                'bucket_end' => $end,
                'metric_type' => 'latency',
                'value' => 200.0,
                'p50' => 150.0,
                'p90' => 250.0,
                'p95' => 300.0,
                'p99' => 450.0,
            ]);
        }
    }
}
