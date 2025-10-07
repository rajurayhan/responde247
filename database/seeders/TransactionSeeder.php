<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\User;
use App\Models\SubscriptionPackage;
use App\Models\UserSubscription;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $packages = SubscriptionPackage::all();

        if ($users->isEmpty() || $packages->isEmpty()) {
            $this->command->info('No users or packages found. Please run UserSeeder and PackageSeeder first.');
            return;
        }

        // Create sample transactions
        $transactionTypes = ['subscription', 'upgrade', 'renewal', 'refund', 'trial'];
        $paymentMethods = ['stripe', 'paypal', 'manual'];
        $statuses = ['pending', 'completed', 'failed', 'refunded', 'cancelled'];

        foreach ($users as $user) {
            // Create 3-5 transactions per user
            $numTransactions = rand(3, 5);
            
            for ($i = 0; $i < $numTransactions; $i++) {
                $package = $packages->random();
                $status = $statuses[array_rand($statuses)];
                $type = $transactionTypes[array_rand($transactionTypes)];
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'subscription_package_id' => $package->id,
                    'user_subscription_id' => null, // Will be set if subscription exists
                    'amount' => $package->price,
                    'currency' => 'USD',
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'payment_method_id' => $paymentMethod === 'stripe' ? 'pi_' . strtoupper(uniqid()) : null,
                    'payment_details' => [
                        'method' => $paymentMethod,
                        'processor' => $paymentMethod === 'stripe' ? 'stripe' : ($paymentMethod === 'paypal' ? 'paypal' : 'manual'),
                        'last4' => $paymentMethod === 'stripe' ? '4242' : null,
                    ],
                    'billing_email' => $user->email,
                    'billing_name' => $user->name,
                    'billing_address' => '123 Main St',
                    'billing_city' => 'New York',
                    'billing_state' => 'NY',
                    'billing_country' => 'USA',
                    'billing_postal_code' => '10001',
                    'type' => $type,
                    'description' => ucfirst($type) . ' for ' . $package->name . ' package',
                    'metadata' => [
                        'source' => 'seeder',
                        'demo' => true,
                    ],
                    'processed_at' => $status === 'completed' ? now() : null,
                    'failed_at' => $status === 'failed' ? now() : null,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

                // Update created_at to be more realistic
                $transaction->update([
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $this->command->info('Sample transactions created successfully!');
    }
}
