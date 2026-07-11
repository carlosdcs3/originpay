<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookAdminAudit extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'batch_id',
        'reason',
        'ip_address',
        'user_agent',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
