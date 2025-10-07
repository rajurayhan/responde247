<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPackage;

class SubscriptionPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter',
                'description' => 'Perfect for small businesses',
                'price' => 50.00,
                'yearly_price' => 500.00, // 10 months worth (2 months free)
                'voice_agents_limit' => 1,
                'monthly_minutes_limit' => 100,
                'extra_per_minute_charge' => 0.10,
                'features' => ['1 Voice Agent', '100 minutes/month', 'Basic Analytics', 'Email Support'],
                'support_level' => 'email',
                'analytics_level' => 'basic',
                'is_popular' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Professional',
                'description' => 'Perfect for growing businesses',
                'price' => 250.00,
                'yearly_price' => 2500.00, // 10 months worth (2 months free)
                'voice_agents_limit' => 5,
                'monthly_minutes_limit' => 600,
                'extra_per_minute_charge' => 0.08,
                'features' => ['5 Voice Agents', '400 minutes/month', 'Advanced Analytics', 'Priority Support', 'Custom Integrations'],
                'support_level' => 'priority',
                'analytics_level' => 'advanced',
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'For large organizations',
                'price' => 450.00,
                'yearly_price' => 4500.00, // 10 months worth (2 months free)
                'voice_agents_limit' => 10, // Unlimited
                'monthly_minutes_limit' => 1000, // Unlimited
                'extra_per_minute_charge' => 0.05,
                'features' => ['10 Voice Agents', '1000 minutes', 'Custom Analytics', '24/7 Phone Support', 'Dedicated Account Manager'],
                'support_level' => 'dedicated',
                'analytics_level' => 'custom',
                'is_popular' => false,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            SubscriptionPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
