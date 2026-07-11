<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPassword extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'password_hash',
        'failed_attempts',
        'locked_until',
        'last_changed_at',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'last_changed_at' => 'datetime',
        'failed_attempts' => 'integer',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the user that owns the transaction password.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
