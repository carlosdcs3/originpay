<?php
namespace App\Policies\Connect;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\Connect\ConnectAccessContext;
use App\Support\Connect\Capabilities;

class ConnectWhatsappInstancePolicy
{
    use HandlesAuthorization;

    public function viewAny($user) {
        return ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::WHATSAPP_SEND);
    }

    public function view($user, $model) {
        return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::WHATSAPP_SEND);
    }

    public function create($user) {
        return ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::WHATSAPP_SEND);
    }

    public function update($user, $model) {
        return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::WHATSAPP_SEND);
    }

    public function delete($user, $model) {
        return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::WHATSAPP_SEND);
    }
}
