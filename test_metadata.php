<?php
/**
 * Test script to verify metadata is being set correctly
 * Run with: php test_metadata.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ResellerSubscription;
use App\Models\UserSubscription;
use App\Services\StripeService;

echo "=== Metadata Test ===\n\n";

// Test 1: Check reseller subscriptions
echo "1. Reseller Subscriptions with Stripe IDs:\n";
$resellerSubs = ResellerSubscription::whereNotNull('stripe_subscription_id')->get();
foreach ($resellerSubs as $sub) {
    echo "   - Local ID: {$sub->id}\n";
    echo "     Stripe ID: {$sub->stripe_subscription_id}\n";
    echo "     Reseller: {$sub->reseller_id}\n";
    echo "     Status: {$sub->status}\n";
    echo "     ---\n";
}

// Test 2: Check user subscriptions
echo "\n2. User Subscriptions with Stripe IDs:\n";
$userSubs = UserSubscription::whereNotNull('stripe_subscription_id')->get();
foreach ($userSubs as $sub) {
    echo "   - Local ID: {$sub->id}\n";
    echo "     Stripe ID: {$sub->stripe_subscription_id}\n";
    echo "     User: {$sub->user_id}\n";
    echo "     Reseller: {$sub->reseller_id}\n";
    echo "     Status: {$sub->status}\n";
    echo "     ---\n";
}

// Test 3: Fetch metadata from Stripe
echo "\n3. Fetching metadata from Stripe:\n";
$stripeService = new StripeService();

// Test with a specific subscription ID
echo "Enter a Stripe subscription ID to test metadata fetch: ";
$subscriptionId = trim(fgets(STDIN));

if ($subscriptionId) {
    try {
        echo "\nFetching subscription from Stripe: {$subscriptionId}\n";
        $stripeSubscription = $stripeService->getResellerSubscription($subscriptionId, '');
        
        if ($stripeSubscription) {
            echo "✅ Subscription found in Stripe:\n";
            echo "   - ID: {$stripeSubscription['id']}\n";
            echo "   - Status: {$stripeSubscription['status']}\n";
            echo "   - Customer: {$stripeSubscription['customer']}\n";
            echo "   - Metadata: " . json_encode($stripeSubscription['metadata'], JSON_PRETTY_PRINT) . "\n";
            
            // Check if it's a reseller subscription
            if (isset($stripeSubscription['metadata']['is_reseller_subscription'])) {
                echo "   - Type: RESELLER SUBSCRIPTION\n";
                echo "   - Reseller ID: {$stripeSubscription['metadata']['reseller_id']}\n";
            } else {
                echo "   - Type: USER SUBSCRIPTION\n";
                echo "   - User ID: {$stripeSubscription['metadata']['user_id']}\n";
                echo "   - Reseller ID: {$stripeSubscription['metadata']['reseller_id']}\n";
            }
        } else {
            echo "❌ Subscription not found in Stripe\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error fetching subscription: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
