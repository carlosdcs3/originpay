<?php

namespace App\Events;

use App\Models\Charge;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChargePaidEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $charge;
    public $amountPaid;

    /**
     * Create a new event instance.
     */
    public function __construct(Charge $charge, float $amountPaid)
    {
        $this->charge = $charge;
        $this->amountPaid = $amountPaid;
    }
}
