<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;

class ConnectTag extends Model
{
    protected $table = 'connect_tags';
    protected $guarded = ['id'];

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }
}
