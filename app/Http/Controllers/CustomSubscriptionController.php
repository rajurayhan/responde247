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

    /**
     * Get Stripe customers for admin
     */
    public function getStripeCustomers(): JsonResponse
    {
        // Only admins can access Stripe customers
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $customers = $this->stripeService->getAllCustomers();
            
            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Stripe customers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Stripe customers'
            ], 500);
        }
    }

    /**
     * Get customer subscriptions from Stripe
     */
    public function getCustomerSubscriptions(Request $request): JsonResponse
    {
        // Only admins can access customer subscriptions
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'customer_id' => 'required|string'
        ]);

        try {
            $subscriptions = $this->stripeService->getCustomerSubscriptions($request->customer_id);
            
            return response()->json([
                'success' => true,
                'data' => $subscriptions
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch customer subscriptions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer subscriptions'
            ], 500);
        }
    }

    /**
     * Sync existing Stripe subscription
     */
    public function syncFromStripe(Request $request): JsonResponse
    {
        // Only admins can sync from Stripe
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'package_id' => 'required|exists:subscription_packages,id',
            'stripe_customer_id' => 'required|string',
            'stripe_subscription_id' => 'required|string'
        ]);

        try {
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

            // Get Stripe subscription and customer data
            $stripeSubscription = $this->stripeService->getSubscription($request->stripe_subscription_id);
            $stripeCustomer = $this->stripeService->getCustomer($request->stripe_customer_id);

            if (!$stripeSubscription || !$stripeCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch Stripe data'
                ], 400);
            }

            // Check if subscription already exists
            $existingSubscription = UserSubscription::where('stripe_subscription_id', $request->stripe_subscription_id)->first();
            
            if ($existingSubscription) {
                Log::info('Updating existing subscription', [
                    'subscription_id' => $existingSubscription->id,
                    'stripe_subscription_id' => $request->stripe_subscription_id,
                    'user_id' => $user->id,
                    'existing_user_id' => $existingSubscription->user_id
                ]);
            } else {
                Log::info('Creating new subscription', [
                    'stripe_subscription_id' => $request->stripe_subscription_id,
                    'user_id' => $user->id
                ]);
            }

            // Create or update subscription
            $subscription = UserSubscription::updateOrCreate(
                [
                    'stripe_subscription_id' => $request->stripe_subscription_id
                ],
                [
                    'user_id' => $user->id,
                    'subscription_package_id' => $package->id,
                    'status' => $this->mapStripeStatusToLocalStatus($stripeSubscription['status']),
                    'current_period_start' => $stripeSubscription['current_period_start'],
                    'current_period_end' => $stripeSubscription['current_period_end'],
                    'trial_ends_at' => $stripeSubscription['trial_end'],
                    'stripe_customer_id' => $request->stripe_customer_id,
                    'stripe_subscription_id' => $request->stripe_subscription_id,
                    'reseller_id' => Auth::user()->reseller_id,
                    'metadata_json' => json_encode([
                        'stripe_customer' => $stripeCustomer,
                        'stripe_subscription' => $stripeSubscription
                    ])
                ]
            );

            // Sync transactions
            $this->syncSubscriptionTransactions($subscription, $request->stripe_subscription_id);

            return response()->json([
                'success' => true,
                'message' => 'Subscription synced successfully',
                'data' => $subscription->load(['user', 'package'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync from Stripe: ' . $e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync subscription from Stripe'
            ], 500);
        }
    }

    /**
     * Map Stripe status to local status
     */
    private function mapStripeStatusToLocalStatus(string $stripeStatus): string
    {
        $statusMap = [
            'active' => 'active',
            'canceled' => 'cancelled',
            'incomplete' => 'pending',
            'incomplete_expired' => 'expired',
            'past_due' => 'past_due',
            'trialing' => 'trial',
            'unpaid' => 'expired'
        ];

        return $statusMap[$stripeStatus] ?? 'pending';
    }

    /**
     * Sync subscription transactions from Stripe
     */
    private function syncSubscriptionTransactions(UserSubscription $subscription, string $stripeSubscriptionId): void
    {
        try {
            $transactions = $this->stripeService->getSubscriptionTransactions($stripeSubscriptionId);
            
            foreach ($transactions as $transaction) {
                \App\Models\Transaction::updateOrCreate(
                    ['external_transaction_id' => $transaction['id']],
                    [
                        'user_id' => $subscription->user_id,
                        'reseller_id' => $subscription->reseller_id,
                        'subscription_package_id' => $subscription->subscription_package_id,
                        'user_subscription_id' => $subscription->id,
                        'amount' => ($transaction['amount_paid'] ?? 0) / 100, // Convert from cents
                        'currency' => strtoupper($transaction['currency']),
                        'status' => $this->mapStripeTransactionStatus($transaction['status'], $transaction['paid']),
                        'payment_method' => 'stripe',
                        'payment_method_id' => $transaction['payment_intent_details']['id'] ?? null,
                        'payment_details' => json_encode($transaction),
                        'billing_email' => $transaction['customer_email'] ?? $subscription->user->email ?? null,
                        'billing_name' => $transaction['customer_name'] ?? $subscription->user->name ?? null,
                        'billing_address' => $this->formatBillingAddress($transaction['customer_address'] ?? null),
                        'billing_city' => $this->extractAddressField($transaction['customer_address'] ?? null, 'city'),
                        'billing_state' => $this->extractAddressField($transaction['customer_address'] ?? null, 'state'),
                        'billing_country' => $this->extractAddressField($transaction['customer_address'] ?? null, 'country'),
                        'billing_postal_code' => $this->extractAddressField($transaction['customer_address'] ?? null, 'postal_code'),
                        'type' => 'subscription',
                        'description' => 'Subscription payment',
                        'metadata' => json_encode($transaction['metadata'] ?? []),
                        'processed_at' => $transaction['paid'] ? Carbon::createFromTimestamp($transaction['created']) : null,
                        'failed_at' => !$transaction['paid'] ? Carbon::createFromTimestamp($transaction['created']) : null,
                        'transaction_id' => $transaction['id']
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync transactions from Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Map Stripe transaction status to local status
     */
    private function mapStripeTransactionStatus(string $stripeStatus, bool $paid): string
    {
        if ($paid) {
            return 'completed';
        }

        $statusMap = [
            'draft' => 'pending',
            'open' => 'pending',
            'paid' => 'completed',
            'uncollectible' => 'failed',
            'void' => 'cancelled'
        ];

        return $statusMap[$stripeStatus] ?? 'pending';
    }

    /**
     * Format billing address from Stripe data
     */
    private function formatBillingAddress($address): ?string
    {
        if (!$address) return null;
        
        // Convert StripeObject to array if needed
        if (is_object($address)) {
            $address = (array) $address;
        }
        
        $parts = array_filter([
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['country'] ?? null,
            $address['postal_code'] ?? null,
        ]);
        
        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Extract specific field from address
     */
    private function extractAddressField($address, string $field): ?string
    {
        if (!$address) return null;
        
        // Convert StripeObject to array if needed
        if (is_object($address)) {
            $address = (array) $address;
        }
        
        return $address[$field] ?? null;
    }
}
