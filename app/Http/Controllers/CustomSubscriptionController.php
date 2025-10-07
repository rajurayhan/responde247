<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use App\Models\UserSubscription;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomSubscriptionController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a custom subscription with payment link
     */
    public function createCustomSubscription(Request $request): JsonResponse
    {
        // Only admins can create custom subscriptions
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'package_id' => 'required|exists:subscription_packages,id',
            'custom_amount' => 'required|numeric|min:0.01',
            'billing_interval' => 'required|in:monthly,yearly',
            'duration_months' => 'required|integer|min:1|max:120', // Max 10 years
        ]);

        $user = User::findOrFail($request->user_id);
        $package = SubscriptionPackage::findOrFail($request->package_id);

        // Ensure user belongs to the same reseller
        if ($user->reseller_id !== Auth::user()->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create subscription for this user'
            ], 403);
        }

        // Ensure package belongs to the same reseller
        if ($package->reseller_id !== Auth::user()->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to use this package'
            ], 403);
        }
        $customAmount = $request->custom_amount;
        $billingInterval = $request->billing_interval;
        $durationMonths = $request->duration_months;

        // Calculate subscription period
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = Carbon::now()->addMonths($durationMonths);

        // Create the subscription with pending status
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'pending',
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'custom_amount' => $customAmount,
        ]);

        // Generate payment link
        try {
            $paymentLinkResult = $this->stripeService->createPaymentLink(
                $user,
                $customAmount,
                'usd',
                [
                    'subscription_id' => $subscription->id,
                    'package_id' => $package->id,
                    'billing_interval' => $billingInterval,
                    'duration_months' => $durationMonths,
                ]
            );

            if (!$paymentLinkResult) {
                throw new \Exception('Failed to generate payment link');
            }

            // Update subscription with payment link details
            $subscription->update([
                'payment_link_id' => $paymentLinkResult['payment_link_id'],
                'payment_link_url' => $paymentLinkResult['payment_link_url'],
            ]);

            Log::info('Custom subscription created with payment link', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'amount' => $customAmount,
                'payment_link_id' => $paymentLinkResult['payment_link_id'],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->load('package'),
                    'payment_link' => $paymentLinkResult['payment_link_url'],
                    'amount' => $customAmount,
                    'expires_at' => $currentPeriodStart->addDays(7)->toISOString(), // Payment link expires in 7 days
                ],
                'message' => 'Custom subscription created successfully with payment link'
            ], 201);

        } catch (\Exception $e) {
            // Delete the subscription if payment link generation fails
            $subscription->delete();

            Log::error('Failed to create custom subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create custom subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get custom subscriptions for admin
     */
    public function getCustomSubscriptions(): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $customSubscriptions = UserSubscription::contentProtection()
            ->whereNotNull('custom_amount')
            ->with(['user', 'package'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customSubscriptions
        ]);
    }

    /**
     * Activate a custom subscription after payment
     */
    public function activateSubscription(Request $request): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'subscription_id' => 'required|exists:user_subscriptions,id',
        ]);

        $subscription = UserSubscription::contentProtection()->findOrFail($request->subscription_id);

        if ($subscription->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not in pending status'
            ], 400);
        }

        // Activate the subscription
        $subscription->update([
            'status' => 'active',
        ]);

        Log::info('Custom subscription activated', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated successfully'
        ]);
    }

    /**
     * Resend payment link for expired subscription
     */
    public function resendPaymentLink(Request $request): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'subscription_id' => 'required|exists:user_subscriptions,id',
        ]);

        $subscription = UserSubscription::contentProtection()->findOrFail($request->subscription_id);

        if ($subscription->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not in pending status'
            ], 400);
        }

        // Generate new payment link
        try {
            $paymentLinkResult = $this->stripeService->createPaymentLink(
                $subscription->user,
                $subscription->custom_amount,
                'usd',
                [
                    'subscription_id' => $subscription->id,
                    'package_id' => $subscription->package_id,
                    'resend' => true,
                ]
            );

            if (!$paymentLinkResult) {
                throw new \Exception('Failed to generate new payment link');
            }

            // Update subscription with new payment link
            $subscription->update([
                'payment_link_id' => $paymentLinkResult['payment_link_id'],
                'payment_link_url' => $paymentLinkResult['payment_link_url'],
            ]);

            Log::info('Payment link resent for custom subscription', [
                'subscription_id' => $subscription->id,
                'new_payment_link_id' => $paymentLinkResult['payment_link_id'],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_link' => $paymentLinkResult['payment_link_url'],
                    'expires_at' => Carbon::now()->addDays(7)->toISOString(),
                ],
                'message' => 'Payment link resent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resend payment link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend payment link: ' . $e->getMessage()
            ], 500);
        }
    }
}
