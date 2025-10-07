<?php

namespace App\Console\Commands;

use App\Models\Assistant;
use App\Models\User;
use App\Services\VapiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportVapiAssistants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapi:import-assistants {--user-id= : Specific user ID to assign assistants to} {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import assistants from Vapi API and map them to users in the database';

    protected $vapiService;

    public function __construct(VapiService $vapiService)
    {
        parent::__construct();
        $this->vapiService = $vapiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Vapi assistants import...');

        // Get all assistants from Vapi
        $vapiAssistants = $this->vapiService->getAssistants();

        if (empty($vapiAssistants)) {
            $this->warn('No assistants found in Vapi API');
            return 1;
        }

        $this->info("Found " . count($vapiAssistants) . " assistants in Vapi API");

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($vapiAssistants as $vapiAssistant) {
            try {
                $assistantId = $vapiAssistant['id'] ?? null;
                $assistantName = $vapiAssistant['name'] ?? 'Unnamed Assistant';

                if (!$assistantId) {
                    $this->warn("Skipping assistant without ID: " . $assistantName);
                    $skippedCount++;
                    continue;
                }

                // Check if assistant already exists in database
                $existingAssistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
                if ($existingAssistant) {
                    $this->line("Assistant already exists: {$assistantName} (ID: {$assistantId})");
                    $skippedCount++;
                    continue;
                }

                // Determine user to assign the assistant to
                $userId = $this->option('user-id');
                if (!$userId) {
                    // Try to get user_id from Vapi metadata
                    $userId = $vapiAssistant['metadata']['user_id'] ?? null;
                    
                    if (!$userId) {
                        // Assign to admin user if no user_id found
                        $adminUser = User::where('role', 'admin')->first();
                        if ($adminUser) {
                            $userId = $adminUser->id;
                        } else {
                            $this->warn("No admin user found, skipping assistant: {$assistantName}");
                            $skippedCount++;
                            continue;
                        }
                    }
                }

                // Verify user exists
                $user = User::find($userId);
                if (!$user) {
                    $this->warn("User with ID {$userId} not found, skipping assistant: {$assistantName}");
                    $skippedCount++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("Would import: {$assistantName} (ID: {$assistantId}) -> User: {$user->name} ({$user->email})");
                    $importedCount++;
                } else {
                    // Create assistant in database
                    Assistant::create([
                        'name' => $assistantName,
                        'user_id' => $userId,
                        'vapi_assistant_id' => $assistantId,
                        'created_by' => $userId,
                    ]);

                    $this->line("Imported: {$assistantName} (ID: {$assistantId}) -> User: {$user->name} ({$user->email})");
                    $importedCount++;
                }

            } catch (\Exception $e) {
                $error = "Error processing assistant {$assistantName}: " . $e->getMessage();
                $this->error($error);
                $errors[] = $error;
                Log::error($error);
            }
        }

        $this->newLine();
        $this->info("Import completed!");
        $this->info("Imported: {$importedCount}");
        $this->info("Skipped: {$skippedCount}");

        if (!empty($errors)) {
            $this->error("Errors occurred: " . count($errors));
            foreach ($errors as $error) {
                $this->error($error);
            }
        }

        return 0;
    }
}
