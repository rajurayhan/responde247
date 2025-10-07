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
            // Create the Responde247.com reseller
            $reseller = Reseller::firstOrCreate(
                ['org_name' => 'Responde247.com'],
                [
                    'org_name' => 'Responde247.com',
                    'logo_address' => null, // Can be updated later
                    'company_email' => 'info@Responde247.com',
                    'domain' => 'responde247.com',
                    'status' => 'active',
                ]
            );

            $this->command->info("Reseller '{$reseller->org_name}' created/found with ID: {$reseller->id}");

            // Create the admin user for the reseller
            $adminUser = User::firstOrCreate(
                ['email' => 'send2raju.bd@gmail.com'],
                [
                    'name' => 'Raju',
                    'email' => 'send2raju.bd@gmail.com',
                    'password' => Hash::make('raju@2025'), // Default password, should be changed
                    'role' => 'reseller_admin',
                    'phone' => null,
                    'company' => 'Responde247.com',
                    'bio' => 'Admin user for Responde247.com reseller',
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
