<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'color',
    ];

    /**
     * Get the merchant (user) that owns the tag.
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Get the contacts associated with this tag.
     */
    public function contacts()
    {
        return $this->belongsToMany(ConnectContact::class, 'connect_contact_tags', 'tag_id', 'contact_id');
    }
}
