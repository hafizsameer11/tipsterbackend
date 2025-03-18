<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\SubscriptionRequest;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function storePurchase(Request $request)
    {
        Log::info("Data received for subscription:", $request->all());
        $request->validate([
            'google_product_id' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'order_id' => 'nullable|string|unique:subscriptions,order_id',
            'purchase_token' => 'nullable|string|unique:subscriptions,purchase_token',
        ]);

        $user = Auth::user();

        // Find package from database
        $package = DB::table('packages')->where('google_product_id', $request->google_product_id)->first();
        if (!$package) {
            return response()->json(["error" => "Package not found"], 404);
        }

        // Calculate expiration date
        $expiresAt = match ($package->duration) {
            "1 Month" => Carbon::parse($request->purchase_date)->addMonth(),
            "1 Week" => Carbon::parse($request->purchase_date)->addWeek(),
            "1 Day" => Carbon::parse($request->purchase_date)->addDay(),
            default => now(),
        };

        // Deactivate existing subscriptions
        Subscription::where('user_id', $user->id)->update(['status' => 'expired']);

        // Create new subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'google_product_id' => $request->google_product_id,
            'purchase_token' => $request->purchase_token,
            'status' => 'active',
            'renewal_date' => $expiresAt,
            'expires_at' => $expiresAt,
        ]);

        // Store transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'google_product_id' => $request->google_product_id,
            'order_id' => $request->order_id ?? null,
            'purchase_token' => $request->purchase_token,
            'amount' => $package->amount_usd,
            'transaction_date' => now(),
            'status' => 'completed',
            'response'=>$request->response
        ]);

        // Update user VIP status
        // $user->update(['vip_status' => true]);
        $authUser = User::where('user_id', $user->id)->first();
        $authUser->vip_status = 'active';
        $authUser->save();
        return response()->json([
            'message' => 'Subscription and transaction stored successfully!',
            'subscription' => $subscription,
            'transaction' => $transaction,
        ], 201);
    }
    public function createSubscription(SubscriptionRequest $request)
    {
        try {
            $userId = auth()->id();
            $packageId = $request->package_id;
            $renewalDate = $request->renewal_date;

            $subscription = $this->subscriptionService->createSubscription($userId, $packageId, $renewalDate);

            return ResponseHelper::success($subscription, 'Subscription created successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function finishSubscription()
    {
        try {
            $userId = auth()->id();
            $subscription = $this->subscriptionService->finishSubscription($userId);

            return ResponseHelper::success($subscription, 'Subscription finished successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function createPackage(Request $request)
    {
        $package = new Package();
        $package->title = $request->title;
        $package->amount = $request->amount;
        $package->duration = $request->duration;
        $package->save();
        return response()->json(['message' => 'Package created successfully', 'data' => $package], 201);
    }
    public function getAllPackage()
    {
        $packages = Package::all();
        return response()->json(['message' => 'All packages', 'data' => $packages], 200);
    }
}
