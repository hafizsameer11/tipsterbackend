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
        'package_id' => 'nullable',
        'google_product_id' => 'nullable|array',
        'purchase_date' => 'nullable',
        'order_id' => 'nullable',
        'purchase_token' => 'nullable',
    ]);

    $user = Auth::user();
    $authUser = User::where('id', $user->id)->first();
    $authUser->vip_status = 'active';
    $authUser->save();

    $purchaseDate = now();

    // Find package from database
    $package = Package::find($request->package_id);
    if (!$package) {
        return response()->json(["error" => "Package not found"], 404);
    }

    // Calculate expiration date
    $expiresAt = match ($package->duration) {
        "30" => Carbon::parse($purchaseDate)->addMonth(),
        "7" => Carbon::parse($purchaseDate)->addWeek(),
        "1" => Carbon::parse($purchaseDate)->addDay(),
        default => now(),
    };

    // Deactivate existing subscriptions
    Subscription::where('user_id', $user->id)->update(['status' => 'expired']);

    // Create new subscription
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'google_product_id' => json_encode($request->google_product_id), // Convert array to JSON
        'purchase_token' => $request->purchase_token,
        'status' => 'active',
        'renewal_date' => $expiresAt,
        'amount_usd' => $package->amount,
    ]);

    // Store transaction record
    $transaction = Transaction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'google_product_id' => json_encode($request->google_product_id), // Convert array to JSON
        'order_id' => $request->order_id ?? null,
        'purchase_token' => $request->purchase_token,
        'amount' => $package->amount,
        'transaction_date' => now(),
        'status' => 'completed',
        'response' => $request->response
    ]);

    return response()->json([
        'message' => 'Subscription and transaction stored successfully!',
        'subscription' => $subscription,
        'transaction' => $transaction,
    ], 201);
}

    public function getTransaction()
    {
        $user = Auth::user();
        $transactions = Transaction::where('user_id', $user->id)
            ->with('subscription.package')
            ->get()
            ->map(function ($transaction) {
                return [
                    'title' => optional($transaction->subscription->package)->title ?? 'Subscription Payment',
                    'amount' => (float) $transaction->amount,
                    'ref' => $transaction->order_id,
                    'date' => \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y'),
                ];
            });

        return ResponseHelper::success($transactions, 'Subscription data fetched successfully');
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
    public function getSubscriptions()
    {
        $subscriptions = Subscription::with('user', 'package')->get();

        $transformed = $subscriptions->map(function ($sub) {
            return [
                'select' => false,
                'name' => $sub->user->username ?? 'N/A',
                'duration' => $sub->package->duration . ' Day' . ($sub->package->duration > 1 ? 's' : ''),
                'reference' => json_decode($sub->google_product_id)->order_id ?? 'N/A',
                'email' => $sub->user->email ?? 'N/A',
                'amount' => 'N ' . number_format($sub->amount_usd, 2),
                'sub_date' => \Carbon\Carbon::parse($sub->created_at)->format('Y-m-d'),
                'exp_date' => $sub->expires_at
                    ? \Carbon\Carbon::parse($sub->expires_at)->format('Y-m-d')
                    : \Carbon\Carbon::parse($sub->renewal_date)->format('Y-m-d'),
                'status' => $sub->status === 'active'
            ];
        });

        // Calculate total revenue using package amount
        $totalRevenue = $subscriptions->sum(function ($sub) {
            return (float) $sub->package->amount;
        });

        $totalUsers = \App\Models\User::count();
        $totalSubscribers = $subscriptions->pluck('user_id')->unique()->count();
        $totalProfit = $totalRevenue; // Change this if profit differs

        return response()->json([
            'message' => 'All subscriptions',
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalSubscribers' => $totalSubscribers,
                'subscriptionRevenue' => 'N ' . number_format($totalRevenue, 0),
                'totalProfit' => 'N ' . number_format($totalProfit, 0),
            ],
            'data' => $transformed
        ], 200);
    }


}
