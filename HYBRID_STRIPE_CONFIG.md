# Hybrid Stripe Configuration System

This document explains the hybrid Stripe configuration system that uses different Stripe accounts and webhook secrets based on the payment type.

## Overview

The system now supports two distinct payment flows with different Stripe configurations:

1. **Reseller Subscription Payments** → Use **Global .env Stripe Config**
2. **User Subscription Payments** → Use **Reseller-Specific Database Stripe Config**

## Business Logic

### Reseller Subscription Payments
- **When**: Super admin creates a subscription for a reseller, and the reseller pays for it
- **Stripe Account**: Platform's main Stripe account (global .env config)
- **Revenue**: Platform revenue
- **Webhook Secret**: `STRIPE_WEBHOOK_SECRET` from .env file

### User Subscription Payments  
- **When**: Users subscribe to services under a reseller's account
- **Stripe Account**: Reseller's individual Stripe account (database config)
- **Revenue**: Reseller revenue (with platform fees)
- **Webhook Secret**: Reseller-specific webhook secret from environment or database

## Implementation Details

### Payment Type Detection

The system automatically detects payment types using multiple methods:

```php
private function determinePaymentType(array $event): string
{
    $eventData = $event['data']['object'] ?? [];
    
    // Method 1: Check metadata for reseller subscription flag
    if (isset($eventData['metadata']['is_reseller_subscription']) && 
        $eventData['metadata']['is_reseller_subscription'] === 'true') {
        return 'reseller_subscription';
    }
    
    // Method 2: Check metadata for user subscription indicators
    if (isset($eventData['metadata']['user_id']) && 
        isset($eventData['metadata']['reseller_id'])) {
        return 'user_subscription';
    }
    
    // Method 3: Database lookup by Stripe subscription ID
    if (isset($eventData['id'])) {
        // Check reseller subscriptions table
        $resellerSubscription = ResellerSubscription::where('stripe_subscription_id', $eventData['id'])->first();
        if ($resellerSubscription) {
            return 'reseller_subscription';
        }
        
        // Check user subscriptions table
        $userSubscription = UserSubscription::where('stripe_subscription_id', $eventData['id'])->first();
        if ($userSubscription) {
            return 'user_subscription';
        }
    }
    
    // Default fallback
    return 'user_subscription';
}
```

### Webhook Secret Selection

Based on the payment type, the system selects the appropriate webhook secret:

```php
private function getWebhookSecretForPaymentType(string $paymentType, array $event): ?string
{
    if ($paymentType === 'reseller_subscription') {
        // Always use global .env webhook secret
        return config('stripe.webhook_secret');
    }
    
    if ($paymentType === 'user_subscription') {
        $resellerId = $this->extractResellerIdFromEvent($event);
        
        if ($resellerId) {
            // Try environment variable first
            $envSecret = env('STRIPE_WEBHOOK_SECRET_' . strtoupper($resellerId));
            if ($envSecret) {
                return $envSecret;
            }
            
            // Try database setting
            $dbSecret = ResellerSetting::where('reseller_id', $resellerId)
                ->where('key', 'stripe_webhook_secret')
                ->value('value');
            
            if ($dbSecret) {
                return $dbSecret;
            }
        }
        
        // Fallback to global secret
        return config('stripe.webhook_secret');
    }
    
    return null;
}
```

### Stripe Configuration Context

The StripeService now supports different configuration contexts:

```php
public function processWebhookEvent(array $event, ?string $resellerId = null, string $configSource = 'reseller'): void
{
    if ($configSource === 'global') {
        // Use global Stripe config for reseller subscription payments
        $this->setGlobalContext();
    } else {
        // Use reseller-specific Stripe config for user subscription payments
        $this->setResellerContext($resellerId);
    }
    
    // Process webhook with appropriate context
    $this->processWebhookEvent($event);
}
```

## Configuration Setup

### Global Stripe Configuration (.env)

For reseller subscription payments, configure your main Stripe account:

```bash
# Platform's main Stripe account (for reseller subscription payments)
STRIPE_PUBLISHABLE_KEY=pk_live_your_platform_key_here
STRIPE_SECRET_KEY=sk_live_your_platform_secret_here
STRIPE_WEBHOOK_SECRET=whsec_your_platform_webhook_secret_here
STRIPE_API_VERSION=2024-06-20
STRIPE_CURRENCY=usd
STRIPE_TEST_MODE=false
```

### Reseller-Specific Configuration

For user subscription payments, configure reseller-specific Stripe accounts:

#### Option 1: Environment Variables (Recommended)
```bash
# Reseller-specific webhook secrets
STRIPE_WEBHOOK_SECRET_RESELLER1=whsec_reseller1_webhook_secret_here
STRIPE_WEBHOOK_SECRET_RESELLER2=whsec_reseller2_webhook_secret_here
STRIPE_WEBHOOK_SECRET_ACME_CORP=whsec_acme_webhook_secret_here
```

#### Option 2: Database Settings
Store reseller-specific Stripe configurations in the `reseller_settings` table:

```php
// Example: Set reseller-specific Stripe webhook secret
ResellerSetting::setValue(
    'reseller1', 
    'stripe_webhook_secret', 
    'whsec_reseller1_secret_here',
    'Stripe Webhook Secret',
    'password',
    'stripe',
    'Webhook secret for Stripe webhook verification'
);
```

## Webhook Processing Flow

### 1. Webhook Received
```
POST /webhook/stripe
Headers: Stripe-Signature: t=timestamp,v1=signature
Body: { webhook payload }
```

### 2. Payment Type Detection
- Parse webhook payload
- Check metadata and database records
- Determine if it's a reseller or user subscription payment

### 3. Webhook Secret Selection
- **Reseller subscription** → Use global `STRIPE_WEBHOOK_SECRET`
- **User subscription** → Use reseller-specific webhook secret

### 4. Signature Verification
- Verify webhook signature using the selected secret
- Reject if signature is invalid

### 5. Context Setting
- **Reseller subscription** → Set global Stripe context
- **User subscription** → Set reseller-specific Stripe context

### 6. Event Processing
- Process webhook event with appropriate Stripe configuration
- Update local database records
- Handle payment success/failure

## Logging and Monitoring

The system provides comprehensive logging for debugging:

```php
Log::info('Payment type determined', [
    'payment_type' => $paymentType,
    'event_type' => $event['type'] ?? 'unknown'
]);

Log::info('Using global webhook secret for reseller subscription payment');
Log::info('Using environment webhook secret for user subscription payment', [
    'reseller_id' => $resellerId
]);
```

## Security Considerations

1. **Webhook Secret Isolation**: Each reseller uses their own webhook secret
2. **Signature Verification**: All webhooks are verified before processing
3. **Context Separation**: Global and reseller contexts are properly isolated
4. **Fallback Handling**: Graceful fallback to global config when reseller config is unavailable

## Testing

### Test Reseller Subscription Payment
1. Create a reseller subscription with `is_reseller_subscription: true` metadata
2. Send webhook to endpoint
3. Verify it uses global webhook secret
4. Check logs confirm "reseller_subscription" payment type

### Test User Subscription Payment
1. Create a user subscription under a reseller
2. Send webhook to endpoint  
3. Verify it uses reseller-specific webhook secret
4. Check logs confirm "user_subscription" payment type

## Troubleshooting

### Common Issues

1. **Wrong Webhook Secret Used**
   - Check payment type detection logic
   - Verify metadata is correctly set during subscription creation
   - Review database records for subscription types

2. **Signature Verification Fails**
   - Ensure correct webhook secret is being used
   - Check if reseller-specific secret is properly configured
   - Verify webhook endpoint URL in Stripe dashboard

3. **Wrong Stripe Context**
   - Confirm `configSource` parameter is correctly passed
   - Check if global vs reseller context is properly set
   - Review Stripe configuration loading logic

### Debug Commands

```bash
# Check webhook secret configuration
php artisan tinker
>>> config('stripe.webhook_secret')
>>> env('STRIPE_WEBHOOK_SECRET_RESELLER1')

# Check reseller settings
>>> App\Models\ResellerSetting::where('key', 'stripe_webhook_secret')->get()

# Check subscription types
>>> App\Models\ResellerSubscription::where('stripe_subscription_id', 'sub_xxx')->first()
>>> App\Models\UserSubscription::where('stripe_subscription_id', 'sub_xxx')->first()
```

## Migration Guide

If you're migrating from a single Stripe configuration:

1. **Update Environment Variables**
   - Add reseller-specific webhook secrets to `.env`
   - Ensure global Stripe config is properly set

2. **Update Database Settings**
   - Migrate existing reseller Stripe settings to new format
   - Update webhook secret storage if needed

3. **Test Both Payment Flows**
   - Test reseller subscription payments (global config)
   - Test user subscription payments (reseller config)

4. **Monitor Logs**
   - Watch for payment type detection accuracy
   - Verify correct webhook secrets are being used
   - Check for any signature verification failures

This hybrid approach provides the flexibility to handle both platform revenue (reseller subscriptions) and reseller revenue (user subscriptions) with appropriate Stripe account isolation.
