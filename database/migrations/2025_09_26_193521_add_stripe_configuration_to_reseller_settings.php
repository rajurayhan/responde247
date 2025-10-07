<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Stripe configuration settings to reseller_settings table
        // We'll use the existing reseller_settings table structure
        
        // Insert default Stripe settings for existing resellers
        $resellers = \App\Models\Reseller::all();
        
        foreach ($resellers as $reseller) {
            $stripeSettings = [
                [
                    'reseller_id' => $reseller->id,
                    'key' => 'stripe_publishable_key',
                    'value' => env('STRIPE_PUBLISHABLE_KEY', ''),
                    'type' => 'text',
                    'group' => 'stripe',
                    'label' => 'Stripe Publishable Key',
                    'description' => 'Your Stripe publishable key for frontend integration'
                ],
                [
                    'reseller_id' => $reseller->id,
                    'key' => 'stripe_secret_key',
                    'value' => env('STRIPE_SECRET_KEY', ''),
                    'type' => 'password',
                    'group' => 'stripe',
                    'label' => 'Stripe Secret Key',
                    'description' => 'Your Stripe secret key for backend operations'
                ],
                [
                    'reseller_id' => $reseller->id,
                    'key' => 'stripe_webhook_secret',
                    'value' => env('STRIPE_WEBHOOK_SECRET', ''),
                    'type' => 'password',
                    'group' => 'stripe',
                    'label' => 'Stripe Webhook Secret',
                    'description' => 'Your Stripe webhook endpoint secret for verification'
                ],
                [
                    'reseller_id' => $reseller->id,
                    'key' => 'stripe_test_mode',
                    'value' => env('STRIPE_TEST_MODE', 'true'),
                    'type' => 'boolean',
                    'group' => 'stripe',
                    'label' => 'Test Mode',
                    'description' => 'Enable Stripe test mode (true for testing, false for production)'
                ],
                [
                    'reseller_id' => $reseller->id,
                    'key' => 'stripe_api_version',
                    'value' => env('STRIPE_API_VERSION', '2024-06-20'),
                    'type' => 'text',
                    'group' => 'stripe',
                    'label' => 'Stripe API Version',
                    'description' => 'Stripe API version to use'
                ],
                [
                    'reseller_id' => $reseller->id,
                    'key' => 'stripe_currency',
                    'value' => env('STRIPE_CURRENCY', 'usd'),
                    'type' => 'text',
                    'group' => 'stripe',
                    'label' => 'Default Currency',
                    'description' => 'Default currency for payments'
                ]
            ];
            
            foreach ($stripeSettings as $setting) {
                \App\Models\ResellerSetting::setValue(
                    $reseller->id,
                    $setting['key'],
                    $setting['value'],
                    $setting['label'],
                    $setting['type'],
                    $setting['group'],
                    $setting['description']
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Stripe settings from reseller_settings table
        \App\Models\ResellerSetting::where('group', 'stripe')->delete();
    }
};