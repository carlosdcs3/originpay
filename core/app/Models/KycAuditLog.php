<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'kyc_submission_id',
        'action',
        'document_type',
        'notes',
        'ip_address',
        'user_agent',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function kycSubmission()
    {
        return $this->belongsTo(KycSubmission::class);
    }
}
