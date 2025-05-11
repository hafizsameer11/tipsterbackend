<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PaymentRefference;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaystackService;
use Carbon\Carbon;
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
            $package = Package::find($request->selected_package_id);
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
            $paymentRefference = new PaymentRefference();
            $paymentRefference->reference = $paymentData['reference'];
            $paymentRefference->email = $request->email;
            $paymentRefference->status = 'pending';
            $paymentRefference->amount = $amount;
            $paymentRefference->save();
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
        // production mode
        return response()->json([
            'mode' => 'production'
        ]);
    }
    public function verifyTransaction(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        try {
            $reference = $request->reference;
            $data = $this->paystack->verifyTransaction($reference);

            if ($data['status'] === 'success') {
                // Get payment record
                $paymentRefference = PaymentRefference::where('reference', $reference)->first();
                if (!$paymentRefference) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment reference not found.',
                    ], 404);
                }

                $email = $paymentRefference->email;
                $user = User::where('email', $email)->first();

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found.',
                    ], 404);
                }

                $user->vip_status = 'active';
                $user->save();

                $paymentRefference->status = 'completed';
                $paymentRefference->save();

                // 🧠 Retrieve selected package
                $package = Package::where('amount', $paymentRefference->amount)->first();
                if (!$package) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Matching package not found.',
                    ], 404);
                }

                // ⏳ Calculate expiration
                $purchaseDate = now();
                $expiresAt = match ($package->duration) {
                    "30" => Carbon::parse($purchaseDate)->addMonth(),
                    "7"  => Carbon::parse($purchaseDate)->addWeek(),
                    "1"  => Carbon::parse($purchaseDate)->addDay(),
                    default => now(),
                };

                // ❌ Expire existing subscriptions
                Subscription::where('user_id', $user->id)->update(['status' => 'expired']);

                // ✅ Create subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'purchase_token' => $reference,
                    'status' => 'active',
                    'renewal_date' => $expiresAt,
                    'amount_usd' => $package->amount,
                ]);

                // ✅ Create transaction
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'google_product_id' => null, // Not applicable for Paystack
                    'order_id' => $reference,
                    'purchase_token' => $reference,
                    'amount' => $package->amount,
                    'transaction_date' => now(),
                    'status' => 'completed',
                    'response' => json_encode($data),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified and subscription stored successfully',
                    'subscription' => $subscription,
                    'transaction' => $transaction,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment was not successful',
                'data' => $data,
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Paystack Verification Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to verify payment',
            ], 500);
        }
    }
}
