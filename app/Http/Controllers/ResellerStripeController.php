<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\ResellerPackage;
use App\Models\ResellerSubscription;
use App\Models\ResellerTransaction;
use App\Models\ResellerSetting;
use App\Services\StripeService;
use App\Traits\SafeTimestampConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ResellerStripeController extends Controller
{
    use SafeTimestampConversion;
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get Stripe configuration for admin panel
     */
    public function getConfig(): JsonResponse
    {
        try {
            $resellerId = config('reseller.id');
            
            if (!$resellerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reseller context found'
                ], 400);
            }

            // Get reseller-specific Stripe settings
            $stripeSettings = ResellerSetting::where('reseller_id', $resellerId)
                ->where('group', 'stripe')
                ->get()
                ->pluck('value', 'key')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'stripe_secret_key' => $stripeSettings['stripe_secret_key'] ?? '',
                    'stripe_publishable_key' => $stripeSettings['stripe_publishable_key'] ?? '',
                    'stripe_webhook_secret' => $stripeSettings['stripe_webhook_secret'] ?? '',
                    'stripe_test_mode' => $stripeSettings['stripe_test_mode'] ?? true,
                    'stripe_currency' => $stripeSettings['stripe_currency'] ?? 'usd',
                    'stripe_api_version' => $stripeSettings['stripe_api_version'] ?? '2023-10-16',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting Stripe config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Stripe configuration'
            ], 500);
        }
    }

    /**
     * Update Stripe configuration
     */
    public function updateConfig(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'stripe_secret_key' => 'required|string',
                'stripe_publishable_key' => 'required|string',
                'stripe_webhook_secret' => 'nullable|string',
                'stripe_test_mode' => 'boolean',
                'stripe_currency' => 'string|in:usd,eur,gbp,cad,aud',
                'stripe_api_version' => 'string',
            ]);

            $resellerId = config('reseller.id');
            
            if (!$resellerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reseller context found'
                ], 400);
            }

            // Define Stripe setting labels and descriptions
            $stripeSettingLabels = [
                'stripe_secret_key' => 'Stripe Secret Key',
                'stripe_publishable_key' => 'Stripe Publishable Key',
                'stripe_webhook_secret' => 'Stripe Webhook Secret',
                'stripe_test_mode' => 'Test Mode',
                'stripe_currency' => 'Currency',
                'stripe_api_version' => 'API Version',
            ];

            $stripeSettingDescriptions = [
                'stripe_secret_key' => 'Your Stripe secret key for backend operations',
                'stripe_publishable_key' => 'Your Stripe publishable key for frontend integration',
                'stripe_webhook_secret' => 'Your Stripe webhook endpoint secret for verification',
                'stripe_test_mode' => 'Enable or disable Stripe test mode',
                'stripe_currency' => 'Default currency for Stripe transactions',
                'stripe_api_version' => 'Stripe API version to use',
            ];

            // Update or create Stripe settings
            foreach ($validated as $key => $value) {
                ResellerSetting::updateOrCreate(
                    [
                        'reseller_id' => $resellerId,
                        'group' => 'stripe',
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                        'label' => $stripeSettingLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                        'description' => $stripeSettingDescriptions[$key] ?? null,
                        'type' => $key === 'stripe_test_mode' ? 'boolean' : 
                                 (str_contains($key, 'secret') || str_contains($key, 'webhook') ? 'password' : 'text'),
                    ]
                );
            }

            Log::info('Stripe configuration updated', [
                'reseller_id' => $resellerId,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stripe configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating Stripe config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating Stripe configuration'
            ], 500);
        }
    }

    /**
     * Test Stripe configuration
     */
    public function testConfig(Request $request): JsonResponse
    {
        try {
            $resellerId = config('reseller.id');
            
            if (!$resellerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reseller context found'
                ], 400);
            }

            // Get secret key from request or from stored settings
            $secretKey = $request->input('stripe_secret_key');
            
            if (!$secretKey) {
                // Try to get from stored settings
                $storedSecretKey = ResellerSetting::where('reseller_id', $resellerId)
                    ->where('group', 'stripe')
                    ->where('key', 'stripe_secret_key')
                    ->value('value');
                
                if (!$storedSecretKey) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No Stripe secret key found. Please provide a secret key or save your configuration first.'
                    ], 400);
                }
                
                $secretKey = $storedSecretKey;
            }

            // Validate secret key format
            if (!preg_match('/^sk_(test_|live_)/', $secretKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Stripe secret key format. Secret keys should start with sk_test_ or sk_live_'
                ], 400);
            }

            // Temporarily set Stripe configuration for testing
            $originalKey = config('stripe.secret_key');
            \Stripe\Stripe::setApiKey($secretKey);

            try {
                // Test API key by making a simple API call
                $account = \Stripe\Account::retrieve();
                Log::info('Stripe account: ' . json_encode($account));
                
                return response()->json([
                    'success' => true,
                    'message' => 'Stripe configuration is valid',
                    'data' => [
                        'account_id' => $account->id,
                        'country' => $account->country,
                        'type' => $account->type,
                        'email' => $account->email ?? null,
                        'charges_enabled' => $account->charges_enabled ?? false,
                        'payouts_enabled' => $account->payouts_enabled ?? false,
                        'business_name' => $account->business_profile->name ?? $account->settings->dashboard->display_name ?? null,
                        'business_type' => $account->business_profile->type ?? null,
                        'business_url' => $account->business_profile->url ?? null,
                    ]
                ]);
            } catch (\Stripe\Exception\AuthenticationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Stripe secret key: ' . $e->getMessage()
                ], 400);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Stripe request: ' . $e->getMessage()
                ], 400);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error testing Stripe configuration: ' . $e->getMessage()
                ], 400);
            } finally {
                // Restore original API key
                \Stripe\Stripe::setApiKey($originalKey);
            }
        } catch (\Exception $e) {
            Log::error('Error testing Stripe config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error testing Stripe configuration'
            ], 500);
        }
    }

    /**
     * Get public Stripe configuration for frontend
     */
    public function getPublicConfig(): JsonResponse
    {
        try {
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
            $stripeSettings = ResellerSetting::where('reseller_id', $resellerId)
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
        } catch (\Exception $e) {
            Log::error('Error getting public Stripe config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving public Stripe configuration'
            ], 500);
        }
    }

    /**
     * Create a Stripe subscription for a reseller
     */
    public function createSubscription(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reseller_id' => 'required|exists:resellers,id',
                'package_id' => 'required|exists:reseller_packages,id',
                'payment_method_id' => 'required|string',
                'billing_interval' => 'required|string|in:month,year',
                'trial_days' => 'nullable|integer|min:0|max:365',
            ]);

            $reseller = Reseller::findOrFail($validated['reseller_id']);
            $package = ResellerPackage::findOrFail($validated['package_id']);

            // Check if reseller already has an active subscription
            if ($reseller->hasActiveSubscription()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller already has an active subscription'
                ], 400);
            }

            // Calculate pricing
            $amount = $validated['billing_interval'] === 'year' ? $package->yearly_price : $package->price;
            $amountInCents = $amount * 100;

            // Create Stripe customer if not exists
            $customer = $this->stripeService->createResellerCustomer([
                'email' => $reseller->company_email,
                'name' => $reseller->org_name,
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'is_reseller' => 'true'
                ]
            ], $reseller->id);

            // Create Stripe subscription
            $subscriptionData = [
                'customer' => $customer['id'],
                'items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $package->name,
                            'description' => $package->description,
                        ],
                        'unit_amount' => $amountInCents,
                        'recurring' => [
                            'interval' => $validated['billing_interval']
                        ],
                    ],
                ]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'package_id' => $package->id,
                    'is_reseller_subscription' => 'true',
                    'billing_interval' => $validated['billing_interval']
                ]
            ];

            // Add trial period if specified
            if (isset($validated['trial_days']) && $validated['trial_days'] > 0) {
                $subscriptionData['trial_period_days'] = $validated['trial_days'];
            }

            $stripeSubscription = $this->stripeService->createResellerSubscription($subscriptionData, $reseller->id);

            // Create local reseller subscription with safe timestamp conversion
            $periodStart = $this->safeTimestampConversion($stripeSubscription['current_period_start']);
            $periodEnd = $this->safeTimestampConversion($stripeSubscription['current_period_end']);
            $trialEnd = $stripeSubscription['trial_end'] ? $this->safeTimestampConversion($stripeSubscription['trial_end']) : null;

            $localSubscription = ResellerSubscription::create([
                'reseller_id' => $reseller->id,
                'reseller_package_id' => $package->id,
                'status' => 'pending',
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'trial_ends_at' => $trialEnd,
                'stripe_subscription_id' => $stripeSubscription['id'],
                'stripe_customer_id' => $customer['id'],
                'metadata' => [
                    'billing_interval' => $validated['billing_interval'],
                    'stripe_metadata' => $stripeSubscription['metadata'] ?? [],
                ],
            ]);

            // Create transaction record
            $transaction = ResellerTransaction::create([
                'reseller_id' => $reseller->id,
                'reseller_package_id' => $package->id,
                'reseller_subscription_id' => $localSubscription->id,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => ResellerTransaction::STATUS_PENDING,
                'payment_method' => ResellerTransaction::PAYMENT_STRIPE,
                'external_transaction_id' => $stripeSubscription['id'],
                'billing_email' => $reseller->company_email,
                'billing_name' => $reseller->org_name,
                'type' => ResellerTransaction::TYPE_SUBSCRIPTION,
                'description' => "Reseller subscription to {$package->name} ({$validated['billing_interval']})",
                'metadata' => [
                    'stripe_subscription_id' => $stripeSubscription['id'],
                    'billing_interval' => $validated['billing_interval'],
                    'trial_days' => $validated['trial_days'] ?? 0,
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $localSubscription->load(['reseller', 'package']),
                    'transaction' => $transaction,
                    'stripe_subscription' => $stripeSubscription,
                    'client_secret' => $stripeSubscription['latest_invoice']['payment_intent']['client_secret'] ?? null,
                ],
                'message' => 'Reseller subscription created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating reseller subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a reseller subscription
     */
    public function cancelSubscription(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'subscription_id' => 'required|exists:reseller_subscriptions,id',
                'cancel_at_period_end' => 'boolean',
            ]);

            $subscription = ResellerSubscription::findOrFail($validated['subscription_id']);

            if ($subscription->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is already cancelled'
                ], 400);
            }

            // Cancel Stripe subscription
            $stripeSubscription = $this->stripeService->cancelSubscription(
                $subscription->stripe_subscription_id,
                $validated['cancel_at_period_end'] ?? true
            );

            // Update local subscription
            $subscription->update([
                'status' => $validated['cancel_at_period_end'] ? 'active' : 'cancelled',
                'cancelled_at' => $validated['cancel_at_period_end'] ? null : now(),
                'ends_at' => $validated['cancel_at_period_end'] ? $subscription->current_period_end : now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $subscription->load(['reseller', 'package']),
                'message' => 'Reseller subscription cancelled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelling reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling reseller subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a reseller subscription
     */
    public function updateSubscription(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'subscription_id' => 'required|exists:reseller_subscriptions,id',
                'package_id' => 'required|exists:reseller_packages,id',
                'proration_behavior' => 'string|in:create_prorations,always_invoice,none',
            ]);

            $subscription = ResellerSubscription::findOrFail($validated['subscription_id']);
            $newPackage = ResellerPackage::findOrFail($validated['package_id']);

            // Get current Stripe subscription to get item ID
            $currentStripeSubscription = $this->stripeService->getResellerSubscription($subscription->stripe_subscription_id, $subscription->reseller_id);
            
            // Update Stripe subscription
            $stripeSubscription = $this->stripeService->updateResellerSubscription(
                $subscription->stripe_subscription_id,
                [
                    'items' => [[
                        'id' => $currentStripeSubscription['items']['data'][0]['id'],
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $newPackage->name,
                                'description' => $newPackage->description,
                            ],
                            'unit_amount' => ($newPackage->price * 100),
                            'recurring' => [
                                'interval' => 'month'
                            ],
                        ],
                    ]],
                    'proration_behavior' => $validated['proration_behavior'] ?? 'create_prorations',
                    'metadata' => [
                        'reseller_id' => $subscription->reseller_id,
                        'package_id' => $newPackage->id,
                        'is_reseller_subscription' => 'true',
                    ]
                ],
                $subscription->reseller_id
            );

            // Update local subscription
            $subscription->update([
                'reseller_package_id' => $newPackage->id,
                'current_period_start' => Carbon::createFromTimestamp($stripeSubscription['current_period_start']),
                'current_period_end' => Carbon::createFromTimestamp($stripeSubscription['current_period_end']),
            ]);

            return response()->json([
                'success' => true,
                'data' => $subscription->load(['reseller', 'package']),
                'message' => 'Reseller subscription updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating reseller subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reseller subscription details
     */
    public function getSubscriptionDetails(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'subscription_id' => 'required|exists:reseller_subscriptions,id',
            ]);

            $subscription = ResellerSubscription::with(['reseller', 'package'])->findOrFail($validated['subscription_id']);

            // Get Stripe subscription details
            $stripeSubscription = $this->stripeService->getResellerSubscription($subscription->stripe_subscription_id, $subscription->reseller_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription,
                    'stripe_subscription' => $stripeSubscription,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting reseller subscription details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting subscription details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a payment link for reseller subscription
     */
    public function createPaymentLink(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reseller_id' => 'required|exists:resellers,id',
                'package_id' => 'required|exists:reseller_packages,id',
                'custom_amount' => 'nullable|numeric|min:0',
                'billing_interval' => 'required|string|in:month,year',
                'trial_days' => 'nullable|integer|min:0|max:365',
            ]);

            $reseller = Reseller::findOrFail($validated['reseller_id']);
            $package = ResellerPackage::findOrFail($validated['package_id']);

            // Calculate amount
            $amount = $validated['custom_amount'] ?? 
                     ($validated['billing_interval'] === 'year' ? $package->yearly_price : $package->price);

            // Create Stripe payment link
            $paymentLink = $this->stripeService->createResellerPaymentLink([
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $package->name,
                            'description' => $package->description,
                        ],
                        'unit_amount' => $amount * 100,
                        'recurring' => [
                            'interval' => $validated['billing_interval']
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'package_id' => $package->id,
                    'is_reseller_subscription' => 'true',
                    'billing_interval' => $validated['billing_interval'],
                    'custom_amount' => $validated['custom_amount'] ?? null,
                ],
                'allow_promotion_codes' => true,
                'billing_address_collection' => 'required',
                'success_url' => config('app.url') . '/reseller/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . '/reseller/subscription/cancel',
            ], $reseller->id);

            // Create pending subscription record
            $subscription = ResellerSubscription::create([
                'reseller_id' => $reseller->id,
                'reseller_package_id' => $package->id,
                'status' => 'pending',
                'current_period_start' => now(),
                'current_period_end' => $validated['billing_interval'] === 'year' ? now()->addYear() : now()->addMonth(),
                'trial_ends_at' => isset($validated['trial_days']) ? now()->addDays($validated['trial_days']) : null,
                'payment_link_id' => $paymentLink['id'],
                'payment_link_url' => $paymentLink['url'],
                'custom_amount' => $validated['custom_amount'],
                'metadata' => [
                    'billing_interval' => $validated['billing_interval'],
                    'trial_days' => $validated['trial_days'] ?? 0,
                    'stripe_payment_link_id' => $paymentLink['id'],
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->load(['reseller', 'package']),
                    'payment_link' => $paymentLink,
                ],
                'message' => 'Payment link created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating reseller payment link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment link: ' . $e->getMessage()
            ], 500);
        }
    }

}