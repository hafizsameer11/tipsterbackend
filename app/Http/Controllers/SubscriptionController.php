<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\SubscriptionRequest;
use App\Models\Package;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
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
    public function createPackage(Request $request){
        $package = new Package();
        $package->title = $request->title;
        $package->amount = $request->amount;
        $package->duration = $request->duration;
        $package->save();
        return response()->json(['message' => 'Package created successfully', 'data' => $package], 201);
    }
    public function getAllPackage(){
        $packages = Package::all();
        return response()->json(['message' => 'All packages', 'data' => $packages], 200);
    }
}
