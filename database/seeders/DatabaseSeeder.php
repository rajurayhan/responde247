<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Assistant;
use App\Services\VapiService;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create subscription packages
        // $this->call([
        //     SubscriptionPackageSeeder::class,
        // ]);

        // Create settings and templates
        $this->call([
            SettingsSeeder::class,
        ]);

        // Create features
        $this->call([
            FeatureSeeder::class,
        ]);

        // Create admin user
        $adminUser = User::create([
            'name' => 'Raju',
            'email' => 'raju@xpartfone.com',
            'password' => Hash::make('raju@2025'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create test user
        $testUser = User::create([
            'name' => 'Akib',
            'email' => 'akib@xpartfone.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create additional test users
        User::create([
            'name' => 'Riyad',
            'email' => 'riyad@xpartfone.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Import assistants from Vapi API
        $this->importVapiAssistants($adminUser, $testUser);
    }

    /**
     * Import assistants from Vapi API and map them to users
     */
    private function importVapiAssistants(User $adminUser, User $testUser): void
    {
        $vapiService = app(VapiService::class);
        
        try {
            $vapiAssistants = $vapiService->getAssistants();
            
            if (empty($vapiAssistants)) {
                $this->command->info('No assistants found in Vapi API');
                return;
            }

            $this->command->info("Found " . count($vapiAssistants) . " assistants in Vapi API");

            $importedCount = 0;
            $skippedCount = 0;

            foreach ($vapiAssistants as $vapiAssistant) {
                try {
                    $assistantId = $vapiAssistant['id'] ?? null;
                    $assistantName = $vapiAssistant['name'] ?? 'Unnamed Assistant';

                    if (!$assistantId) {
                        $this->command->warn("Skipping assistant without ID: " . $assistantName);
                        $skippedCount++;
                        continue;
                    }

                    // Check if assistant already exists in database
                    $existingAssistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
                    if ($existingAssistant) {
                        $this->command->line("Assistant already exists: {$assistantName} (ID: {$assistantId})");
                        $skippedCount++;
                        continue;
                    }

                    // Determine user to assign the assistant to
                    $userId = $vapiAssistant['metadata']['user_id'] ?? null;
                    
                    if (!$userId) {
                        // Assign to admin user if no user_id found
                        $userId = $adminUser->id;
                    } else {
                        // Check if the user_id exists in our database
                        $user = User::find($userId);
                        if (!$user) {
                            // If user doesn't exist, assign to admin
                            $userId = $adminUser->id;
                        }
                    }

                    // Create assistant in database
                    Assistant::create([
                        'name' => $assistantName,
                        'user_id' => $userId,
                        'vapi_assistant_id' => $assistantId,
                        'created_by' => $userId,
                    ]);

                    $this->command->line("Imported: {$assistantName} (ID: {$assistantId})");
                    $importedCount++;

                } catch (\Exception $e) {
                    $error = "Error processing assistant {$assistantName}: " . $e->getMessage();
                    $this->command->error($error);
                    Log::error($error);
                }
            }

            $this->command->info("Import completed! Imported: {$importedCount}, Skipped: {$skippedCount}");

        } catch (\Exception $e) {
            $this->command->error("Error connecting to Vapi API: " . $e->getMessage());
            Log::error("Vapi API connection error: " . $e->getMessage());
        }
    }
}
