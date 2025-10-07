# Reseller Webhook Secret Configuration

This document explains how to configure webhook secrets for reseller-specific Stripe webhooks using environment variables.

## Overview

The system supports multiple methods for configuring webhook secrets for resellers:

1. **Environment Variables** (Recommended) - Reseller-specific secrets in `.env` file
2. **Database Settings** - Stored in `reseller_settings` table
3. **Global Configuration** - Fallback to global `STRIPE_WEBHOOK_SECRET`

## Priority Order

The system uses the following priority when determining which webhook secret to use:

1. **Environment Variable**: `STRIPE_WEBHOOK_SECRET_{RESELLER_ID}`
2. **Database Setting**: `stripe_webhook_secret` in `reseller_settings` table
3. **Global Config**: `STRIPE_WEBHOOK_SECRET` from environment

## Environment Variable Configuration

### Format
```
STRIPE_WEBHOOK_SECRET_{RESELLER_ID}=whsec_your_webhook_secret_here
```

### Examples

For a reseller with ID `reseller1`:
```bash
STRIPE_WEBHOOK_SECRET_RESELLER1=whsec_1234567890abcdef1234567890abcdef12345678
```

For a reseller with ID `acme_corp`:
```bash
STRIPE_WEBHOOK_SECRET_ACME_CORP=whsec_fedcba0987654321fedcba0987654321fedcba09
```

### Multiple Resellers
```bash
# Global webhook secret (fallback)
STRIPE_WEBHOOK_SECRET=whsec_global_secret_here

# Reseller-specific secrets
STRIPE_WEBHOOK_SECRET_RESELLER1=whsec_reseller1_secret_here
STRIPE_WEBHOOK_SECRET_RESELLER2=whsec_reseller2_secret_here
STRIPE_WEBHOOK_SECRET_ACME_CORP=whsec_acme_secret_here
```

## Database Configuration (Alternative)

If you prefer to store webhook secrets in the database instead of environment variables:

```php
// Set webhook secret for a reseller
ResellerSetting::setValue(
    'reseller1', 
    'stripe_webhook_secret', 
    'whsec_your_secret_here',
    'Stripe Webhook Secret',
    'password',
    'stripe',
    'Webhook secret for Stripe webhook verification'
);
```

## Implementation Details

### StripeWebhookController
The webhook controller automatically detects the reseller context and retrieves the appropriate webhook secret:

```php
// Get reseller context
$resellerId = config('reseller.id');

// Set reseller context in StripeService
$this->stripeService->setResellerContext($resellerId);

// Get webhook secret (with priority handling)
$webhookSecret = $this->stripeService->getWebhookSecret();
```

### StripeService
The service includes a `getWebhookSecret()` method that implements the priority logic:

```php
public function getWebhookSecret(): ?string
{
    if (!$this->resellerId) {
        return config('stripe.webhook_secret');
    }

    // 1. Try environment variable
    $envWebhookSecret = env('STRIPE_WEBHOOK_SECRET_' . strtoupper($this->resellerId));
    if ($envWebhookSecret) {
        return $envWebhookSecret;
    }

    // 2. Try database setting
    $dbWebhookSecret = ResellerSetting::where('reseller_id', $this->resellerId)
        ->where('key', 'stripe_webhook_secret')
        ->value('value');

    if ($dbWebhookSecret) {
        return $dbWebhookSecret;
    }

    // 3. Fallback to global config
    return config('stripe.webhook_secret');
}
```

## Security Considerations

1. **Environment Variables**: Store secrets in `.env` file and ensure it's not committed to version control
2. **Database Storage**: Encrypt sensitive data if storing in database
3. **Access Control**: Limit access to webhook endpoints
4. **Logging**: Webhook secret usage is logged for audit purposes

## Testing

To test webhook secret configuration:

1. Set up a reseller-specific webhook secret in your `.env` file
2. Send a test webhook to your endpoint
3. Check the logs to verify which secret was used:

```bash
# Look for log entries like:
# "Using environment webhook secret for reseller" {"reseller_id":"reseller1"}
# "Using database webhook secret for reseller" {"reseller_id":"reseller1"}
# "Using global webhook secret for reseller" {"reseller_id":"reseller1"}
```

## Troubleshooting

### Common Issues

1. **Webhook Secret Not Found**
   - Check that the environment variable name matches the reseller ID exactly
   - Ensure the reseller ID is uppercase in the environment variable name
   - Verify the `.env` file is loaded correctly

2. **Invalid Signature Errors**
   - Verify the webhook secret matches the one configured in Stripe dashboard
   - Check that the reseller context is set correctly
   - Ensure the webhook endpoint URL is correct

3. **Fallback to Global Secret**
   - This is normal behavior when no reseller-specific secret is found
   - Check logs to confirm which secret source was used

### Debug Commands

```bash
# Check if environment variable is set
php artisan tinker
>>> env('STRIPE_WEBHOOK_SECRET_RESELLER1')

# Check database setting
>>> App\Models\ResellerSetting::where('reseller_id', 'reseller1')->where('key', 'stripe_webhook_secret')->value('value')

# Check global config
>>> config('stripe.webhook_secret')
```

## Migration from Database to Environment Variables

If you're currently using database-stored webhook secrets and want to migrate to environment variables:

1. Export current secrets from database
2. Add them to your `.env` file using the proper format
3. Test webhook processing
4. Optionally remove database entries (they will be ignored due to priority)

```sql
-- Export current webhook secrets
SELECT reseller_id, value 
FROM reseller_settings 
WHERE key = 'stripe_webhook_secret';
```

Then add to `.env`:
```bash
STRIPE_WEBHOOK_SECRET_RESELLER1=exported_value_here
STRIPE_WEBHOOK_SECRET_RESELLER2=exported_value_here
```
