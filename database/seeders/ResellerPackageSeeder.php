<?php

namespace Database\Seeders;

use App\Models\ResellerPackage;
use Illuminate\Database\Seeder;

class ResellerPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter Reseller',
                'description' => 'Perfect for small resellers getting started with voice AI technology.',
                'price' => 99.00,
                'yearly_price' => 990.00,
                'voice_agents_limit' => 10,
                'monthly_minutes_limit' => 1000,
                'extra_per_minute_charge' => 0.05,
                'features' => [
                    'Basic voice agents',
                    'Standard support',
                    'Basic analytics',
                    'Email support'
                ],
                'support_level' => 'standard',
                'analytics_level' => 'basic',
                'is_popular' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Professional Reseller',
                'description' => 'Ideal for growing resellers with multiple clients and higher usage.',
                'price' => 299.00,
                'yearly_price' => 2990.00,
                'voice_agents_limit' => 50,
                'monthly_minutes_limit' => 5000,
                'extra_per_minute_charge' => 0.04,
                'features' => [
                    'Advanced voice agents',
                    'Priority support',
                    'Advanced analytics',
                    'Phone & email support',
                    'Custom branding',
                    'API access'
                ],
                'support_level' => 'priority',
                'analytics_level' => 'advanced',
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise Reseller',
                'description' => 'For large resellers with enterprise clients and high-volume usage.',
                'price' => 799.00,
                'yearly_price' => 7990.00,
                'voice_agents_limit' => -1, // Unlimited
                'monthly_minutes_limit' => 20000,
                'extra_per_minute_charge' => 0.03,
                'features' => [
                    'Unlimited voice agents',
                    'Enterprise support',
                    'Enterprise analytics',
                    'Dedicated account manager',
                    'Custom integrations',
                    'White-label solution',
                    'SLA guarantee',
                    'Advanced security'
                ],
                'support_level' => 'enterprise',
                'analytics_level' => 'enterprise',
                'is_popular' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Unlimited Reseller',
                'description' => 'Complete unlimited package for high-volume resellers.',
                'price' => 1499.00,
                'yearly_price' => 14990.00,
                'voice_agents_limit' => -1, // Unlimited
                'monthly_minutes_limit' => -1, // Unlimited
                'extra_per_minute_charge' => 0.02,
                'features' => [
                    'Unlimited everything',
                    '24/7 enterprise support',
                    'Full analytics suite',
                    'Dedicated infrastructure',
                    'Custom development',
                    'Priority feature requests',
                    'Advanced monitoring',
                    'Compliance tools'
                ],
                'support_level' => 'enterprise',
                'analytics_level' => 'enterprise',
                'is_popular' => false,
                'is_active' => true,
            ]
        ];

        foreach ($packages as $package) {
            ResellerPackage::create($package);
        }
    }
}
