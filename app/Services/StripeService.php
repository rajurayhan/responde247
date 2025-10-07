<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Transaction;
use App\Models\SubscriptionPackage;
use App\Models\ResellerSetting;
use App\Notifications\ResellerSubscriptionActivated;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\PaymentIntent;
use Stripe\Product;
use Stripe\PaymentMethod;
use Stripe\PaymentLink;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Carbon\Carbon;

class StripeService
{
    protected $resellerId;
    protected $stripeConfig;

    public function __construct()
    {
        $this->resellerId = config('reseller.id');
        $this->loadStripeConfig();
    }

    /**
     * Load Stripe configuration for current reseller
     */
    protected function loadStripeConfig()
    {
        if (!$this->resellerId) {
            // Fallback to global config if no reseller context
            $this->stripeConfig = [
                'secret_key' => config('stripe.secret_key'),
                'api_version' => config('stripe.api_version'),
                'currency' => config('stripe.currency'),
                'test_mode' => config('stripe.test_mode'),
            ];
        } else {
            // Get reseller-specific Stripe settings
            $stripeSettings = ResellerSetting::where('reseller_id', $this->resellerId)
                ->where('group', 'stripe')
                ->get()
                ->pluck('value', 'key')
                ->toArray();

            $this->stripeConfig = [
                'secret_key' => $stripeSettings['stripe_secret_key'] ?? config('stripe.secret_key'),
                'api_version' => $stripeSettings['stripe_api_version'] ?? config('stripe.api_version'),
                'currency' => $stripeSettings['stripe_currency'] ?? config('stripe.currency'),
                'test_mode' => $stripeSettings['stripe_test_mode'] ?? config('stripe.test_mode'),
            ];
        }

        // Set Stripe configuration
        Stripe::setApiKey($this->stripeConfig['secret_key']);
        Stripe::setApiVersion($this->stripeConfig['api_version']);
    }

    /**
     * Get current Stripe configuration
     */
    public function getConfig()
    {
        return $this->stripeConfig;
    }

    /**
     * Get webhook secret for current reseller
     * Priority: Environment variable > Database setting > Global config
     */
    public function getWebhookSecret(): ?string
    {
        if (!$this->resellerId) {
            // Fallback to global config if no reseller context
            return config('stripe.webhook_secret');
        }

        // First, try to get reseller-specific webhook secret from environment
        $envWebhookSecret = env('STRIPE_WEBHOOK_SECRET_' . strtoupper($this->resellerId));
        if ($envWebhookSecret) {
            Log::info('Using environment webhook secret for reseller', ['reseller_id' => $this->resellerId]);
            return $envWebhookSecret;
        }

        // Second, try to get from database settings
        $dbWebhookSecret = ResellerSetting::where('reseller_id', $this->resellerId)
            ->where('key', 'stripe_webhook_secret')
            ->value('value');

        if ($dbWebhookSecret) {
            Log::info('Using database webhook secret for reseller', ['reseller_id' => $this->resellerId]);
            return $dbWebhookSecret;
        }

        // Finally, fallback to global config
        Log::info('Using global webhook secret for reseller', ['reseller_id' => $this->resellerId]);
        return config('stripe.webhook_secret');
    }

    /**
     * Create a Stripe customer for reseller
     */
    public function createResellerCustomer(array $customerData, string $resellerId): ?array
    {
        try {
            $customer = Customer::create([
                'email' => $customerData['email'],
                'name' => $customerData['name'],
                'metadata' => $customerData['metadata'] ?? []
            ]);

            return [
                'id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name
            ];
        } catch (\Exception $e) {
            Log::error('Error creating reseller customer: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a Stripe customer
     */
    public function createCustomer(User $user): ?string
    {
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                    'reseller_id' => $user->reseller_id,
                    'reseller_domain' => app('currentReseller')->domain ?? null,
                    'created_at' => $user->created_at->toISOString(),
                ],
            ]);

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe customer creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a Stripe subscription for reseller
     */
    public function createResellerSubscription(array $subscriptionData, string $resellerId): ?array
    {
        try {
            $subscription = Subscription::create($subscriptionData);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_end' => $subscription->trial_end,
                'customer' => $subscription->customer,
                'latest_invoice' => $subscription->latest_invoice,
                'metadata' => $subscription->metadata
            ];
        } catch (\Exception $e) {
            Log::error('Error creating reseller subscription: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a Stripe subscription
     */
    public function createSubscription(User $user, SubscriptionPackage $package, string $paymentMethodId = null, string $billingInterval = 'monthly', array $additionalMetadata = []): ?array
    {
        try {
            // Get or create Stripe customer
            $customerId = $this->getOrCreateCustomer($user);
            if (!$customerId) {
                throw new \Exception('Failed to create or retrieve customer');
            }

            // If payment method is provided, validate and attach it to the customer first
            if ($paymentMethodId) {
                $this->validateAndAttachPaymentMethod($customerId, $paymentMethodId);
            }

            // Determine the price and interval based on billing interval
            $price = $billingInterval === 'yearly' ? $package->yearly_price : $package->price;
            $interval = $billingInterval === 'yearly' ? 'year' : 'month';

            // Create subscription parameters
            $subscriptionData = [
                'customer' => $customerId,
                'items' => [
                    [
                        'price_data' => [
                            'currency' => $this->stripeConfig['currency'],
                            'product' => $this->getOrCreateProduct($package),
                            'unit_amount' => (int) ($price * 100), // Convert to cents
                            'recurring' => [
                                'interval' => $interval,
                            ],
                        ],
                    ],
                ],
                'metadata' => array_merge([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'billing_interval' => $billingInterval,
                ], $additionalMetadata),
            ];

            // Add payment method if provided
            if ($paymentMethodId) {
                $subscriptionData['default_payment_method'] = $paymentMethodId;
            } else {
                // Only use payment_behavior when no payment method is provided
                $subscriptionData['payment_behavior'] = 'default_incomplete';
                $subscriptionData['payment_settings'] = [
                    'payment_method_types' => ['card'],
                    'save_default_payment_method' => 'on_subscription',
                ];
                $subscriptionData['expand'] = ['latest_invoice.payment_intent'];
            }

            $subscription = Subscription::create($subscriptionData);

            return [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'status' => $subscription->status,
                'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end),
                'trial_end' => $subscription->trial_end ? Carbon::createFromTimestamp($subscription->trial_end) : null,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_method_id' => $paymentMethodId,
                'error_code' => $e->getStripeCode(),
                'error_type' => $e->getStripeCode(),
            ]);
            
            // Provide more specific error messages
            $errorMessage = 'Failed to create subscription';
            if (str_contains($e->getMessage(), 'No such PaymentMethod')) {
                $errorMessage = 'The payment method is invalid or has expired. Please try again with a different payment method.';
            } elseif (str_contains($e->getMessage(), 'card was declined')) {
                $errorMessage = 'Your card was declined. Please try a different payment method.';
            } elseif (str_contains($e->getMessage(), 'insufficient funds')) {
                $errorMessage = 'Insufficient funds. Please try a different payment method.';
            }
            
            throw new \Exception($errorMessage);
        }
    }

    /**
     * Create a payment intent for one-time payments
     */
    public function createPaymentIntent(User $user, float $amount, string $currency = 'usd', array $metadata = []): ?array
    {
        try {
            $customerId = $this->getOrCreateCustomer($user);

            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => $currency,
                'customer' => $customerId,
                'metadata' => array_merge($metadata, [
                    'user_id' => $user->id,
                ]),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a payment link for custom subscription
     */
    public function createPaymentLink(User $user, float $amount, string $currency = 'usd', array $metadata = []): ?array
    {
        try {
            $customerId = $this->getOrCreateCustomer($user);

            $paymentLink = \Stripe\PaymentLink::create([
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => 'Custom Subscription',
                                'description' => 'Custom subscription payment',
                            ],
                            'unit_amount' => (int) ($amount * 100), // Convert to cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'metadata' => array_merge($metadata, [
                    'user_id' => $user->id,
                    'reseller_id' => $user->reseller_id,
                    'reseller_domain' => app('currentReseller')->domain ?? null,
                    'customer_id' => $customerId,
                    'type' => 'custom_subscription',
                ]),
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => [
                        'url' => config('app.url') . '/subscription/success',
                    ],
                ],
            ]);

            return [
                'payment_link_id' => $paymentLink->id,
                'payment_link_url' => $paymentLink->url,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment link creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel a Stripe subscription
     */
    public function cancelSubscription(string $stripeSubscriptionId, bool $atPeriodEnd = true): bool
    {
        try {
            $subscription = Subscription::retrieve($stripeSubscriptionId);
            
            if ($atPeriodEnd) {
                $subscription->cancel_at_period_end = true;
            } else {
                $subscription->cancel();
            }
            
            $subscription->save();
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription cancellation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a Stripe subscription for reseller
     */
    public function updateResellerSubscription(string $subscriptionId, array $updateData, string $resellerId): ?array
    {
        try {
            $subscription = Subscription::retrieve($subscriptionId);
            $subscription->update($updateData);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_end' => $subscription->trial_end,
                'customer' => $subscription->customer,
                'metadata' => $subscription->metadata
            ];
        } catch (\Exception $e) {
            Log::error('Error updating reseller subscription: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a Stripe subscription for reseller
     */
    public function getResellerSubscription(string $subscriptionId, string $resellerId): ?array
    {
        try {
            $subscription = Subscription::retrieve($subscriptionId);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_end' => $subscription->trial_end,
                'customer' => $subscription->customer,
                'items' => $subscription->items,
                'metadata' => $subscription->metadata
            ];
        } catch (\Exception $e) {
            Log::error('Error getting reseller subscription: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel a Stripe subscription for reseller
     */
    public function cancelResellerSubscription(string $subscriptionId, bool $cancelAtPeriodEnd = true, string $resellerId = null): ?array
    {
        try {
            $subscription = Subscription::retrieve($subscriptionId);
            
            if ($cancelAtPeriodEnd) {
                $subscription->cancel_at_period_end = true;
                $subscription->save();
            } else {
                $subscription->cancel();
            }

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'canceled_at' => $subscription->canceled_at,
                'current_period_end' => $subscription->current_period_end
            ];
        } catch (\Exception $e) {
            Log::error('Error cancelling reseller subscription: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a checkout session for reseller subscription
     */
    public function createResellerCheckoutSession(array $checkoutData, string $resellerId): ?array
    {
        try {
            // Create checkout session
            $checkoutSession = Session::create($checkoutData);

            return [
                'id' => $checkoutSession->id,
                'url' => $checkoutSession->url,
                'subscription_id' => $checkoutSession->subscription->id ?? null,
                'payment_intent_id' => $checkoutSession->payment_intent->id ?? null,
                'metadata' => $checkoutSession->metadata
            ];
        } catch (\Exception $e) {
            Log::error('Error creating reseller checkout session: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a payment link for reseller (legacy method - kept for backward compatibility)
     */
    public function createResellerPaymentLink(array $paymentLinkData, string $resellerId): ?array
    {
        try {
            $paymentLink = PaymentLink::create($paymentLinkData);

            return [
                'id' => $paymentLink->id,
                'url' => $paymentLink->url,
                'active' => $paymentLink->active,
                'metadata' => $paymentLink->metadata
            ];
        } catch (\Exception $e) {
            Log::error('Error creating reseller payment link: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update subscription (upgrade/downgrade)
     */
    public function updateSubscription(string $stripeSubscriptionId, SubscriptionPackage $newPackage): bool
    {
        try {
            $subscription = Subscription::retrieve($stripeSubscriptionId);
            
            // Update the subscription item with new price
            $subscription->items->data[0]->price_data = [
                'currency' => $this->stripeConfig['currency'],
                'product_data' => [
                    'name' => $newPackage->name,
                    'description' => $newPackage->description,
                ],
                'unit_amount' => (int) ($newPackage->price * 100),
                'recurring' => [
                    'interval' => 'month',
                ],
            ];
            
            $subscription->save();
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve a Stripe subscription
     */
    public function getSubscription(string $stripeSubscriptionId): ?array
    {
        try {
            $subscription = Subscription::retrieve($stripeSubscriptionId);
            
            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end),
                'trial_end' => $subscription->trial_end ? Carbon::createFromTimestamp($subscription->trial_end) : null,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription retrieval failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get or create a Stripe customer for a user
     */
    private function getOrCreateCustomer(User $user): ?string
    {
        // Check if user already has a Stripe customer ID
        $existingSubscription = $user->subscriptions()->whereNotNull('stripe_customer_id')->first();
        if ($existingSubscription && $existingSubscription->stripe_customer_id) {
            return $existingSubscription->stripe_customer_id;
        }

        // Create new customer
        return $this->createCustomer($user);
    }

    /**
     * Get or create a Stripe product
     */
    private function getOrCreateProduct(SubscriptionPackage $package): string
    {
        try {
            // Check if product already exists
            $existingProducts = \Stripe\Product::all(['limit' => 1, 'active' => true]);
            foreach ($existingProducts->data as $product) {
                if ($product->name === $package->name) {
                    return $product->id;
                }
            }

            // Create new product
            $product = \Stripe\Product::create([
                'name' => $package->name,
                'description' => $package->description,
                'metadata' => [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                ],
            ]);

            return $product->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe product creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Attach a payment method to a customer
     */
    private function attachPaymentMethodToCustomer(string $customerId, string $paymentMethodId): void
    {
        try {
            // Attach the payment method to the customer
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);
            
            Log::info('Payment method attached to customer', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment method attachment failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate and attach a payment method to a customer
     */
    private function validateAndAttachPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        try {
            // Log the environment being used
            $isTestMode = config('stripe.test_mode', true);
            $apiKey = config('stripe.secret_key');
            $keyType = strpos($apiKey, 'sk_test_') === 0 ? 'TEST' : 'LIVE';
            
            Log::info('Validating payment method', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId,
                'environment' => $isTestMode ? 'TEST' : 'LIVE',
                'key_type' => $keyType,
                'api_key_prefix' => substr($apiKey, 0, 20) . '...'
            ]);
            
            // First, validate the payment method exists
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            
            // Check if payment method is already attached to this customer
            if ($paymentMethod->customer && $paymentMethod->customer !== $customerId) {
                throw new \Exception('Payment method is already attached to a different customer');
            }
            
            // Attach the payment method to the customer if not already attached
            if (!$paymentMethod->customer) {
                $paymentMethod->attach(['customer' => $customerId]);
            }
            
            Log::info('Payment method validated and attached to customer', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId,
                'payment_method_type' => $paymentMethod->type,
                'environment' => $isTestMode ? 'TEST' : 'LIVE'
            ]);
        } catch (ApiErrorException $e) {
            $errorCode = $e->getStripeCode();
            $errorType = $e->getStripeCode();
            $errorMessage = $e->getMessage();
            
            Log::error('Stripe payment method validation/attachment failed', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId,
                'error_code' => $errorCode,
                'error_type' => $errorType,
                'error_message' => $errorMessage,
                'environment' => config('stripe.test_mode', true) ? 'TEST' : 'LIVE',
                'api_key_prefix' => substr(config('stripe.secret_key'), 0, 20) . '...'
            ]);
            
            // Provide specific error messages based on the error
            if (str_contains($errorMessage, 'No such PaymentMethod')) {
                $envInfo = config('stripe.test_mode', true) ? 'test' : 'production';
                throw new \Exception("The payment method is invalid or has expired. This could be because you're using a {$envInfo} payment method with the wrong environment. Please try again with a different payment method.");
            } elseif (str_contains($errorMessage, 'already attached')) {
                throw new \Exception('This payment method is already in use. Please try a different payment method.');
            } elseif (str_contains($errorMessage, 'authentication_required')) {
                throw new \Exception('Payment requires authentication. Please complete the 3D Secure verification.');
            } elseif (str_contains($errorMessage, 'card_declined')) {
                throw new \Exception('Your card was declined. Please try a different payment method.');
            } else {
                throw new \Exception('Failed to validate payment method: ' . $errorMessage);
            }
        }
    }

    /**
     * Clean up invalid PaymentMethod references in transactions
     */
    public function cleanupInvalidPaymentMethods(): array
    {
        $results = [
            'checked' => 0,
            'cleaned' => 0,
            'errors' => []
        ];
        
        try {
            // Get all transactions with payment_method_id
            $transactions = \App\Models\Transaction::whereNotNull('payment_method_id')->get();
            
            foreach ($transactions as $transaction) {
                $results['checked']++;
                
                try {
                    // Try to retrieve the PaymentMethod from Stripe
                    $paymentMethod = PaymentMethod::retrieve($transaction->payment_method_id);
                    
                    // If successful, the PaymentMethod exists
                    Log::info('PaymentMethod validation successful', [
                        'transaction_id' => $transaction->id,
                        'payment_method_id' => $transaction->payment_method_id
                    ]);
                    
                } catch (ApiErrorException $e) {
                    // PaymentMethod doesn't exist, clean it up
                    if (str_contains($e->getMessage(), 'No such PaymentMethod')) {
                        $transaction->update([
                            'payment_method_id' => null,
                            'payment_details' => array_merge($transaction->payment_details ?? [], [
                                'cleaned_up' => true,
                                'cleanup_reason' => 'PaymentMethod not found in Stripe',
                                'cleanup_date' => now()->toISOString()
                            ])
                        ]);
                        
                        $results['cleaned']++;
                        Log::info('Cleaned up invalid PaymentMethod reference', [
                            'transaction_id' => $transaction->id,
                            'payment_method_id' => $transaction->payment_method_id
                        ]);
                    } else {
                        $results['errors'][] = [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
            
            Log::info('PaymentMethod cleanup completed', $results);
            return $results;
            
        } catch (\Exception $e) {
            Log::error('PaymentMethod cleanup failed: ' . $e->getMessage());
            $results['errors'][] = ['error' => $e->getMessage()];
            return $results;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);
            return true;
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process webhook event
     */
    public function processWebhookEvent(array $event, ?string $resellerId = null, string $configSource = 'reseller'): void
    {
        $eventType = $event['type'];
        
        // Set appropriate context based on config source
        if ($configSource === 'global') {
            // Use global Stripe config for reseller subscription payments
            $this->setGlobalContext();
        } else {
            // Use reseller-specific Stripe config for user subscription payments
            $this->setResellerContext($resellerId);
        }
        
        // Check if this is a reseller subscription webhook
        $isResellerWebhook = $this->isResellerWebhook($event['data']['object']);
        
        if ($isResellerWebhook) {
            $this->processResellerWebhookEvent($event);
        } else {
            $this->processUserWebhookEvent($event);
        }
    }

    /**
     * Process user subscription webhook events
     */
    private function processUserWebhookEvent(array $event): void
    {
        $eventType = $event['type'];
        
        switch ($eventType) {
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event['data']['object']);
                break;
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event['data']['object']);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event['data']['object']);
                break;
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event['data']['object']);
                break;
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event['data']['object']);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event['data']['object']);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event['data']['object']);
                break;
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event['data']['object']);
                break;
        }
    }

    /**
     * Process reseller subscription webhook events
     */
    private function processResellerWebhookEvent(array $event): void
    {
        $eventType = $event['type'];
        
        switch ($eventType) {
            case 'customer.subscription.created':
                $this->handleResellerSubscriptionCreated($event['data']['object']);
                break;
            case 'customer.subscription.updated':
                $this->handleResellerSubscriptionUpdated($event['data']['object']);
                break;
            case 'customer.subscription.deleted':
                $this->handleResellerSubscriptionDeleted($event['data']['object']);
                break;
            case 'invoice.payment_succeeded':
                $this->handleResellerPaymentSucceeded($event['data']['object']);
                break;
            case 'invoice.payment_failed':
                $this->handleResellerPaymentFailed($event['data']['object']);
                break;
            case 'payment_intent.succeeded':
                $this->handleResellerPaymentIntentSucceeded($event['data']['object']);
                break;
            case 'payment_intent.payment_failed':
                $this->handleResellerPaymentIntentFailed($event['data']['object']);
                break;
            case 'checkout.session.completed':
                $this->handleResellerCheckoutSessionCompleted($event['data']['object']);
                break;
        }
    }

    /**
     * Check if webhook is for reseller subscription
     */
    private function isResellerWebhook(array $eventData): bool
    {
        // Check metadata for reseller subscription indicator
        $metadata = $eventData['metadata'] ?? [];
        if (isset($metadata['is_reseller_subscription']) && $metadata['is_reseller_subscription'] === 'true') {
            return true;
        }
        
        // For payment intent events, check by customer ID
        if (isset($eventData['customer'])) {
            $customerId = $eventData['customer'];
            
            // Check if there's a reseller subscription for this customer (pending or active)
            $resellerSubscription = \App\Models\ResellerSubscription::where('stripe_customer_id', $customerId)
                ->whereIn('status', ['pending', 'active'])
                ->first();
            
            if ($resellerSubscription) {
                Log::info('Identified as reseller webhook by customer ID', [
                    'customer_id' => $customerId,
                    'subscription_id' => $resellerSubscription->id,
                    'reseller_id' => $resellerSubscription->reseller_id
                ]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Set reseller context for webhook processing
     */
    public function setResellerContext(?string $resellerId): void
    {
        if ($resellerId && $resellerId !== $this->resellerId) {
            $this->resellerId = $resellerId;
            $this->loadStripeConfig();
        }
    }

    /**
     * Set global context for webhook processing (reseller subscription payments)
     */
    public function setGlobalContext(): void
    {
        // Clear reseller context to use global config
        $this->resellerId = null;
        $this->loadStripeConfig();
        
        Log::info('Using global Stripe configuration for webhook processing');
    }

    /**
     * Handle subscription created webhook
     */
    private function handleSubscriptionCreated(array $subscriptionData): void
    {
        $userId = $subscriptionData['metadata']['user_id'] ?? null;
        $resellerId = $subscriptionData['metadata']['reseller_id'] ?? null;
        if (!$userId || !$resellerId) return;

        $user = User::find($userId);
        if (!$user || $user->reseller_id !== $resellerId) return;

        // Update local subscription
        $localSubscription = $user->subscriptions()->where('stripe_subscription_id', $subscriptionData['id'])->first();
        if ($localSubscription) {
            $localSubscription->update([
                'status' => $subscriptionData['status'],
                'current_period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
                'current_period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
                'trial_ends_at' => $subscriptionData['trial_end'] ? Carbon::createFromTimestamp($subscriptionData['trial_end']) : null,
            ]);
        } else {
            // Create local subscription if it doesn't exist
            $packageId = $subscriptionData['metadata']['package_id'] ?? null;
            if ($packageId) {
                UserSubscription::create([
                    'user_id' => $userId,
                    'subscription_package_id' => $packageId,
                    'status' => $subscriptionData['status'],
                    'current_period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
                    'current_period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
                    'trial_ends_at' => $subscriptionData['trial_end'] ? Carbon::createFromTimestamp($subscriptionData['trial_end']) : null,
                    'stripe_subscription_id' => $subscriptionData['id'],
                    'stripe_customer_id' => $subscriptionData['customer'],
                ]);
            }
        }

        Log::info('Subscription created/updated via webhook', [
            'subscription_id' => $subscriptionData['id'],
            'user_id' => $userId,
            'status' => $subscriptionData['status'],
            'period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
            'period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end'])
        ]);
    }

    /**
     * Handle subscription updated webhook
     */
    private function handleSubscriptionUpdated(array $subscriptionData): void
    {
        $this->handleSubscriptionCreated($subscriptionData);
    }

    /**
     * Handle subscription deleted webhook
     */
    private function handleSubscriptionDeleted(array $subscriptionData): void
    {
        $userId = $subscriptionData['metadata']['user_id'] ?? null;
        $resellerId = $subscriptionData['metadata']['reseller_id'] ?? null;
        if (!$userId || !$resellerId) return;

        $user = User::find($userId);
        if (!$user || $user->reseller_id !== $resellerId) return;

        // Update local subscription
        $localSubscription = $user->subscriptions()->where('stripe_subscription_id', $subscriptionData['id'])->first();
        if ($localSubscription) {
            $localSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
            ]);
        }
    }

    /**
     * Handle payment succeeded webhook
     */
    private function handlePaymentSucceeded(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) return;

        $localSubscription = UserSubscription::contentProtection()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();
        if ($localSubscription) {
            // Get the actual subscription data from Stripe to get correct period dates
            try {
                $stripeSubscription = Subscription::retrieve($subscriptionId);
                
                // Activate the subscription with correct period dates
                $localSubscription->update([
                    'status' => 'active',
                    'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                    'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                    'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to retrieve Stripe subscription for period dates', [
                    'subscription_id' => $subscriptionId,
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to invoice data if Stripe subscription retrieval fails
                $localSubscription->update([
                    'status' => 'active',
                    'current_period_start' => Carbon::createFromTimestamp($invoiceData['period_start'] ?? time()),
                    'current_period_end' => Carbon::createFromTimestamp($invoiceData['period_end'] ?? time() + 30 * 24 * 60 * 60),
                ]);
            }

            // Update transaction status
            $transaction = Transaction::where('user_subscription_id', $localSubscription->id)
                ->where('status', Transaction::STATUS_PENDING)
                ->latest()
                ->first();

            if ($transaction) {
                $transaction->update([
                    'status' => Transaction::STATUS_COMPLETED,
                    'external_transaction_id' => $invoiceData['id'],
                    'processed_at' => Carbon::now(),
                ]);

                // Send invoice email to user
                try {
                    $user = $localSubscription->user;
                    $user->notify(new \App\Notifications\SubscriptionInvoice($localSubscription, $transaction, $invoiceData));
                    
                    Log::info('Invoice email sent successfully', [
                        'user_id' => $user->id,
                        'subscription_id' => $localSubscription->id,
                        'transaction_id' => $transaction->id,
                        'invoice_id' => $invoiceData['id']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send invoice email', [
                        'user_id' => $localSubscription->user_id,
                        'subscription_id' => $localSubscription->id,
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Subscription activated via webhook', [
                'subscription_id' => $subscriptionId,
                'user_id' => $localSubscription->user_id,
                'invoice_id' => $invoiceData['id'],
                'period_start' => $localSubscription->current_period_start,
                'period_end' => $localSubscription->current_period_end
            ]);
        }
    }

    /**
     * Handle payment failed webhook
     */
    private function handlePaymentFailed(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) return;

        $localSubscription = UserSubscription::contentProtection()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();
        if ($localSubscription) {
            // Update transaction status
            $transaction = Transaction::contentProtection()
                ->where('user_subscription_id', $localSubscription->id)
                ->where('status', Transaction::STATUS_PENDING)
                ->latest()
                ->first();

            if ($transaction) {
                $transaction->update([
                    'status' => Transaction::STATUS_FAILED,
                    'failed_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Handle payment intent succeeded webhook
     */
    private function handlePaymentIntentSucceeded(array $paymentIntentData): void
    {
        $transaction = Transaction::contentProtection()
            ->where('external_transaction_id', $paymentIntentData['id'])
            ->first();
        if ($transaction) {
            $transaction->update([
                'status' => Transaction::STATUS_COMPLETED,
                'processed_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Handle payment intent failed webhook
     */
    private function handlePaymentIntentFailed(array $paymentIntentData): void
    {
        $transaction = Transaction::contentProtection()
            ->where('external_transaction_id', $paymentIntentData['id'])
            ->first();
        if ($transaction) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Handle checkout.session.completed webhook
     */
    private function handleCheckoutSessionCompleted(array $checkoutSessionData): void
    {
        $userId = $checkoutSessionData['metadata']['user_id'] ?? null;
        $resellerId = $checkoutSessionData['metadata']['reseller_id'] ?? null;
        if (!$userId || !$resellerId) return;

        // Check if this is a custom subscription payment
        $subscriptionId = $checkoutSessionData['metadata']['subscription_id'] ?? null;
        if ($subscriptionId) {
            // Find the custom subscription
            $subscription = \App\Models\UserSubscription::contentProtection()
                ->find($subscriptionId);
            if ($subscription && $subscription->status === 'pending') {
                // Activate the subscription
                $subscription->update([
                    'status' => 'active',
                ]);

                // Create transaction record for revenue tracking
                try {
                    $transaction = \App\Models\Transaction::create([
                        'user_id' => $userId,
                        'reseller_id' => $resellerId,
                        'user_subscription_id' => $subscription->id,
                        'amount' => $subscription->custom_amount,
                        'currency' => 'usd',
                        'status' => \App\Models\Transaction::STATUS_COMPLETED,
                        'payment_method' => 'stripe_payment_link',
                        'external_transaction_id' => $checkoutSessionData['payment_intent'] ?? $checkoutSessionData['id'],
                        'description' => "Custom subscription payment for {$subscription->package->name}",
                        'metadata' => [
                            'stripe_checkout_session_id' => $checkoutSessionData['id'],
                            'stripe_payment_intent_id' => $checkoutSessionData['payment_intent'] ?? null,
                            'subscription_id' => $subscription->id,
                            'package_id' => $subscription->package_id,
                            'custom_amount' => $subscription->custom_amount,
                            'billing_interval' => $checkoutSessionData['metadata']['billing_interval'] ?? 'monthly',
                            'duration_months' => $checkoutSessionData['metadata']['duration_months'] ?? 1,
                        ],
                        'processed_at' => Carbon::now(),
                    ]);

                    Log::info('Transaction record created for custom subscription', [
                        'transaction_id' => $transaction->id,
                        'subscription_id' => $subscriptionId,
                        'user_id' => $userId,
                        'amount' => $subscription->custom_amount,
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to create transaction record for custom subscription', [
                        'subscription_id' => $subscriptionId,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('Custom subscription activated via payment link', [
                    'subscription_id' => $subscriptionId,
                    'user_id' => $userId,
                    'amount' => $subscription->custom_amount,
                    'checkout_session_id' => $checkoutSessionData['id'],
                ]);
            }
        }
    }

    // ==================== RESELLER WEBHOOK HANDLERS ====================

    /**
     * Handle reseller subscription created webhook
     */
    private function handleResellerSubscriptionCreated(array $subscriptionData): void
    {
        $resellerId = $subscriptionData['metadata']['reseller_id'] ?? null;
        if (!$resellerId) return;

        $reseller = \App\Models\Reseller::find($resellerId);
        if (!$reseller) return;

        // Update local reseller subscription
        $localSubscription = $reseller->subscriptions()->where('stripe_subscription_id', $subscriptionData['id'])->first();
        if ($localSubscription) {
            $localSubscription->update([
                'status' => $subscriptionData['status'],
                'current_period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
                'current_period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
                'trial_ends_at' => $subscriptionData['trial_end'] ? Carbon::createFromTimestamp($subscriptionData['trial_end']) : null,
            ]);
        } else {
            // Create new reseller subscription
            $packageId = $subscriptionData['metadata']['package_id'] ?? null;
            if ($packageId) {
                $localSubscription = \App\Models\ResellerSubscription::create([
                    'reseller_id' => $resellerId,
                    'reseller_package_id' => $packageId,
                    'status' => $subscriptionData['status'],
                    'current_period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
                    'current_period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
                    'trial_ends_at' => $subscriptionData['trial_end'] ? Carbon::createFromTimestamp($subscriptionData['trial_end']) : null,
                    'stripe_subscription_id' => $subscriptionData['id'],
                    'stripe_customer_id' => $subscriptionData['customer'],
                ]);
            }
        }

        // Create usage period if subscription is active
        if ($localSubscription && $localSubscription->status === 'active') {
            try {
                $usageTracker = new \App\Services\ResellerUsageTracker();
                $usagePeriod = $usageTracker->createUsagePeriod($localSubscription);
                
                Log::info('Reseller usage period created on subscription creation', [
                    'subscription_id' => $subscriptionData['id'],
                    'reseller_id' => $resellerId,
                    'usage_period_id' => $usagePeriod->id,
                    'period_start' => $usagePeriod->period_start,
                    'period_end' => $usagePeriod->period_end,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create usage period on subscription creation', [
                    'subscription_id' => $subscriptionData['id'],
                    'reseller_id' => $resellerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Reseller subscription created/updated via webhook', [
            'subscription_id' => $subscriptionData['id'],
            'reseller_id' => $resellerId,
            'status' => $subscriptionData['status'],
            'period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
            'period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end'])
        ]);
    }

    /**
     * Handle reseller subscription updated webhook
     */
    private function handleResellerSubscriptionUpdated(array $subscriptionData): void
    {
        $this->handleResellerSubscriptionCreated($subscriptionData);
    }

    /**
     * Handle reseller subscription deleted webhook
     */
    private function handleResellerSubscriptionDeleted(array $subscriptionData): void
    {
        $resellerId = $subscriptionData['metadata']['reseller_id'] ?? null;
        if (!$resellerId) return;

        $localSubscription = \App\Models\ResellerSubscription::where('reseller_id', $resellerId)
            ->where('stripe_subscription_id', $subscriptionData['id'])
            ->first();

        if ($localSubscription) {
            $localSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
            ]);
        }

        Log::info('Reseller subscription cancelled via webhook', [
            'subscription_id' => $subscriptionData['id'],
            'reseller_id' => $resellerId,
            'cancelled_at' => Carbon::now()
        ]);
    }

    /**
     * Handle reseller payment succeeded webhook
     */
    private function handleResellerPaymentSucceeded(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) return;

        $localSubscription = \App\Models\ResellerSubscription::where('stripe_subscription_id', $subscriptionId)->first();
        if ($localSubscription) {
            // Update subscription status
            $localSubscription->update([
                'status' => 'active',
                'current_period_start' => Carbon::createFromTimestamp($invoiceData['period_start']),
                'current_period_end' => Carbon::createFromTimestamp($invoiceData['period_end']),
            ]);

            // Update existing transaction record (created during subscription creation)
            $existingTransaction = \App\Models\ResellerTransaction::where('reseller_subscription_id', $localSubscription->id)
                ->where('status', \App\Models\ResellerTransaction::STATUS_PENDING)
                ->latest()
                ->first();

            if ($existingTransaction) {
                // Update existing transaction
                $existingTransaction->update([
                    'status' => \App\Models\ResellerTransaction::STATUS_COMPLETED,
                    'external_transaction_id' => $invoiceData['id'],
                    'billing_email' => $invoiceData['customer_email'] ?? $existingTransaction->billing_email,
                    'description' => "Reseller subscription payment - {$localSubscription->package->name}",
                    'metadata' => array_merge($existingTransaction->metadata ?? [], [
                        'stripe_invoice_id' => $invoiceData['id'],
                        'stripe_subscription_id' => $subscriptionId,
                        'period_start' => Carbon::createFromTimestamp($invoiceData['period_start']),
                        'period_end' => Carbon::createFromTimestamp($invoiceData['period_end']),
                        'webhook_processed_at' => Carbon::now()->toISOString(),
                    ]),
                    'processed_at' => Carbon::now(),
                ]);
                
                Log::info('Updated existing reseller transaction', [
                    'transaction_id' => $existingTransaction->id,
                    'subscription_id' => $subscriptionId,
                    'reseller_id' => $localSubscription->reseller_id
                ]);
            } else {
                // Fallback: create new transaction if none exists
                \App\Models\ResellerTransaction::create([
                    'reseller_id' => $localSubscription->reseller_id,
                    'reseller_package_id' => $localSubscription->reseller_package_id,
                    'reseller_subscription_id' => $localSubscription->id,
                    'amount' => $invoiceData['amount_paid'] / 100, // Convert from cents
                    'currency' => strtoupper($invoiceData['currency']),
                    'status' => \App\Models\ResellerTransaction::STATUS_COMPLETED,
                    'payment_method' => \App\Models\ResellerTransaction::PAYMENT_STRIPE,
                    'external_transaction_id' => $invoiceData['id'],
                    'billing_email' => $invoiceData['customer_email'] ?? '',
                    'type' => \App\Models\ResellerTransaction::TYPE_SUBSCRIPTION,
                    'description' => "Reseller subscription payment - {$localSubscription->package->name}",
                    'metadata' => [
                        'stripe_invoice_id' => $invoiceData['id'],
                        'stripe_subscription_id' => $subscriptionId,
                        'period_start' => Carbon::createFromTimestamp($invoiceData['period_start']),
                        'period_end' => Carbon::createFromTimestamp($invoiceData['period_end']),
                        'created_via_webhook' => true,
                    ],
                    'processed_at' => Carbon::now(),
                ]);
                
                Log::warning('Created new reseller transaction (no existing PENDING transaction found)', [
                    'subscription_id' => $subscriptionId,
                    'reseller_id' => $localSubscription->reseller_id
                ]);
            }

            // Create usage period for the new billing cycle
            try {
                $usageTracker = new \App\Services\ResellerUsageTracker();
                $usagePeriod = $usageTracker->createUsagePeriod($localSubscription);
                
                Log::info('Reseller usage period created on payment success', [
                    'subscription_id' => $subscriptionId,
                    'reseller_id' => $localSubscription->reseller_id,
                    'usage_period_id' => $usagePeriod->id,
                    'period_start' => $usagePeriod->period_start,
                    'period_end' => $usagePeriod->period_end,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create usage period on payment success', [
                    'subscription_id' => $subscriptionId,
                    'reseller_id' => $localSubscription->reseller_id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Reseller subscription payment succeeded via webhook', [
                'subscription_id' => $subscriptionId,
                'reseller_id' => $localSubscription->reseller_id,
                'invoice_id' => $invoiceData['id'],
                'amount' => $invoiceData['amount_paid'] / 100
            ]);
        }
    }

    /**
     * Handle reseller payment failed webhook
     */
    private function handleResellerPaymentFailed(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) return;

        $localSubscription = \App\Models\ResellerSubscription::where('stripe_subscription_id', $subscriptionId)->first();
        if ($localSubscription) {
            // Update transaction status if exists
            $transaction = \App\Models\ResellerTransaction::where('reseller_subscription_id', $localSubscription->id)
                ->where('status', \App\Models\ResellerTransaction::STATUS_PENDING)
                ->latest()
                ->first();

            if ($transaction) {
                $transaction->update([
                    'status' => \App\Models\ResellerTransaction::STATUS_FAILED,
                    'failed_at' => Carbon::now(),
                ]);
            }

            Log::info('Reseller subscription payment failed via webhook', [
                'subscription_id' => $subscriptionId,
                'reseller_id' => $localSubscription->reseller_id,
                'invoice_id' => $invoiceData['id']
            ]);
        }
    }

    /**
     * Handle reseller payment intent succeeded webhook
     * This event is only for payment processing, not subscription management
     */
    private function handleResellerPaymentIntentSucceeded(array $paymentIntentData): void
    {
        Log::info('Processing reseller payment intent succeeded', [
            'payment_intent_id' => $paymentIntentData['id'],
            'amount' => $paymentIntentData['amount'],
            'customer_id' => $paymentIntentData['customer'] ?? null,
            'invoice_id' => $paymentIntentData['invoice'] ?? null
        ]);

        $paymentIntentId = $paymentIntentData['id'];
        $customerId = $paymentIntentData['customer'] ?? null;

        // Only update existing transaction status, don't create new ones or activate subscriptions
        // Subscription activation is handled by checkout.session.completed webhook
        $transaction = \App\Models\ResellerTransaction::where('external_transaction_id', $paymentIntentId)->first();
        
        if ($transaction) {
            Log::info('Found transaction by payment intent ID, updating status', [
                'transaction_id' => $transaction->id,
                'subscription_id' => $transaction->reseller_subscription_id
            ]);
            
            $transaction->update([
                'status' => \App\Models\ResellerTransaction::STATUS_COMPLETED,
                'processed_at' => Carbon::now(),
            ]);

            Log::info('Transaction status updated to completed', [
                'transaction_id' => $transaction->id,
                'payment_intent_id' => $paymentIntentId
            ]);
        } else {
            Log::info('No existing transaction found for payment intent', [
                'payment_intent_id' => $paymentIntentId,
                'customer_id' => $customerId
            ]);
        }
    }

    /**
     * Handle reseller payment intent failed webhook
     */
    private function handleResellerPaymentIntentFailed(array $paymentIntentData): void
    {
        $transaction = \App\Models\ResellerTransaction::where('external_transaction_id', $paymentIntentData['id'])->first();
        if ($transaction) {
            $transaction->update([
                'status' => \App\Models\ResellerTransaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Handle reseller checkout session completed webhook
     * This is the proper event for subscription activation and transaction creation
     */
    private function handleResellerCheckoutSessionCompleted(array $checkoutSessionData): void
    {
        Log::info('Processing reseller checkout session completed', [
            'checkout_session_id' => $checkoutSessionData['id'],
            'metadata' => $checkoutSessionData['metadata'] ?? [],
            'subscription_id' => $checkoutSessionData['subscription'] ?? null,
            'payment_status' => $checkoutSessionData['payment_status'] ?? null,
            'customer_id' => $checkoutSessionData['customer'] ?? null
        ]);

        $resellerId = $checkoutSessionData['metadata']['reseller_id'] ?? null;
        $checkoutSessionId = $checkoutSessionData['id'];
        $stripeSubscriptionId = $checkoutSessionData['subscription'] ?? null;
        $customerId = $checkoutSessionData['customer'] ?? null;
        $paymentIntentId = $checkoutSessionData['payment_intent'] ?? null;

        if (!$resellerId) {
            Log::warning('No reseller_id found in checkout session metadata', [
                'checkout_session_id' => $checkoutSessionId,
                'metadata' => $checkoutSessionData['metadata'] ?? []
            ]);
            return;
        }

        // Find the reseller subscription by checkout session ID
        $subscription = \App\Models\ResellerSubscription::where('stripe_checkout_session_id', $checkoutSessionId)->first();
        
        if (!$subscription) {
            Log::warning('No reseller subscription found for checkout session', [
                'checkout_session_id' => $checkoutSessionId,
                'reseller_id' => $resellerId
            ]);
            return;
        }

        if ($subscription->status !== 'pending') {
            Log::info('Subscription is not in pending status, skipping activation', [
                'subscription_id' => $subscription->id,
                'current_status' => $subscription->status
            ]);
            return;
        }

        // Get subscription details from Stripe to get current period dates
        $currentPeriodStart = null;
        $currentPeriodEnd = null;
        
        if ($stripeSubscriptionId) {
            try {
                $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
                $stripeSubscription = $stripe->subscriptions->retrieve($stripeSubscriptionId);
                
                $currentPeriodStart = $stripeSubscription->current_period_start ? 
                    Carbon::createFromTimestamp($stripeSubscription->current_period_start) : null;
                $currentPeriodEnd = $stripeSubscription->current_period_end ? 
                    Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null;
                    
                Log::info('Retrieved subscription period details from Stripe', [
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'current_period_start' => $currentPeriodStart,
                    'current_period_end' => $currentPeriodEnd
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve subscription details from Stripe', [
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Update subscription with Stripe subscription ID and activate
        $updateData = [
            'status' => 'active',
            'stripe_subscription_id' => $stripeSubscriptionId,
            'stripe_customer_id' => $customerId,
        ];

        // Only add period dates if we have them
        if ($currentPeriodStart) {
            $updateData['current_period_start'] = $currentPeriodStart;
        }
        if ($currentPeriodEnd) {
            $updateData['current_period_end'] = $currentPeriodEnd;
        }
        
        $subscription->update($updateData);

        $usageTracker = new \App\Services\ResellerUsageTracker();
        $usageTracker->createUsagePeriod($subscription);

        Log::info('Reseller subscription activated', [
            'subscription_id' => $subscription->id,
            'reseller_id' => $resellerId,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'package_id' => $subscription->reseller_package_id
        ]);

        // Create transaction record with billing information
        try {
            $amount = $subscription->custom_amount ?? $subscription->package->price ?? 0;
            
            // Extract billing information from checkout session
            $billingEmail = null;
            $billingName = null;
            $billingAddress = null;
            
            // Get billing details from customer or checkout session
            if ($customerId) {
                try {
                    $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
                    $customer = $stripe->customers->retrieve($customerId);
                    
                    $billingEmail = $customer->email ?? null;
                    $billingName = $customer->name ?? null;
                    
                    if ($customer->address) {
                        $billingAddress = [
                            'line1' => $customer->address->line1 ?? null,
                            'line2' => $customer->address->line2 ?? null,
                            'city' => $customer->address->city ?? null,
                            'state' => $customer->address->state ?? null,
                            'country' => $customer->address->country ?? null,
                            'postal_code' => $customer->address->postal_code ?? null,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve customer billing details from Stripe', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Fallback to reseller admin email if no billing email found
            if (!$billingEmail && $subscription->reseller) {
                $billingEmail = $subscription->reseller->admin_email ?? null;
            }
            
            // Fallback to reseller organization name if no billing name found
            if (!$billingName && $subscription->reseller) {
                $billingName = $subscription->reseller->org_name ?? null;
            }
            
            Log::info('Extracted billing information from checkout session', [
                'billing_email' => $billingEmail,
                'billing_name' => $billingName,
                'billing_address' => $billingAddress,
                'checkout_session_id' => $checkoutSessionId
            ]);
            
            $transaction = \App\Models\ResellerTransaction::create([
                'reseller_id' => $resellerId,
                'reseller_package_id' => $subscription->reseller_package_id,
                'reseller_subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => \App\Models\ResellerTransaction::STATUS_COMPLETED,
                'payment_method' => 'stripe_checkout_session',
                'external_transaction_id' => $paymentIntentId ?? $checkoutSessionId,
                'billing_email' => $billingEmail,
                'billing_name' => $billingName,
                'billing_address' => $billingAddress ? json_encode($billingAddress) : null,
                'type' => 'subscription',
                'description' => "Reseller subscription payment for {$subscription->package->name}",
                'metadata' => [
                    'stripe_checkout_session_id' => $checkoutSessionId,
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_customer_id' => $customerId,
                    'subscription_id' => $subscription->id,
                    'package_id' => $subscription->reseller_package_id,
                    'custom_amount' => $subscription->custom_amount,
                    'payment_status' => $checkoutSessionData['payment_status'] ?? null,
                ],
                'processed_at' => Carbon::now(),
            ]);

            Log::info('Reseller transaction record created', [
                'transaction_id' => $transaction->id,
                'subscription_id' => $subscription->id,
                'reseller_id' => $resellerId,
                'amount' => $amount,
                'stripe_subscription_id' => $stripeSubscriptionId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create reseller transaction record', [
                'subscription_id' => $subscription->id,
                'reseller_id' => $resellerId,
                'checkout_session_id' => $checkoutSessionId,
                'error' => $e->getMessage()
            ]);
        }

        // Send activation email to reseller admin
        try {
            $reseller = $subscription->reseller;
            if ($reseller && $reseller->adminUser) {
                $reseller->adminUser->notify(new ResellerSubscriptionActivated($subscription));
                Log::info('Reseller subscription activation email sent', [
                    'reseller_id' => $reseller->id,
                    'admin_email' => $reseller->adminUser->email,
                    'subscription_id' => $subscription->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send reseller subscription activation email', [
                'subscription_id' => $subscription->id,
                'reseller_id' => $resellerId,
                'error' => $e->getMessage()
            ]);
        }
    }
} 