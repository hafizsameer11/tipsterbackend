<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionRepository
{
    public function createSubscription($userId, $packageId, $renewalDate = null)
    {
        $subscription = Subscription::create([
            'user_id' => $userId,
            'package_id' => $packageId,
            'status' => 'active',
            'renewal_date' => $renewalDate ?? Carbon::now()->addMonth()->toDateString(), // Default: 1-month renewal
        ]);

        // Update user's VIP status
        User::where('id', $userId)->update(['vip_status' => 'active']);

        return $subscription;
    }

    public function finishSubscription($userId)
    {
        $subscription = Subscription::where('user_id', $userId)->where('status', 'active')->first();

        if (!$subscription) {
            throw new \Exception("No active subscription found.");
        }

        $subscription->update(['status' => 'expired']);

        // Update user's VIP status to not active
        User::where('id', $userId)->update(['vip_status' => 'not_active']);

        return $subscription;
    }
}
