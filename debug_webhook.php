<?php
/**
 * Debug script to check webhook subscription detection
 * Run with: php debug_webhook.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ResellerSubscription;
use App\Models\UserSubscription;

echo "=== Webhook Subscription Debug ===\n\n";

// Check reseller subscriptions
echo "1. Reseller Subscriptions:\n";
$resellerSubs = ResellerSubscription::with(['reseller', 'package'])->get();
foreach ($resellerSubs as $sub) {
    echo "   - ID: {$sub->id}\n";
    echo "     Reseller: {$sub->reseller_id}\n";
    echo "     Stripe Subscription ID: {$sub->stripe_subscription_id}\n";
    echo "     Status: {$sub->status}\n";
    echo "     Package: {$sub->package->name ?? 'N/A'}\n";
    echo "     Created: {$sub->created_at}\n";
    echo "     ---\n";
}

echo "\n2. User Subscriptions:\n";
$userSubs = UserSubscription::with(['user', 'package'])->get();
foreach ($userSubs as $sub) {
    echo "   - ID: {$sub->id}\n";
    echo "     User: {$sub->user_id}\n";
    echo "     Reseller: {$sub->reseller_id}\n";
    echo "     Stripe Subscription ID: {$sub->stripe_subscription_id}\n";
    echo "     Status: {$sub->status}\n";
    echo "     Package: {$sub->package->name ?? 'N/A'}\n";
    echo "     Created: {$sub->created_at}\n";
    echo "     ---\n";
}

echo "\n3. Test Subscription Lookup:\n";
echo "Enter a Stripe subscription ID to test lookup: ";
$subscriptionId = trim(fgets(STDIN));

if ($subscriptionId) {
    echo "\nLooking up subscription: {$subscriptionId}\n";
    
    $resellerSub = ResellerSubscription::where('stripe_subscription_id', $subscriptionId)->first();
    if ($resellerSub) {
        echo "✅ Found in reseller_subscriptions table:\n";
        echo "   - Reseller ID: {$resellerSub->reseller_id}\n";
        echo "   - Package ID: {$resellerSub->reseller_package_id}\n";
        echo "   - Status: {$resellerSub->status}\n";
    } else {
        echo "❌ Not found in reseller_subscriptions table\n";
    }
    
    $userSub = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();
    if ($userSub) {
        echo "✅ Found in user_subscriptions table:\n";
        echo "   - User ID: {$userSub->user_id}\n";
        echo "   - Reseller ID: {$userSub->reseller_id}\n";
        echo "   - Package ID: {$userSub->subscription_package_id}\n";
        echo "   - Status: {$userSub->status}\n";
    } else {
        echo "❌ Not found in user_subscriptions table\n";
    }
    
    if (!$resellerSub && !$userSub) {
        echo "\n⚠️  Subscription not found in either table!\n";
        echo "This means the webhook will default to 'user_subscription' type.\n";
    }
}

echo "\n=== Debug Complete ===\n";
