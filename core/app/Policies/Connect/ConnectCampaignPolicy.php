<?php
namespace App\Policies\Connect;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\Connect\ConnectAccessContext;
use App\Support\Connect\Capabilities;

class ConnectCampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny($user) { return ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::CAMPAIGN_READ); }
    public function view($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::CAMPAIGN_READ); }
    public function create($user) { return ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::CAMPAIGN_WRITE); }
    public function update($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::CAMPAIGN_WRITE); }
    public function execute($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::CAMPAIGN_EXECUTE); }
    public function cancel($user, $model) { return $user->id === $model->merchant_id && ConnectAccessContext::getInstance($user->id)->hasFeature(Capabilities::CAMPAIGN_CANCEL); }
}
