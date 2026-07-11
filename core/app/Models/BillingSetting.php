<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function fallbackPlan()
    {
        return $this->belongsTo(CommercialPlan::class, 'fallback_plan_id');
    }
}
