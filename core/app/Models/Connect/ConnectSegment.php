<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConnectSegment extends Model
{
    use SoftDeletes;
    protected $table = 'connect_segments';
    protected $guarded = ['id'];
    protected $casts = [
        'rules' => 'array',
        'is_dynamic' => 'boolean'
    ];

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }
}
