<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\ResellerPackage;
use App\Models\ResellerSubscription;
use App\Models\ResellerTransaction;
use App\Services\ResellerUsageService;
use App\Services\StripeService;
use App\Services\ResellerUsageTracker;
use App\Notifications\ResellerPaymentLinkEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ResellerSubscriptionController extends Controller
{
    protected $usageService;
    protected $stripeService;
    protected $usageTracker;

    public function __construct(ResellerUsageService $usageService, StripeService $stripeService, ResellerUsageTracker $usageTracker)
    {
        $this->usageService = $usageService;
        $this->stripeService = $stripeService;
        $this->usageTracker = $usageTracker;
    }

    /**
     * Display a listing of reseller subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ResellerSubscription::with(['reseller', 'package']);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('reseller', function ($q) use ($search) {
                    $q->where('org_name', 'LIKE', "%{$search}%")
                      ->orWhere('company_email', 'LIKE', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Apply reseller filter
            if ($request->has('reseller_id') && !empty($request->reseller_id)) {
                $query->where('reseller_id', $request->reseller_id);
            }

            // Apply package filter
            if ($request->has('package_id') && !empty($request->package_id)) {
                $query->where('reseller_package_id', $request->package_id);
            }

            $subscriptions = $query->orderBy('created_at', 'desc')->paginate(15);
            
            // Calculate statistics
            $stats = [
                'total_subscriptions' => ResellerSubscription::count(),
                'active_subscriptions' => ResellerSubscription::where('status', 'active')->count(),
                'pending_subscriptions' => ResellerSubscription::where('status', 'pending')->count(),
                'cancelled_subscriptions' => ResellerSubscription::where('status', 'cancelled')->count(),
                'expired_subscriptions' => ResellerSubscription::where('status', 'expired')->count(),
                'trial_subscriptions' => ResellerSubscription::where('status', 'trial')->count(),
                'total_revenue' => ResellerSubscription::where('status', 'active')->sum('custom_amount') ?? 0,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $subscriptions->getCollection(),
                'meta' => [
                    'total' => $subscriptions->total(),
                    'per_page' => $subscriptions->perPage(),
                    'current_page' => $subscriptions->currentPage(),
                    'last_page' => $subscriptions->lastPage(),
                ],
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller subscriptions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reseller subscriptions'
            ], 500);
        }
    }

    /**
     * Store a newly created reseller subscription
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reseller_id' => 'required|exists:resellers,id',
                'reseller_package_id' => 'required|exists:reseller_packages,id',
                'status' => 'required|string|in:active,pending,trial,cancelled,expired',
                'trial_ends_at' => 'nullable|date|after:now',
                'current_period_start' => 'required|date',
                'current_period_end' => 'required|date|after:current_period_start',
                'custom_amount' => 'nullable|numeric|min:0',
                'billing_interval' => 'required|string|in:monthly,yearly',
                'metadata' => 'nullable|array',
                'stripe_customer_id' => 'nullable|string',
                'stripe_subscription_id' => 'nullable|string',
            ]);

            // If Stripe IDs are provided, auto-sync from Stripe
            if ($validated['stripe_customer_id'] && $validated['stripe_subscription_id']) {
                return $this->syncFromStripe($request);
            }

            // Check if reseller already has an active subscription
            $existingSubscription = ResellerSubscription::where('reseller_id', $validated['reseller_id'])
                ->whereIn('status', ['active', 'pending'])
                ->first();

            if ($existingSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller already has an active subscription'
                ], 400);
            }

            // Get reseller and package details
            $reseller = Reseller::findOrFail($validated['reseller_id']);
            $package = ResellerPackage::findOrFail($validated['reseller_package_id']);
            
            // Calculate amount based on billing interval
            $billingInterval = $validated['billing_interval'];
            $amount = $validated['custom_amount'] ?? ($billingInterval === 'yearly' ? $package->yearly_price : $package->price);
            
            // Validate yearly pricing if yearly billing is selected
            if ($billingInterval === 'yearly' && !$validated['custom_amount'] && !$package->yearly_price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yearly pricing is not available for this package. Please set a custom amount or choose monthly billing.'
                ], 400);
            }
            
            // Create Stripe checkout session data
            $checkoutData = [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $package->name,
                            'description' => $package->description,
                        ],
                        'unit_amount' => (int) ($amount * 100), // Convert to cents
                        'recurring' => [
                            'interval' => $billingInterval === 'yearly' ? 'year' : 'month'
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => config('app.url') . '/reseller/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . '/reseller/subscription/cancel',
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'package_id' => $package->id,
                    'is_reseller_subscription' => 'true',
                    'billing_interval' => $billingInterval,
                    'custom_amount' => $validated['custom_amount'] ?? null,
                    'created_by_admin' => 'true',
                ],
                'allow_promotion_codes' => true,
                'billing_address_collection' => 'required',
                'subscription_data' => [
                    'metadata' => [
                        'reseller_id' => $reseller->id,
                        'package_id' => $package->id,
                        'is_reseller_subscription' => 'true',
                        'billing_interval' => $billingInterval,
                        'custom_amount' => $validated['custom_amount'] ?? null,
                        'created_by_admin' => 'true',
                    ],
                ],
            ];

            // Create checkout session using StripeService
            $checkoutSession = $this->stripeService->createResellerCheckoutSession($checkoutData, $reseller->id);
            Log::info('Checkout session created', ['checkout_session' => $checkoutSession]);
            
            if (!$checkoutSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create checkout session'
                ], 500);
            }

            // Create subscription with checkout session data
            $subscriptionData = array_merge($validated, [
                'status' => 'pending', // Set to pending until payment is completed
                'stripe_checkout_session_id' => $checkoutSession['id'],
                'checkout_session_url' => $checkoutSession['url'],
                'stripe_subscription_id' => $checkoutSession['subscription_id'],
                'custom_amount' => $validated['custom_amount'] ?? null,
                'billing_interval' => $billingInterval,
                'metadata' => array_merge($validated['metadata'] ?? [], [
                    'billing_interval' => $billingInterval,
                    'stripe_checkout_session_id' => $checkoutSession['id'],
                    'created_by_admin' => 'true',
                ]),
            ]);

            $subscription = ResellerSubscription::create($subscriptionData);
            $this->createUsagePeriod($subscription);

            // Send checkout session email to reseller
            try {
                $reseller->notify(new ResellerPaymentLinkEmail($reseller, $subscription, $checkoutSession['url']));
                
                Log::info('Checkout session email sent to reseller', [
                    'reseller_id' => $reseller->id,
                    'subscription_id' => $subscription->id,
                    'checkout_session_id' => $checkoutSession['id']
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send checkout session email', [
                    'reseller_id' => $reseller->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->load(['reseller', 'package']),
                    'checkout_session' => $checkoutSession,
                ],
                'message' => 'Reseller subscription created successfully. Checkout session link has been sent to the reseller via email.'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors specifically
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating reseller subscription'
            ], 500);
        }
    }

    /**
     * Display the specified reseller subscription
     */
    public function show(ResellerSubscription $resellerSubscription): JsonResponse
    {
        $resellerSubscription->load(['reseller', 'package']);
        
        // Get usage statistics
        $usageStats = $this->usageService->getUsageStatistics($resellerSubscription->reseller);

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $resellerSubscription,
                'usage_statistics' => $usageStats
            ]
        ]);
    }

    /**
     * Update the specified reseller subscription
     */
    public function update(Request $request, ResellerSubscription $resellerSubscription): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reseller_package_id' => 'sometimes|required|exists:reseller_packages,id',
                'status' => 'sometimes|required|string|in:active,pending,trial,cancelled,expired',
                'trial_ends_at' => 'nullable|date',
                'current_period_start' => 'sometimes|required|date',
                'current_period_end' => 'sometimes|required|date|after:current_period_start',
                'cancelled_at' => 'nullable|date',
                'ends_at' => 'nullable|date',
                'custom_amount' => 'nullable|numeric|min:0',
                'billing_interval' => 'sometimes|required|string|in:monthly,yearly',
                'metadata' => 'nullable|array',
            ]);

            // If cancelling subscription, set cancelled_at
            if (isset($validated['status']) && $validated['status'] === 'cancelled' && !$resellerSubscription->cancelled_at) {
                $validated['cancelled_at'] = now();
            }

            $resellerSubscription->update($validated);

            return response()->json([
                'success' => true,
                'data' => $resellerSubscription->load(['reseller', 'package']),
                'message' => 'Reseller subscription updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors specifically
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating reseller subscription'
            ], 500);
        }
    }

    /**
     * Cancel a reseller subscription
     */
    public function cancel(ResellerSubscription $resellerSubscription): JsonResponse
    {
        try {
            if ($resellerSubscription->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is already cancelled'
                ], 400);
            }

            $resellerSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'ends_at' => $resellerSubscription->current_period_end
            ]);

            return response()->json([
                'success' => true,
                'data' => $resellerSubscription->load(['reseller', 'package']),
                'message' => 'Reseller subscription cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling reseller subscription'
            ], 500);
        }
    }

    /**
     * Reactivate a reseller subscription
     */
    public function reactivate(ResellerSubscription $resellerSubscription): JsonResponse
    {
        try {
            if ($resellerSubscription->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is already active'
                ], 400);
            }

            $resellerSubscription->update([
                'status' => 'active',
                'cancelled_at' => null,
                'ends_at' => null
            ]);

            return response()->json([
                'success' => true,
                'data' => $resellerSubscription->load(['reseller', 'package']),
                'message' => 'Reseller subscription reactivated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error reactivating reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error reactivating reseller subscription'
            ], 500);
        }
    }

    /**
     * Get usage statistics for all resellers
     */
    public function usageStatistics(): JsonResponse
    {
        try {
            $usageData = $this->usageService->getAllResellersUsageStatistics();

            return response()->json([
                'success' => true,
                'data' => $usageData
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching usage statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching usage statistics'
            ], 500);
        }
    }

    /**
     * Resend checkout session link for a pending subscription
     */
    public function resendPaymentLink(ResellerSubscription $resellerSubscription): JsonResponse
    {
        try {
            if ($resellerSubscription->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is not in pending status'
                ], 400);
            }

            // Check for checkout session URL first, then fallback to payment link URL
            $paymentUrl = $resellerSubscription->checkout_session_url ?? $resellerSubscription->payment_link_url;
            
            if (!$paymentUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment link found for this subscription'
                ], 400);
            }

            // Send payment link email to reseller
            $reseller = $resellerSubscription->reseller;
            $reseller->notify(new ResellerPaymentLinkEmail($reseller, $resellerSubscription, $paymentUrl));

            Log::info('Payment link resent to reseller', [
                'reseller_id' => $reseller->id,
                'subscription_id' => $resellerSubscription->id,
                'checkout_session_id' => $resellerSubscription->stripe_checkout_session_id,
                'payment_link_id' => $resellerSubscription->payment_link_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment link has been resent to the reseller'
            ]);
        } catch (\Exception $e) {
            Log::error('Error resending payment link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error resending payment link'
            ], 500);
        }
    }

    /**
     * Create usage period for existing subscription (for testing)
     */
    public function createUsagePeriod(ResellerSubscription $resellerSubscription): JsonResponse
    {
        try {
            // if ($resellerSubscription->status !== 'active') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Subscription must be active to create usage period'
            //     ], 400);
            // }

            // Check if usage period already exists
            $existingPeriod = \App\Models\ResellerUsagePeriod::where('reseller_subscription_id', $resellerSubscription->id)
                ->where('period_start', '<=', now())
                ->where('period_end', '>=', now())
                ->first();

            if ($existingPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usage period already exists for this subscription'
                ], 400);
            }

            // Create usage period
            $usagePeriod = $this->usageTracker->createUsagePeriod($resellerSubscription);

            Log::info('Usage period created manually for testing', [
                'subscription_id' => $resellerSubscription->id,
                'reseller_id' => $resellerSubscription->reseller_id,
                'usage_period_id' => $usagePeriod->id,
                'period_start' => $usagePeriod->period_start,
                'period_end' => $usagePeriod->period_end,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'usage_period' => $usagePeriod,
                    'subscription' => $resellerSubscription->load(['reseller', 'package'])
                ],
                'message' => 'Usage period created successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating usage period manually: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating usage period: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overage report for all resellers
     */
    public function overageReport(): JsonResponse
    {
        try {
            $resellers = Reseller::with(['activeSubscription.package'])->get();
            $overageData = [];

            foreach ($resellers as $reseller) {
                $overageInfo = $this->usageService->calculateOverageCharges($reseller);
                
                if ($overageInfo['overage_minutes'] > 0) {
                    $overageData[] = [
                        'reseller_id' => $reseller->id,
                        'reseller_name' => $reseller->org_name,
                        'reseller_email' => $reseller->company_email,
                        'package_name' => $reseller->activeSubscription?->package?->name ?? 'No Package',
                        'minutes_used' => $overageInfo['total_minutes_used'],
                        'minutes_limit' => $overageInfo['monthly_limit'],
                        'overage_minutes' => $overageInfo['overage_minutes'],
                        'overage_cost' => $overageInfo['overage_cost'],
                        'extra_per_minute_charge' => $overageInfo['extra_per_minute_charge'],
                        'billing_period_start' => $overageInfo['billing_period_start'],
                        'billing_period_end' => $overageInfo['billing_period_end'],
                    ];
                }
            }

            // Sort by overage cost descending
            usort($overageData, function($a, $b) {
                return $b['overage_cost'] <=> $a['overage_cost'];
            });

            return response()->json([
                'success' => true,
                'data' => $overageData
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating overage report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating overage report'
            ], 500);
        }
    }

    /**
     * Get Stripe customers for dropdown
     */
    public function getStripeCustomers(): JsonResponse
    {
        try {
            $customers = $this->stripeService->getAllCustomers();
            
            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching Stripe customers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Stripe customers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscriptions for a specific Stripe customer
     */
    public function getCustomerSubscriptions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|string'
            ]);

            $subscriptions = $this->stripeService->getCustomerSubscriptions($request->customer_id);
            
            return response()->json([
                'success' => true,
                'data' => $subscriptions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer subscriptions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer subscriptions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync subscription data from Stripe
     */
    public function syncFromStripe(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'stripe_customer_id' => 'required|string',
                'stripe_subscription_id' => 'required|string',
                'reseller_id' => 'required|exists:resellers,id',
                'reseller_package_id' => 'required|exists:reseller_packages,id'
            ]);

            $customerId = $request->stripe_customer_id;
            $subscriptionId = $request->stripe_subscription_id;
            $resellerId = $request->reseller_id;
            $packageId = $request->reseller_package_id;

            // Fetch subscription data from Stripe
            $stripeSubscription = $this->stripeService->getResellerSubscription($subscriptionId, $resellerId);
            Log::info('Stripe subscription data', ['stripeSubscription' => $stripeSubscription]);
            
            if (!$stripeSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found in Stripe or invalid subscription ID'
                ], 404);
            }

            // Fetch customer data from Stripe
            $stripeCustomer = $this->stripeService->getCustomer($customerId);
            
            if (!$stripeCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found in Stripe or invalid customer ID'
                ], 404);
            }

            // Create or update local subscription record
            $subscription = ResellerSubscription::updateOrCreate([
                'stripe_subscription_id' => $subscriptionId
            ], [
                'reseller_id' => $resellerId,
                'reseller_package_id' => $packageId,
                'stripe_customer_id' => $customerId,
                'status' => $stripeSubscription['status'],
                'current_period_start' => Carbon::createFromTimestamp($stripeSubscription['current_period_start']),
                'current_period_end' => Carbon::createFromTimestamp($stripeSubscription['current_period_end']),
                'trial_ends_at' => $stripeSubscription['trial_end'] ? Carbon::createFromTimestamp($stripeSubscription['trial_end']) : null,
                'metadata' => $stripeSubscription['metadata'] ?? null,
                'synced_from_stripe' => true,
                'synced_at' => Carbon::now()
            ]);

            // Create usage period if subscription is active
            if ($subscription->status === 'active') {
                try {
                    $usagePeriod = $this->usageTracker->createUsagePeriod($subscription);
                    
                    Log::info('Usage period created for synced subscription', [
                        'subscription_id' => $subscription->id,
                        'usage_period_id' => $usagePeriod->id,
                        'reseller_id' => $resellerId
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create usage period for synced subscription', [
                        'subscription_id' => $subscription->id,
                        'reseller_id' => $resellerId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Sync transactions/invoices from Stripe
            try {
                $transactions = $this->stripeService->getSubscriptionTransactions($subscriptionId);
                $syncedTransactionsCount = 0;
                
                foreach ($transactions as $stripeTransaction) {
                    // Check if transaction already exists
                    $existingTransaction = ResellerTransaction::where('external_transaction_id', $stripeTransaction['id'])->first();
                    
                    if (!$existingTransaction) {
                        // Map Stripe invoice status to our transaction status
                        $status = $this->mapStripeStatusToTransactionStatus($stripeTransaction['status'], $stripeTransaction['paid']);
                        
                        // Create transaction record
                        ResellerTransaction::create([
                            'reseller_id' => $resellerId,
                            'reseller_package_id' => $packageId,
                            'reseller_subscription_id' => $subscription->id,
                            'external_transaction_id' => $stripeTransaction['id'],
                            'amount' => $stripeTransaction['amount_paid'] / 100, // Convert from cents
                            'currency' => strtoupper($stripeTransaction['currency']),
                            'status' => $status,
                            'payment_method' => ResellerTransaction::PAYMENT_STRIPE,
                            'payment_method_id' => $stripeTransaction['payment_intent'] ?? null,
                            'billing_email' => $stripeTransaction['customer_email'] ?? $stripeCustomer['email'] ?? null,
                            'billing_name' => $stripeTransaction['customer_name'] ?? $stripeCustomer['name'] ?? null,
                            'billing_address' => $this->formatBillingAddress($stripeTransaction['customer_address'] ?? $stripeCustomer['address'] ?? null),
                            'billing_city' => $this->extractAddressField($stripeTransaction['customer_address'] ?? $stripeCustomer['address'] ?? null, 'city'),
                            'billing_state' => $this->extractAddressField($stripeTransaction['customer_address'] ?? $stripeCustomer['address'] ?? null, 'state'),
                            'billing_country' => $this->extractAddressField($stripeTransaction['customer_address'] ?? $stripeCustomer['address'] ?? null, 'country'),
                            'billing_postal_code' => $this->extractAddressField($stripeTransaction['customer_address'] ?? $stripeCustomer['address'] ?? null, 'postal_code'),
                            'payment_details' => [
                                'stripe_invoice_id' => $stripeTransaction['id'],
                                'stripe_customer_id' => $stripeTransaction['customer_id'],
                                'stripe_subscription_id' => $stripeTransaction['subscription_id'],
                                'hosted_invoice_url' => $stripeTransaction['hosted_invoice_url'],
                                'invoice_pdf' => $stripeTransaction['invoice_pdf'],
                                'period_start' => $stripeTransaction['period_start'],
                                'period_end' => $stripeTransaction['period_end'],
                                'billing_reason' => $stripeTransaction['billing_reason'] ?? null,
                                'collection_method' => $stripeTransaction['collection_method'] ?? null,
                                'due_date' => $stripeTransaction['due_date'] ?? null,
                                'subtotal' => $stripeTransaction['subtotal'] ?? null,
                                'total' => $stripeTransaction['total'] ?? null,
                                'tax' => $stripeTransaction['tax'] ?? null,
                                'discount' => $stripeTransaction['discount'] ?? null,
                                'customer_tax_exempt' => $stripeTransaction['customer_tax_exempt'] ?? null,
                                'lines' => $stripeTransaction['lines'] ?? [],
                                'payment_intent_details' => $stripeTransaction['payment_intent_details'] ?? null,
                                'customer_phone' => $stripeCustomer['phone'] ?? null,
                                'customer_shipping' => $stripeCustomer['shipping'] ?? null,
                                'customer_tax_ids' => $stripeCustomer['tax_ids'] ?? null,
                                'customer_invoice_settings' => $stripeCustomer['invoice_settings'] ?? null
                            ],
                            'type' => ResellerTransaction::TYPE_SUBSCRIPTION,
                            'description' => "Stripe subscription payment - {$stripeTransaction['id']}",
                            'metadata' => $stripeTransaction['metadata'] ?? [],
                            'processed_at' => $stripeTransaction['paid'] ? Carbon::createFromTimestamp($stripeTransaction['created']) : null,
                            'failed_at' => !$stripeTransaction['paid'] ? Carbon::createFromTimestamp($stripeTransaction['created']) : null,
                        ]);
                        
                        $syncedTransactionsCount++;
                    }
                }
                
                Log::info('Transactions synced from Stripe', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscriptionId,
                    'transactions_synced' => $syncedTransactionsCount,
                    'total_transactions' => count($transactions)
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to sync transactions from Stripe', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscriptionId,
                    'error' => $e
                ]);
                // Don't fail the entire sync if transaction sync fails
            }

            Log::info('Subscription synced from Stripe', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscriptionId,
                'stripe_customer_id' => $customerId,
                'reseller_id' => $resellerId,
                'status' => $stripeSubscription['status']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription synced successfully from Stripe',
                'data' => $subscription->load(['reseller', 'package'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing subscription from Stripe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync subscription from Stripe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format billing address from Stripe data
     */
    private function formatBillingAddress($address): ?string
    {
        if (!$address) {
            return null;
        }

        // Handle StripeObject by converting to array
        if (is_object($address)) {
            $address = (array) $address;
        }

        if (!is_array($address)) {
            return null;
        }

        $parts = array_filter([
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Extract specific field from address
     */
    private function extractAddressField($address, string $field): ?string
    {
        if (!$address) {
            return null;
        }

        // Handle StripeObject by converting to array
        if (is_object($address)) {
            $address = (array) $address;
        }

        if (!is_array($address)) {
            return null;
        }

        return $address[$field] ?? null;
    }

    /**
     * Map Stripe invoice status to transaction status
     */
    private function mapStripeStatusToTransactionStatus(string $stripeStatus, bool $paid): string
    {
        if ($paid) {
            return ResellerTransaction::STATUS_COMPLETED;
        }
        
        switch ($stripeStatus) {
            case 'draft':
            case 'open':
                return ResellerTransaction::STATUS_PENDING;
            case 'paid':
                return ResellerTransaction::STATUS_COMPLETED;
            case 'void':
            case 'uncollectible':
                return ResellerTransaction::STATUS_FAILED;
            default:
                return ResellerTransaction::STATUS_PENDING;
        }
    }

    /**
     * Delete a reseller subscription
     */
    public function destroy(ResellerSubscription $resellerSubscription): JsonResponse
    {
        try {
            // Check if subscription is active
            if ($resellerSubscription->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete active subscription. Please cancel it first.'
                ], 422);
            }

            // Log the deletion
            Log::info('Reseller subscription deleted by super admin', [
                'subscription_id' => $resellerSubscription->id,
                'reseller_id' => $resellerSubscription->reseller_id,
                'status' => $resellerSubscription->status,
                'admin_id' => Auth::id()
            ]);

            $resellerSubscription->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reseller subscription deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting reseller subscription'
            ], 500);
        }
    }
}
