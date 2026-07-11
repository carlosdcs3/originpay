<?php

namespace App\Listeners;

use App\Events\ChargePaidEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\EmailTemplateService;

class SendChargePaidEmailListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public function handle(ChargePaidEvent $event): void
    {
        $charge = $event->charge;
        $user = $charge->user; // Relacionamento assumido existente no model Charge
        
        if ($user && $user->email) {
            $emailService = app(EmailTemplateService::class);
            $emailService->sendChargePaid($user->email, $user->name ?? 'Lojista', $charge->uuid, $event->amountPaid);
        }
    }
}
