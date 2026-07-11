<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectProviderCredential extends Model
{
    use HasFactory;

    protected $table = 'connect_provider_credentials';
    protected $guarded = ['id'];
    
    protected $casts = [
        'credentials' => 'encrypted:array',
        'configuration' => 'array',
        'is_active' => 'boolean',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'health_score' => 'decimal:2',
    ];

    public function merchant()
    {
        return $this->belongsTo(\App\Models\User::class, 'merchant_id');
    }

    public function recordSuccess()
    {
        $this->increment('success_count');
        $this->last_success_at = now();
        $this->recalculateHealth();
    }

    public function recordFailure($errorMessage = null)
    {
        $this->increment('failure_count');
        $this->last_failure_at = now();
        $this->last_error = $errorMessage;
        $this->recalculateHealth();
    }

    protected function recalculateHealth()
    {
        $total = $this->success_count + $this->failure_count;
        if ($total > 0) {
            $this->health_score = round(($this->success_count / $total) * 100, 2);
        }
        $this->save();
    }
}
