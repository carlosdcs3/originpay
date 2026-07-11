<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConnectContact extends Model
{
    use SoftDeletes;
    protected $table = 'connect_contacts';
    protected $guarded = ['id'];

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function tags()
    {
        return $this->belongsToMany(ConnectTag::class, 'connect_contact_tags', 'contact_id', 'tag_id')->withTimestamps();
    }

    public function customFields()
    {
        return $this->hasMany(ConnectContactCustomField::class, 'contact_id');
    }
}
