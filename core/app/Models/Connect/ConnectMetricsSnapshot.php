<?php
namespace App\Models\Connect;
use Illuminate\Database\Eloquent\Model;

class ConnectMetricsSnapshot extends Model
{
    protected $table = 'connect_metrics_snapshots';
    protected $guarded = ['id'];
    protected $casts = [
        'bucket_start' => 'datetime',
        'bucket_end' => 'datetime',
        'metadata' => 'array',
        'value' => 'decimal:2',
        'p50' => 'decimal:2',
        'p95' => 'decimal:2',
        'p99' => 'decimal:2',
    ];
}
