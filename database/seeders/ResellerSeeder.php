<?php

namespace Database\Seeders;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ResellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use database transaction to ensure data consistency
        DB::transaction(function () {
            // Create the Sulus.ai reseller
            $reseller = Reseller::firstOrCreate(
                ['org_name' => 'Sulus.ai'],
                [
                    'org_name' => 'Sulus.ai',
                    'logo_address' => null, // Can be updated later
                    'company_email' => 'info@sulus.ai',
                    'domain' => 'sulus.ai',
                    'status' => 'active',
                ]
            );

            $this->command->info("Reseller '{$reseller->org_name}' created/found with ID: {$reseller->id}");

            // Create the admin user for the reseller
            $adminUser = User::firstOrCreate(
                ['email' => 'raju@sulus.ai'],
                [
                    'name' => 'Raju',
                    'email' => 'raju@sulus.ai',
                    'password' => Hash::make('password123'), // Default password, should be changed
                    'role' => 'reseller_admin',
                    'phone' => null,
                    'company' => 'Sulus.ai',
                    'bio' => 'Admin user for Sulus.ai reseller',
                    'status' => 'active',
                    'reseller_id' => $reseller->id,
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info("Admin user '{$adminUser->name}' created/found with email: {$adminUser->email}");
            $this->command->info("Reseller seeding completed successfully!");
        });
    }
}
