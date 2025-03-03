<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\UserSubscription;
use Illuminate\Http\Request;

class UserSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'subscriber_id' => 'required|exists:users,id',
            'subscribed_to_id' => 'required|exists:users,id|different:subscriber_id'
        ]);

        $exists = UserSubscription::where([
            'subscriber_id' => $request->subscriber_id,
            'subscribed_to_id' => $request->subscribed_to_id,
        ])->exists();

        if ($exists) {
            return ResponseHelper::error('You are already subscribed to this user.');
        }

        $subscription = UserSubscription::create([
            'subscriber_id' => $request->subscriber_id,
            'subscribed_to_id' => $request->subscribed_to_id,
        ]);

        return ResponseHelper::success($subscription, 'Subscription successful.');
    }

    // Unsubscribe from a user
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'subscriber_id' => 'required|exists:users,id',
            'subscribed_to_id' => 'required|exists:users,id'
        ]);

        UserSubscription::where([
            'subscriber_id' => $request->subscriber_id,
            'subscribed_to_id' => $request->subscribed_to_id,
        ])->delete();

        return ResponseHelper::success([], 'Unsubscribed successfully.');
    }

    // Get all users a user is subscribed to
    public function getUserSubscriptions($userId)
    {
        $subscriptions = UserSubscription::where('subscriber_id', $userId)
            ->with('subscribedTo')
            ->get();

        return ResponseHelper::success($subscriptions, 'Subscriptions retrieved successfully.');
    }

    // Get all subscribers of a user
    public function getSubscribers($userId)
    {
        $subscribers = UserSubscription::where('subscribed_to_id', $userId)
            ->with('subscriber')
            ->get();

        return ResponseHelper::success($subscribers, 'Subscribers retrieved successfully.');
    }
}
