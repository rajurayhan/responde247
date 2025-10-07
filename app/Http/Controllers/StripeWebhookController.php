<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle Stripe webhook events
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        
        Log::info('Stripe webhook received', [
            'signature_present' => !empty($signature),
            'payload_size' => strlen($payload)
        ]);
        
        // Parse event to determine payment type
        $event = json_decode($payload, true);
        $paymentType = $this->determinePaymentType($event);
        
        Log::info('Payment type determined', [
            'payment_type' => $paymentType,
            'event_type' => $event['type'] ?? 'unknown'
        ]);
        
        // If no payment type determined, skip processing
        if ($paymentType === null) {
            Log::info('No payment type determined, skipping webhook processing', [
                'event_type' => $event['type'] ?? 'unknown'
            ]);
            return response()->json(['success' => true, 'message' => 'Skipped processing']);
        }
        
        // Get appropriate webhook secret based on payment type
        $webhookSecret = $this->getWebhookSecretForPaymentType($paymentType, $event);
        
        if (!$webhookSecret) {
            Log::error('No webhook secret configured', [
                'payment_type' => $paymentType
            ]);
            return response()->json(['error' => 'Webhook secret not configured'], 400);
        }

        // Verify webhook signature 
        /* if (!$this->stripeService->verifyWebhookSignature($payload, $signature, $webhookSecret)) {
            Log::error('Invalid webhook signature', [
                'payment_type' => $paymentType
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } */

        try {
            // Process the webhook event with appropriate context
            $this->processWebhookWithContext($event, $paymentType);
            
            Log::info('Webhook processed successfully', [
                'event_type' => $event['type'],
                'payment_type' => $paymentType
            ]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e, [
                'payment_type' => $paymentType
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Determine if this is a reseller subscription payment or user subscription payment
     */
    private function determinePaymentType(array $event): ?string
    {
        $eventData = $event['data']['object'] ?? [];
        $eventType = $event['type'] ?? '';
        
        Log::info('Determining payment type', [
            'event_type' => $eventType,
            'event_data_keys' => array_keys($eventData),
            'has_metadata' => isset($eventData['metadata']),
            'metadata_content' => $eventData['metadata'] ?? null
        ]);
        
        // For payment intent events, check if there's an existing transaction
        if ($eventType === 'payment_intent.succeeded') {
            $paymentIntentId = $eventData['id'] ?? null;
            
            Log::info('Payment intent succeeded event detected', [
                'payment_intent_id' => $paymentIntentId,
                'customer_id' => $eventData['customer'] ?? null,
                'amount' => $eventData['amount'] ?? null
            ]);
            
            // Check if there's an existing reseller transaction for this payment intent
            $resellerTransaction = \App\Models\ResellerTransaction::where('external_transaction_id', $paymentIntentId)->first();
            if ($resellerTransaction) {
                Log::info('Found reseller transaction for payment intent', [
                    'transaction_id' => $resellerTransaction->id,
                    'reseller_id' => $resellerTransaction->reseller_id,
                    'subscription_id' => $resellerTransaction->reseller_subscription_id
                ]);
                return 'reseller_subscription';
            }
            
            // Check for user transaction
            $userTransaction = \App\Models\Transaction::where('external_transaction_id', $paymentIntentId)->first();
            if ($userTransaction) {
                Log::info('Found user transaction for payment intent', [
                    'transaction_id' => $userTransaction->id,
                    'user_id' => $userTransaction->user_id
                ]);
                return 'user_subscription';
            }
            
            // If no transaction found, this might be a standalone payment intent
            // Skip processing to avoid errors
            Log::info('No existing transaction found for payment intent, skipping processing', [
                'payment_intent_id' => $paymentIntentId
            ]);
            return null; // Skip processing
        }
        
        // For checkout session events, check metadata
        if ($eventType === 'checkout.session.completed') {
            $checkoutSessionId = $eventData['id'] ?? null;
            
            Log::info('Checkout session event detected', [
                'checkout_session_id' => $checkoutSessionId,
                'metadata' => $eventData['metadata'] ?? [],
                'subscription_id' => $eventData['subscription'] ?? null
            ]);
            
            // Check if this is a reseller subscription checkout session
            if (isset($eventData['metadata']['is_reseller_subscription']) && 
                $eventData['metadata']['is_reseller_subscription'] === 'true') {
                Log::info('Reseller subscription checkout session detected via metadata');
                return 'reseller_subscription';
            }
            
            // Check database for checkout session
            $resellerSubscription = \App\Models\ResellerSubscription::where('stripe_checkout_session_id', $checkoutSessionId)->first();
            if ($resellerSubscription) {
                Log::info('Found reseller subscription by checkout session ID', [
                    'reseller_id' => $resellerSubscription->reseller_id,
                    'subscription_id' => $resellerSubscription->id,
                    'checkout_session_id' => $checkoutSessionId
                ]);
                return 'reseller_subscription';
            }
            
            // Check for user subscription checkout session
            $userSubscription = \App\Models\UserSubscription::where('stripe_checkout_session_id', $checkoutSessionId)->first();
            if ($userSubscription) {
                Log::info('Found user subscription by checkout session ID', [
                    'user_id' => $userSubscription->user_id,
                    'reseller_id' => $userSubscription->reseller_id,
                    'subscription_id' => $userSubscription->id,
                    'checkout_session_id' => $checkoutSessionId
                ]);
                return 'user_subscription';
            }
        }
        
        // For invoice events, check subscription metadata
        if ($eventType === 'invoice.payment_succeeded' && isset($eventData['subscription'])) {
            $subscriptionId = $eventData['subscription'];
            
            Log::info('Invoice event detected, checking subscription', [
                'subscription_id' => $subscriptionId,
                'invoice_id' => $eventData['id'] ?? null,
                'customer_id' => $eventData['customer'] ?? null
            ]);
            
            // Check database first for subscription type
            $resellerSubscription = \App\Models\ResellerSubscription::where('stripe_subscription_id', $subscriptionId)->first();
            if ($resellerSubscription) {
                Log::info('Found reseller subscription in database', [
                    'reseller_id' => $resellerSubscription->reseller_id,
                    'subscription_id' => $subscriptionId,
                    'package_id' => $resellerSubscription->reseller_package_id
                ]);
                return 'reseller_subscription';
            }
            
            $userSubscription = \App\Models\UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();
            if ($userSubscription) {
                Log::info('Found user subscription in database', [
                    'user_id' => $userSubscription->user_id,
                    'reseller_id' => $userSubscription->reseller_id,
                    'subscription_id' => $subscriptionId,
                    'package_id' => $userSubscription->subscription_package_id
                ]);
                return 'user_subscription';
            }
            
            Log::warning('No subscription found in database for invoice event', [
                'subscription_id' => $subscriptionId,
                'invoice_id' => $eventData['id'] ?? null
            ]);
            
            // Try to fetch subscription metadata from Stripe as fallback
            try {
                $stripeSubscription = $this->stripeService->getResellerSubscription($subscriptionId, '');
                if ($stripeSubscription && isset($stripeSubscription['metadata']['is_reseller_subscription'])) {
                    Log::info('Found reseller subscription metadata in Stripe', [
                        'subscription_id' => $subscriptionId,
                        'metadata' => $stripeSubscription['metadata']
                    ]);
                    return 'reseller_subscription';
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch subscription from Stripe', [
                    'subscription_id' => $subscriptionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Check if this is a reseller subscription payment (direct metadata)
        if (isset($eventData['metadata']['is_reseller_subscription']) && 
            $eventData['metadata']['is_reseller_subscription'] === 'true') {
            Log::info('Reseller subscription detected via metadata');
            return 'reseller_subscription';
        }
        
        // Check if this is a user subscription payment (direct metadata)
        if (isset($eventData['metadata']['user_id']) && 
            isset($eventData['metadata']['reseller_id'])) {
            Log::info('User subscription detected via metadata');
            return 'user_subscription';
        }
        
        // Check by Stripe subscription ID in database (fallback)
        if (isset($eventData['id'])) {
            Log::info('Checking database by subscription ID', [
                'subscription_id' => $eventData['id']
            ]);
            
            // Check if it's a reseller subscription
            $resellerSubscription = \App\Models\ResellerSubscription::where('stripe_subscription_id', $eventData['id'])->first();
            if ($resellerSubscription) {
                Log::info('Found reseller subscription by ID');
                return 'reseller_subscription';
            }
            
            // Check if it's a user subscription
            $userSubscription = \App\Models\UserSubscription::where('stripe_subscription_id', $eventData['id'])->first();
            if ($userSubscription) {
                Log::info('Found user subscription by ID');
                return 'user_subscription';
            }
        }
        
        Log::warning('Could not determine payment type, attempting fallback detection');
        
        // Final fallback: if we can't determine the type, try to process as reseller subscription first
        // This handles cases where the subscription exists in Stripe but not in our database
        if (isset($eventData['subscription'])) {
            $subscriptionId = $eventData['subscription'];
            Log::info('Attempting fallback: checking if this might be a reseller subscription', [
                'subscription_id' => $subscriptionId
            ]);
            
            // Try to fetch from Stripe to check metadata
            try {
                $stripeSubscription = $this->stripeService->getResellerSubscription($subscriptionId, '');
                if ($stripeSubscription && isset($stripeSubscription['metadata']['is_reseller_subscription'])) {
                    Log::info('Fallback: Found reseller subscription metadata in Stripe', [
                        'subscription_id' => $subscriptionId,
                        'metadata' => $stripeSubscription['metadata']
                    ]);
                    return 'reseller_subscription';
                }
            } catch (\Exception $e) {
                Log::warning('Fallback: Failed to fetch subscription from Stripe', [
                    'subscription_id' => $subscriptionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::warning('All detection methods failed, defaulting to user_subscription');
        // Default to user subscription for backward compatibility
        return 'user_subscription';
    }

    /**
     * Get webhook secret based on payment type
     */
    private function getWebhookSecretForPaymentType(string $paymentType, array $event): ?string
    {
        if ($paymentType === 'reseller_subscription') {
            // Use global .env webhook secret for reseller subscription payments
            $globalSecret = config('stripe.webhook_secret');
            Log::info('Using global webhook secret for reseller subscription payment');
            return $globalSecret;
        }
        
        if ($paymentType === 'user_subscription') {
            // Use reseller-specific webhook secret for user subscription payments
            $resellerId = $this->extractResellerIdFromEvent($event);
            
            if ($resellerId) {
                // Try environment variable first
                $envSecret = env('STRIPE_WEBHOOK_SECRET_' . strtoupper($resellerId));
                if ($envSecret) {
                    Log::info('Using environment webhook secret for user subscription payment', [
                        'reseller_id' => $resellerId
                    ]);
                    return $envSecret;
                }
                
                // Try database setting
                $dbSecret = \App\Models\ResellerSetting::where('reseller_id', $resellerId)
                    ->where('key', 'stripe_webhook_secret')
                    ->value('value');
                
                if ($dbSecret) {
                    Log::info('Using database webhook secret for user subscription payment', [
                        'reseller_id' => $resellerId
                    ]);
                    return $dbSecret;
                }
            }
            
            // Fallback to global secret
            Log::info('Using global webhook secret as fallback for user subscription payment');
            return config('stripe.webhook_secret');
        }
        
        return null;
    }

    /**
     * Extract reseller ID from event data
     */
    private function extractResellerIdFromEvent(array $event): ?string
    {
        $eventData = $event['data']['object'] ?? [];
        $eventType = $event['type'] ?? '';
        
        Log::info('Extracting reseller ID from event', [
            'event_type' => $eventType,
            'event_data_keys' => array_keys($eventData)
        ]);
        
        // Check metadata first
        if (isset($eventData['metadata']['reseller_id'])) {
            Log::info('Found reseller_id in metadata', [
                'reseller_id' => $eventData['metadata']['reseller_id']
            ]);
            return $eventData['metadata']['reseller_id'];
        }
        
        // For invoice events, check subscription ID
        if ($eventType === 'invoice.payment_succeeded' && isset($eventData['subscription'])) {
            $subscriptionId = $eventData['subscription'];
            
            Log::info('Checking subscription for reseller_id', [
                'subscription_id' => $subscriptionId
            ]);
            
            // Check user subscriptions first
            $userSubscription = \App\Models\UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();
            if ($userSubscription && $userSubscription->reseller_id) {
                Log::info('Found reseller_id in user subscription', [
                    'reseller_id' => $userSubscription->reseller_id,
                    'user_id' => $userSubscription->user_id
                ]);
                return $userSubscription->reseller_id;
            }
            
            // Check reseller subscriptions
            $resellerSubscription = \App\Models\ResellerSubscription::where('stripe_subscription_id', $subscriptionId)->first();
            if ($resellerSubscription && $resellerSubscription->reseller_id) {
                Log::info('Found reseller_id in reseller subscription', [
                    'reseller_id' => $resellerSubscription->reseller_id
                ]);
                return $resellerSubscription->reseller_id;
            }
        }
        
        // Check by Stripe subscription ID (fallback)
        if (isset($eventData['id'])) {
            Log::info('Checking by subscription ID', [
                'subscription_id' => $eventData['id']
            ]);
            
            $userSubscription = \App\Models\UserSubscription::where('stripe_subscription_id', $eventData['id'])->first();
            if ($userSubscription && $userSubscription->reseller_id) {
                Log::info('Found reseller_id by subscription ID', [
                    'reseller_id' => $userSubscription->reseller_id
                ]);
                return $userSubscription->reseller_id;
            }
        }
        
        // Check by customer ID (fallback)
        if (isset($eventData['customer'])) {
            Log::info('Checking by customer ID', [
                'customer_id' => $eventData['customer']
            ]);
            
            $userSubscription = \App\Models\UserSubscription::where('stripe_customer_id', $eventData['customer'])->first();
            if ($userSubscription && $userSubscription->reseller_id) {
                Log::info('Found reseller_id by customer ID', [
                    'reseller_id' => $userSubscription->reseller_id
                ]);
                return $userSubscription->reseller_id;
            }
        }
        
        Log::warning('Could not extract reseller_id from event');
        return null;
    }

    /**
     * Process webhook with appropriate context
     */
    private function processWebhookWithContext(array $event, string $paymentType): void
    {
        Log::info('Processing webhook with context', [
            'payment_type' => $paymentType,
            'event_type' => $event['type'] ?? 'unknown'
        ]);
        
        if ($paymentType === 'reseller_subscription') {
            // Process reseller subscription payment with global Stripe config
            Log::info('Processing as reseller subscription with global config');
            $this->stripeService->processWebhookEvent($event, null, 'global');
        } else {
            // Process user subscription payment with reseller-specific Stripe config
            $resellerId = $this->extractResellerIdFromEvent($event);
            
            if (!$resellerId) {
                Log::error('Cannot process user subscription webhook: reseller_id not found', [
                    'event_type' => $event['type'] ?? 'unknown',
                    'event_data' => $event['data']['object'] ?? []
                ]);
                throw new \Exception('Cannot determine reseller_id for user subscription webhook');
            }
            
            Log::info('Processing as user subscription with reseller config', [
                'reseller_id' => $resellerId
            ]);
            $this->stripeService->processWebhookEvent($event, $resellerId, 'reseller');
        }
    }

} 