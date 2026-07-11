<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Dispute;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisputePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dispute.
     */
    public function view(\Illuminate\Contracts\Auth\Authenticatable $user, Dispute $dispute)
    {
        // TODO: Real tenant/workspace checking logic should be implemented here.
        // E.g., return $user->workspace_id === $dispute->workspace_id;
        
        // Basic check for authenticated admin
        if (!$user) {
            return false;
        }

        // Example: Only superadmins or specific risk_ops role can see everything
        // return $user->hasRole('risk_ops');
        
        return true; // Temporary fallback as per user request to not invent unmapped tenant structure
    }

    /**
     * Determine whether the user can update the dispute.
     */
    public function update(\Illuminate\Contracts\Auth\Authenticatable $user, Dispute $dispute)
    {
        return $this->view($user, $dispute);
    }

    /**
     * Determine whether the MERCHANT can view the dispute.
     */
    public function viewAsMerchant(User $user, Dispute $dispute)
    {
        if (!$user) {
            return false;
        }

        // The merchant must be the owner of the dispute
        return (int)$dispute->merchant_id === (int)$user->id;
    }

    /**
     * Determine whether the MERCHANT can interact with the dispute (send messages/evidence).
     */
    public function interactAsMerchant(User $user, Dispute $dispute)
    {
        return $this->viewAsMerchant($user, $dispute);
    }
}
