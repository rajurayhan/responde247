<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StripeService;
use App\Notifications\SubscriptionInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get Stripe configuration for frontend
     */
    public function getConfig(): JsonResponse
    {
        $resellerId = config('reseller.id');
        
        if (!$resellerId) {
            // Fallback to global config if no reseller context
            return response()->json([
                'success' => true,
                'publishable_key' => config('stripe.publishable_key'),
                'test_mode' => config('stripe.test_mode', true),
                'currency' => config('stripe.currency', 'usd'),
            ]);
        }

        // Get reseller-specific Stripe settings
        $stripeSettings = \App\Models\ResellerSetting::where('reseller_id', $resellerId)
            ->where('group', 'stripe')
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        return response()->json([
            'success' => true,
            'publishable_key' => $stripeSettings['stripe_publishable_key'] ?? config('stripe.publishable_key'),
            'test_mode' => $stripeSettings['stripe_test_mode'] ?? config('stripe.test_mode', true),
            'currency' => $stripeSettings['stripe_currency'] ?? config('stripe.currency', 'usd'),
        ]);
    }

    /**
     * Create a payment intent for one-time payments
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'metadata' => 'nullable|array',
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $currency = $request->currency;
        $metadata = $request->metadata ?? [];
        
        // Add reseller context to metadata
        $metadata['reseller_id'] = $user->reseller_id;
        $metadata['reseller_domain'] = app('currentReseller')->domain ?? null;

        $paymentIntent = $this->stripeService->createPaymentIntent($user, $amount, $currency, $metadata);

        if (!$paymentIntent) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $paymentIntent
        ]);
    }

    /**
     * Create a subscription with Stripe
     */
    public function createSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id',
            'payment_method_id' => 'required|string',
            'billing_interval' => 'nullable|string|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $package = SubscriptionPackage::contentProtection()
            ->findOrFail($request->package_id);

        // Validate package ownership (package must belong to user's reseller)
        if ($package->reseller_id !== $user->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not available for your reseller'
            ], 403);
        }

        $paymentMethodId = $request->payment_method_id;
        $billingInterval = $request->input('billing_interval', 'monthly');

        // Create Stripe subscription with reseller metadata
        $resellerMetadata = [
            'reseller_id' => $user->reseller_id,
            'reseller_domain' => app('currentReseller')->domain ?? null,
        ];
        
        try {
            $stripeResult = $this->stripeService->createSubscription($user, $package, $paymentMethodId, $billingInterval, $resellerMetadata);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        // Determine the amount based on billing interval
        $amount = $billingInterval === 'yearly' ? $package->yearly_price : $package->price;

        // Create local transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'reseller_id' => $user->reseller_id,
            'subscription_package_id' => $package->id,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => Transaction::STATUS_COMPLETED,
            'payment_method' => Transaction::PAYMENT_STRIPE,
            'type' => Transaction::TYPE_SUBSCRIPTION,
            'external_transaction_id' => $stripeResult['subscription_id'],
            'billing_email' => $user->email,
            'billing_name' => $user->name,
            'description' => "Subscription to {$package->name} ({$billingInterval})",
            'processed_at' => now(),
        ]);

        // Create local subscription record
        $subscription = \App\Models\UserSubscription::create([
            'user_id' => $user->id,
            'reseller_id' => $user->reseller_id,
            'subscription_package_id' => $package->id,
            'status' => $stripeResult['status'],
            'current_period_start' => $stripeResult['current_period_start'],
            'current_period_end' => $stripeResult['current_period_end'],
            'trial_ends_at' => $stripeResult['trial_end'],
            'stripe_subscription_id' => $stripeResult['subscription_id'],
            'stripe_customer_id' => $stripeResult['customer_id'],
            'metadata' => [
                'billing_interval' => $billingInterval,
                'stripe_metadata' => $stripeResult['metadata'] ?? [],
            ],
        ]);

        // Update transaction with subscription reference
        $transaction->update([
            'user_subscription_id' => $subscription->id,
        ]);

        // Send invoice email to user
        try {
            $user->notify(new SubscriptionInvoice($subscription, $transaction));
            
            Log::info('Invoice email sent for new subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'transaction_id' => $transaction->id,
                'package_name' => $package->name
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email for new subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription->load('package'),
                'transaction' => $transaction,
                'stripe_data' => $stripeResult,
            ],
            'message' => 'Subscription created successfully'
        ], 201);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Request $request): JsonResponse
    {
        $user = Auth::user();
        $subscription = \App\Models\UserSubscription::contentProtection()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        if (!$subscription->stripe_subscription_id) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not managed by Stripe'
            ], 400);
        }

        $success = $this->stripeService->cancelSubscription($subscription->stripe_subscription_id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription'
            ], 400);
        }

        // Update local subscription
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    /**
     * Update subscription (upgrade/downgrade)
     */
    public function updateSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id',
        ]);

        $user = Auth::user();
        $subscription = \App\Models\UserSubscription::contentProtection()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        $newPackage = SubscriptionPackage::contentProtection()
            ->findOrFail($request->package_id);

        // Validate package ownership (package must belong to user's reseller)
        if ($newPackage->reseller_id !== $user->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not available for your reseller'
            ], 403);
        }

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        if (!$subscription->stripe_subscription_id) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not managed by Stripe'
            ], 400);
        }

        $success = $this->stripeService->updateSubscription($subscription->stripe_subscription_id, $newPackage);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription'
            ], 400);
        }

        // Update local subscription
        $subscription->update([
            'subscription_package_id' => $newPackage->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $subscription->load('package'),
            'message' => 'Subscription updated successfully'
        ]);
    }

    /**
     * Get subscription details
     */
    public function getSubscriptionDetails(Request $request): JsonResponse
    {
        $user = Auth::user();
        $subscription = \App\Models\UserSubscription::contentProtection()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription || !$subscription->stripe_subscription_id) {
            return response()->json([
                'success' => false,
                'message' => 'No Stripe subscription found'
            ], 404);
        }

        $stripeData = $this->stripeService->getSubscription($subscription->stripe_subscription_id);

        if (!$stripeData) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscription details'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'local_subscription' => $subscription->load('package'),
                'stripe_data' => $stripeData,
            ]
        ]);
    }
} 