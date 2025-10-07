<?php

namespace Database\Seeders;

use App\Models\Reseller;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class DefaultResellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use database transaction to ensure data consistency
        DB::transaction(function () {
            // Extract domain from APP_URL
            $appUrl = Config::get('app.url', 'http://localhost:8000');
            $parsedUrl = parse_url($appUrl);
            $domain = $parsedUrl['host'] ?? 'localhost';
            
            // Remove 'www.' prefix if present
            $domain = preg_replace('/^www\./', '', $domain);
            
            $this->command->info("Extracted domain from APP_URL: {$domain}");

            // Check if reseller with this domain already exists
            $existingReseller = Reseller::where('domain', $domain)->first();
            
            if ($existingReseller) {
                $this->command->info("Reseller with domain '{$domain}' already exists with ID: {$existingReseller->id}");
                $resellerId = $existingReseller->id;
            } else {
                // Create the default reseller
                $reseller = Reseller::create([
                    'org_name' => 'Sulus.AI',
                    'company_email' => 'support@sulusai.com',
                    'domain' => $domain,
                    'logo_address' => 'https://sulusai.com/storage/resellers/logo/logo_1759159705_68daa5992c844.png',
                    'status' => 'active',
                ]);

                $this->command->info("Created reseller '{$reseller->org_name}' with ID: {$reseller->id}");
                $resellerId = $reseller->id;
            }

            // Update all existing records in tables with reseller_id fields
            $this->updateExistingRecords($resellerId);
            
            $this->command->info("Default reseller seeding completed successfully!");
        });
    }

    /**
     * Update all existing records in tables that have reseller_id fields
     */
    private function updateExistingRecords(string $resellerId): void
    {
        $tablesWithResellerId = [
            'assistants',
            'user_subscriptions', 
            'transactions',
            'subscription_packages',
            'call_logs',
            'features',
            'settings',
            'contacts',
            'demo_requests',
            'users' // This table has reseller_id field based on the migration list
        ];

        foreach ($tablesWithResellerId as $table) {
            try {
                // Check if table exists and has reseller_id column
                if (DB::getSchemaBuilder()->hasTable($table) && 
                    DB::getSchemaBuilder()->hasColumn($table, 'reseller_id')) {
                    
                    $updatedCount = DB::table($table)
                        ->whereNull('reseller_id')
                        ->update(['reseller_id' => $resellerId]);
                    
                    if ($updatedCount > 0) {
                        $this->command->info("Updated {$updatedCount} records in {$table} table with reseller_id: {$resellerId}");
                    } else {
                        $this->command->info("No records to update in {$table} table");
                    }
                } else {
                    $this->command->warn("Table {$table} does not exist or does not have reseller_id column");
                }
            } catch (\Exception $e) {
                $this->command->error("Error updating {$table} table: " . $e->getMessage());
            }
        }
    }
}
