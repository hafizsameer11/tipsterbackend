<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //
    protected $paystack;
    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    /**
     * Initiate payment from React Native app
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'selected_package_id' => 'required',
        ]);

        try {
            $package=Package::find($request->selected_package_id);
            if (!$package) {
                return response()->json([
                    'error' => 'Package not found'
                ], 404);
            }
            $amount = $package->amount; // Assuming price is in Naira
            $callbackUrl = route('paystack.callback'); // Should be a valid web route
            $paymentData = $this->paystack->initializeTransaction(
                $request->email,
                $amount,
                $callbackUrl
            );
            Log::info('Paystack payment initialized', $paymentData);
            return response()->json([
                'authorization_url' => $paymentData['authorization_url'],
                'access_code' => $paymentData['access_code'],
                'reference' => $paymentData['reference'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Unable to initiate payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function callback(Request $request)
    {
        Log::info('Paystack callback received', $request->all());
        return response()->json([
            'message' => 'Callback received successfully',
            'data' => $request->all(),
        ]);
        // Handle the callback from Paystack
        // Verify the payment and update your database accordingly
        // You can use the reference passed in the request to verify the payment
        // Example: $this->paystack->verifyTransaction($request->reference);
    }
    public function appMode()
    {
        return response()->json([
            'mode' => 'production'
        ]);
    }
}
