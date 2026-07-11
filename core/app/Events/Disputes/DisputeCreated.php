<?php

namespace App\Events\Disputes;

use App\Models\Dispute;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DisputeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Dispute $dispute)
    {
    }
}
