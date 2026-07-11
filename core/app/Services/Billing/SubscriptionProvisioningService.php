<?php

namespace App\Services\Billing;

use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\PlanVersion;
use App\Models\Price;
use App\Models\BillingSetting;
use Carbon\Carbon;

class SubscriptionProvisioningService
{
    /**
     * Create a new subscription request.
     */
    public function createSubscription(User $user, PlanVersion $planVersion, Price $price)
    {
        // Cancel existing active subscription if any
        $activeSub = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->first();

        if ($activeSub) {
            $this->cancelSubscription($activeSub, 'immediate', 'Upgrade/Downgrade para novo plano');
        }

        $status = 'pending_payment';
        if ($price->amount == 0) {
            $status = 'active'; // Free plan is active immediately
        }

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_version_id' => $planVersion->id,
            'price_id' => $price->id,
            'status' => $status,
            'trial_ends_at' => $price->trial_days > 0 ? Carbon::now()->addDays($price->trial_days) : null,
        ]);

        SubscriptionHistory::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'new_plan_version_id' => $planVersion->id,
            'new_price_id' => $price->id,
            'new_status' => $status,
            'action' => 'created',
            'reason' => 'User selected a plan',
        ]);

        if ($status === 'active') {
            $this->activateSubscription($subscription);
        }

        return $subscription;
    }

    /**
     * Called via Webhook when payment is confirmed (payment.paid)
     */
    public function activateSubscription(Subscription $subscription)
    {
        if ($subscription->status === 'active') return;

        $oldStatus = $subscription->status;
        
        $subscription->update([
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(), // Assuming monthly. Logic could check cycle.
        ]);

        SubscriptionHistory::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'new_plan_version_id' => $subscription->plan_version_id,
            'new_price_id' => $subscription->price_id,
            'old_status' => $oldStatus,
            'new_status' => 'active',
            'action' => 'activated',
            'reason' => 'Payment confirmed',
        ]);

        // Note: Quotas and Features are automatically applied because PlanAccessService
        // reads from the current active plan features and quotas dynamically.
        // There is no need to manually copy features.
    }

    /**
     * Cancel a subscription (immediate or at end of cycle)
     */
    public function cancelSubscription(Subscription $subscription, string $behavior = 'end_of_cycle', string $reason = 'User requested cancellation')
    {
        $oldStatus = $subscription->status;

        if ($behavior === 'immediate') {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => Carbon::now(),
            ]);

            SubscriptionHistory::create([
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'old_plan_version_id' => $subscription->plan_version_id,
                'old_price_id' => $subscription->price_id,
                'old_status' => $oldStatus,
                'new_status' => 'canceled',
                'action' => 'canceled',
                'reason' => $reason,
            ]);

        } else {
            // End of cycle
            $subscription->update([
                'cancel_at_period_end' => true,
            ]);

            SubscriptionHistory::create([
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'old_plan_version_id' => $subscription->plan_version_id,
                'old_price_id' => $subscription->price_id,
                'old_status' => $oldStatus,
                'new_status' => $oldStatus,
                'action' => 'scheduled_cancellation',
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Handle payment failure
     */
    public function handlePaymentFailure(Subscription $subscription)
    {
        $oldStatus = $subscription->status;
        $subscription->update(['status' => 'past_due']);

        SubscriptionHistory::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'old_plan_version_id' => $subscription->plan_version_id,
            'old_price_id' => $subscription->price_id,
            'old_status' => $oldStatus,
            'new_status' => 'past_due',
            'action' => 'payment_failed',
            'reason' => 'Invoice payment failed',
        ]);
    }

    /**
     * Process grace period expiration
     */
    public function processPastDue(Subscription $subscription)
    {
        $settings = BillingSetting::first();
        $graceDays = $settings->grace_period_days ?? 3;
        
        $dueDate = $subscription->current_period_end->addDays($graceDays);

        if (Carbon::now()->greaterThanOrEqualTo($dueDate)) {
            // Grace period expired
            $oldStatus = $subscription->status;
            $subscription->update(['status' => 'suspended']);

            SubscriptionHistory::create([
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'old_plan_version_id' => $subscription->plan_version_id,
                'old_price_id' => $subscription->price_id,
                'old_status' => $oldStatus,
                'new_status' => 'suspended',
                'action' => 'suspended',
                'reason' => 'Grace period expired after payment failure',
            ]);
        }
    }
}
