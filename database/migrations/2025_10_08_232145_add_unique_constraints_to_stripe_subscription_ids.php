<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove any duplicate stripe_subscription_ids
        $this->removeDuplicateStripeSubscriptions();
        
        // Add unique constraint to user_subscriptions table
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->unique('stripe_subscription_id', 'user_subscriptions_stripe_subscription_id_unique');
        });
        
        // Add unique constraint to reseller_subscriptions table
        Schema::table('reseller_subscriptions', function (Blueprint $table) {
            $table->unique('stripe_subscription_id', 'reseller_subscriptions_stripe_subscription_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropUnique('user_subscriptions_stripe_subscription_id_unique');
        });
        
        Schema::table('reseller_subscriptions', function (Blueprint $table) {
            $table->dropUnique('reseller_subscriptions_stripe_subscription_id_unique');
        });
    }

    /**
     * Remove duplicate stripe_subscription_ids by keeping the latest record
     */
    private function removeDuplicateStripeSubscriptions(): void
    {
        // Handle user_subscriptions duplicates
        $userDuplicates = DB::table('user_subscriptions')
            ->select('stripe_subscription_id')
            ->whereNotNull('stripe_subscription_id')
            ->groupBy('stripe_subscription_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($userDuplicates as $duplicate) {
            $subscriptions = DB::table('user_subscriptions')
                ->where('stripe_subscription_id', $duplicate->stripe_subscription_id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Keep the latest one, delete the rest
            $keepId = $subscriptions->first()->id;
            $deleteIds = $subscriptions->where('id', '!=', $keepId)->pluck('id');
            
            if ($deleteIds->isNotEmpty()) {
                DB::table('user_subscriptions')->whereIn('id', $deleteIds)->delete();
                echo "Removed " . $deleteIds->count() . " duplicate user_subscriptions for stripe_subscription_id: " . $duplicate->stripe_subscription_id . "\n";
            }
        }

        // Handle reseller_subscriptions duplicates
        $resellerDuplicates = DB::table('reseller_subscriptions')
            ->select('stripe_subscription_id')
            ->whereNotNull('stripe_subscription_id')
            ->groupBy('stripe_subscription_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($resellerDuplicates as $duplicate) {
            $subscriptions = DB::table('reseller_subscriptions')
                ->where('stripe_subscription_id', $duplicate->stripe_subscription_id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Keep the latest one, delete the rest
            $keepId = $subscriptions->first()->id;
            $deleteIds = $subscriptions->where('id', '!=', $keepId)->pluck('id');
            
            if ($deleteIds->isNotEmpty()) {
                DB::table('reseller_subscriptions')->whereIn('id', $deleteIds)->delete();
                echo "Removed " . $deleteIds->count() . " duplicate reseller_subscriptions for stripe_subscription_id: " . $duplicate->stripe_subscription_id . "\n";
            }
        }
    }
};