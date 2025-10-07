<?php

namespace Database\Seeders;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ResellerWithAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample resellers with their admin users
        $resellers = [
            [
                'org_name' => 'Acme Corporation',
                'company_email' => 'contact@acme.com',
                'domain' => 'acme.com',
                'logo_address' => 'https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=ACME',
                'status' => 'active',
                'admin' => [
                    'name' => 'John Smith',
                    'email' => 'admin@acme.com',
                    'phone' => '+1 (555) 123-4567'
                ]
            ],
            [
                'org_name' => 'TechStart Inc',
                'company_email' => 'hello@techstart.io',
                'domain' => 'techstart.io',
                'logo_address' => 'https://via.placeholder.com/200x200/10B981/FFFFFF?text=TS',
                'status' => 'active',
                'admin' => [
                    'name' => 'Sarah Johnson',
                    'email' => 'sarah@techstart.io',
                    'phone' => '+1 (555) 987-6543'
                ]
            ],
            [
                'org_name' => 'Global Solutions Ltd',
                'company_email' => 'info@globalsolutions.com',
                'domain' => 'globalsolutions.com',
                'logo_address' => 'https://via.placeholder.com/200x200/EF4444/FFFFFF?text=GS',
                'status' => 'inactive',
                'admin' => [
                    'name' => 'Michael Brown',
                    'email' => 'admin@globalsolutions.com',
                    'phone' => '+1 (555) 456-7890'
                ]
            ]
        ];

        foreach ($resellers as $resellerData) {
            // Create the reseller
            $reseller = Reseller::create([
                'org_name' => $resellerData['org_name'],
                'company_email' => $resellerData['company_email'],
                'domain' => $resellerData['domain'],
                'logo_address' => $resellerData['logo_address'],
                'status' => $resellerData['status'],
            ]);

            // Create the admin user for this reseller
            User::create([
                'name' => $resellerData['admin']['name'],
                'email' => $resellerData['admin']['email'],
                'password' => Hash::make('password123'),
                'role' => 'reseller_admin',
                'reseller_id' => $reseller->id,
                'phone' => $resellerData['admin']['phone'],
                'company' => $reseller->org_name,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $this->command->info("Created reseller '{$reseller->org_name}' with admin user '{$resellerData['admin']['email']}'");
        }
    }
}
