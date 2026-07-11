<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ConnectContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'email',
        'phone',
        'whatsapp',
        'country',
        'language',
        'timezone',
        'source',
        'status',
        'notes',
    ];

    /**
     * Get the merchant (user) that owns the contact.
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Get the tags associated with the contact.
     */
    public function tags()
    {
        return $this->belongsToMany(ConnectTag::class, 'connect_contact_tags', 'contact_id', 'tag_id');
    }

    /**
     * Scope a query to only include active contacts.
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include contacts with WhatsApp.
     */
    public function scopeHasWhatsapp(Builder $query)
    {
        return $query->whereNotNull('whatsapp')->where('whatsapp', '!=', '');
    }

    /**
     * Scope a query to only include contacts with Email.
     */
    public function scopeHasEmail(Builder $query)
    {
        return $query->whereNotNull('email')->where('email', '!=', '');
    }

    /**
     * Scope a query to search contacts.
     */
    public function scopeSearch(Builder $query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('email', 'LIKE', "%{$term}%")
              ->orWhere('phone', 'LIKE', "%{$term}%")
              ->orWhere('whatsapp', 'LIKE', "%{$term}%");
        });
    }
}
