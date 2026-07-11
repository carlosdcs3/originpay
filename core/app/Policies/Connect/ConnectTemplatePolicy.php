<?php
namespace App\Policies\Connect;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\Connect\ConnectAccessContext;
use App\Support\Connect\Capabilities;

class ConnectTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny($user) { return ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::TEMPLATE_READ); }
    public function view($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::TEMPLATE_READ); }
    public function create($user) { return ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::TEMPLATE_WRITE); }
    public function update($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::TEMPLATE_WRITE); }
    public function publish($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::TEMPLATE_PUBLISH); }
    public function delete($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::TEMPLATE_DELETE); }
}
