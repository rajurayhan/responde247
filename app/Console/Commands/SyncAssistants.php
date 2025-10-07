<?php

namespace App\Console\Commands;

use App\Models\Assistant;
use App\Models\User;
use App\Services\VapiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAssistants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assistants:sync 
                            {--user-id= : Sync assistants for a specific user ID}
                            {--assistant-id= : Sync a specific assistant by Vapi ID}
                            {--force : Force update existing assistants}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize assistants data from Vapi.ai (source of truth) with local database';

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
        $this->info('Starting assistant synchronization...');

        try {
            if ($this->option('assistant-id')) {
                $this->syncSpecificAssistant($this->option('assistant-id'));
            } elseif ($this->option('user-id')) {
                $this->syncUserAssistants($this->option('user-id'));
            } else {
                $this->syncAllAssistants();
            }

            $this->info('Assistant synchronization completed successfully!');
        } catch (\Exception $e) {
            $this->error('Synchronization failed: ' . $e->getMessage());
            Log::error('Assistant sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Sync a specific assistant by Vapi ID
     */
    protected function syncSpecificAssistant(string $assistantId)
    {
        $this->info("Syncing specific assistant: {$assistantId}");

        try {
            // Get assistant data from Vapi
            $vapiAssistant = $this->vapiService->getAssistant($assistantId);
            
            if (!$vapiAssistant) {
                $this->error("Assistant {$assistantId} not found in Vapi");
                return;
            }

            $this->processAssistant($vapiAssistant, $assistantId);
        } catch (\Exception $e) {
            $this->error("Failed to sync assistant {$assistantId}: " . $e->getMessage());
        }
    }

    /**
     * Sync all assistants for a specific user - Vapi is the source of truth
     */
    protected function syncUserAssistants(string $userId)
    {
        $this->info("Syncing assistants for user ID: {$userId} from Vapi...");

        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found");
            return;
        }

        try {
            // Get all assistants from Vapi (source of truth)
            $vapiAssistants = $this->vapiService->getAssistants();
            
            if (!$vapiAssistants || empty($vapiAssistants)) {
                $this->warn('No assistants found in Vapi or unable to retrieve them');
                return;
            }

            $synced = 0;
            $errors = 0;
            $created = 0;
            $updated = 0;

            // Filter assistants that belong to this user (by email in metadata)
            foreach ($vapiAssistants as $vapiAssistant) {
                $metadata = $vapiAssistant['metadata'] ?? [];
                $userEmail = $metadata['user_email'] ?? null;
                
                // Check if this assistant belongs to the specified user
                if ($userEmail && $userEmail === $user->email) {
                    try {
                        $result = $this->processAssistant($vapiAssistant, $vapiAssistant['id']);
                        if ($result === 'created') {
                            $created++;
                        } elseif ($result === 'updated') {
                            $updated++;
                        }
                        $synced++;
                    } catch (\Exception $e) {
                        $this->error("Failed to sync assistant {$vapiAssistant['id']}: " . $e->getMessage());
                        $errors++;
                    }
                }
            }

            $this->info("User sync completed: {$synced} synced ({$created} created, {$updated} updated), {$errors} errors");
        } catch (\Exception $e) {
            $this->error("Failed to sync user assistants from Vapi: " . $e->getMessage());
        }
    }

    /**
     * Sync all assistants in the system - Vapi is the source of truth
     */
    protected function syncAllAssistants()
    {
        $this->info('Syncing all assistants from Vapi (source of truth)...');

        try {
            // Get all assistants from Vapi (source of truth)
            $vapiAssistants = $this->vapiService->getAssistants();
            
            if (!$vapiAssistants || empty($vapiAssistants)) {
                $this->warn('No assistants found in Vapi or unable to retrieve them');
                return;
            }

            $this->info("Found " . count($vapiAssistants) . " assistants in Vapi");

            $synced = 0;
            $errors = 0;
            $created = 0;
            $updated = 0;

            // Process each assistant from Vapi
            foreach ($vapiAssistants as $vapiAssistant) {
                try {
                    $result = $this->processAssistant($vapiAssistant, $vapiAssistant['id']);
                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    }
                    $synced++;
                } catch (\Exception $e) {
                    $this->error("Failed to sync assistant {$vapiAssistant['id']}: " . $e->getMessage());
                    $errors++;
                }
            }

            $this->info("Sync completed: {$synced} synced ({$created} created, {$updated} updated), {$errors} errors");
        } catch (\Exception $e) {
            $this->error("Failed to sync assistants from Vapi: " . $e->getMessage());
            Log::error('Assistant sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process and update assistant data
     */
    protected function processAssistant(array $vapiAssistant, string $assistantId)
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Check if assistant exists in local database
        $localAssistant = Assistant::where('vapi_assistant_id', $assistantId)->first();

        if ($dryRun) {
            if ($localAssistant) {
                $this->line("Would update assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
            } else {
                $this->line("Would create assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
            }
            return 'dry-run';
        }

        if ($localAssistant) {
            // Update existing assistant
            if ($force || $this->shouldUpdate($localAssistant, $vapiAssistant)) {
                $this->updateAssistant($localAssistant, $vapiAssistant);
                $this->info("Updated assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
                return 'updated';
            } else {
                $this->line("Assistant {$vapiAssistant['name']} (ID: {$assistantId}) is up to date");
                return 'up-to-date';
            }
        } else {
            // Create new assistant
            $this->createAssistant($vapiAssistant);
            $this->info("Created assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
            return 'created';
        }
    }


    /**
     * Update existing assistant with comprehensive Vapi data mapping
     */
    protected function updateAssistant(Assistant $localAssistant, array $vapiAssistant)
    {
        $updateData = $this->mapVapiDataToAssistantFields($vapiAssistant);
        
        // Preserve existing user_id and created_by
        unset($updateData['user_id'], $updateData['created_by']);
        
        $localAssistant->update($updateData);
        
        Log::info('Assistant updated from Vapi sync', [
            'assistant_id' => $localAssistant->id,
            'vapi_assistant_id' => $vapiAssistant['id'],
            'name' => $vapiAssistant['name'],
            'updated_fields' => array_keys($updateData)
        ]);
    }

    /**
     * Create new assistant with comprehensive Vapi data mapping
     */
    protected function createAssistant(array $vapiAssistant)
    {
        // Try to find the user by email in metadata
        $userEmail = $vapiAssistant['metadata']['user_email'] ?? null;
        $userId = null;
        $createdBy = null;

        if ($userEmail) {
            $user = User::where('email', $userEmail)->first();
            if ($user) {
                $userId = $user->id;
                $createdBy = $user->id;
            }
        }

        // If no user found, use the first admin user as fallback
        if (!$userId) {
            $adminUser = User::where('role', 'admin')->first();
            if ($adminUser) {
                $userId = $adminUser->id;
                $createdBy = $adminUser->id;
            }
        }

        $createData = $this->mapVapiDataToAssistantFields($vapiAssistant);
        
        // Override with determined user assignments
        $createData['user_id'] = $userId;
        $createData['created_by'] = $createdBy;

        $assistant = Assistant::create($createData);
        
        Log::info('Assistant created from Vapi sync', [
            'assistant_id' => $assistant->id,
            'vapi_assistant_id' => $vapiAssistant['id'],
            'name' => $vapiAssistant['name'],
            'user_id' => $userId,
            'created_fields' => array_keys($createData)
        ]);
        
        return $assistant;
    }


    /**
     * Map Vapi assistant data to database fields
     */
    protected function mapVapiDataToAssistantFields(array $vapiAssistant): array
    {
        $metadata = $vapiAssistant['metadata'] ?? [];
        
        return [
            // Basic fields
            'name' => $vapiAssistant['name'],
            'vapi_assistant_id' => $vapiAssistant['id'],
            'type' => $metadata['type'] ?? 'demo',
            'phone_number' => $metadata['assistant_phone_number'] ?? null,
            'webhook_url' => $metadata['webhook_url'] ?? null,
            
            // Transcriber configuration
            'transcriber' => $vapiAssistant['transcriber'] ?? null,
            
            // Model configuration
            'model' => $vapiAssistant['model'] ?? null,
            
            // Voice configuration
            'voice' => $vapiAssistant['voice'] ?? null,
            
            // First message configuration
            'first_message' => $vapiAssistant['firstMessage'] ?? null,
            'first_message_interruptions_enabled' => $vapiAssistant['firstMessageInterruptionsEnabled'] ?? false,
            'first_message_mode' => $vapiAssistant['firstMessageMode'] ?? 'assistant-speaks-first',
            
            // Voicemail detection
            'voicemail_detection' => $vapiAssistant['voicemailDetection'] ?? null,
            
            // Messages configuration
            'client_messages' => $vapiAssistant['clientMessages'] ?? null,
            'server_messages' => $vapiAssistant['serverMessages'] ?? null,
            
            // Call configuration
            'max_duration_seconds' => $vapiAssistant['maxDurationSeconds'] ?? 600,
            'background_sound' => $vapiAssistant['backgroundSound'] ?? null,
            'model_output_in_messages_enabled' => $vapiAssistant['modelOutputInMessagesEnabled'] ?? false,
            
            // Transport configurations
            'transport_configurations' => $vapiAssistant['transportConfigurations'] ?? null,
            
            // Observability
            'observability_plan' => $vapiAssistant['observabilityPlan'] ?? null,
            
            // Credentials
            'credential_ids' => $vapiAssistant['credentialIds'] ?? null,
            'credentials' => $vapiAssistant['credentials'] ?? null,
            
            // Hooks
            'hooks' => $vapiAssistant['hooks'] ?? null,
            
            // Voicemail and end call messages
            'voicemail_message' => $vapiAssistant['voicemailMessage'] ?? null,
            'end_call_message' => $vapiAssistant['endCallMessage'] ?? null,
            'end_call_phrases' => $vapiAssistant['endCallPhrases'] ?? null,
            
            // Compliance
            'compliance_plan' => $vapiAssistant['compliancePlan'] ?? null,
            
            // Background speech denoising
            'background_speech_denoising_plan' => $vapiAssistant['backgroundSpeechDenoisingPlan'] ?? null,
            
            // Analysis plan
            'analysis_plan' => $vapiAssistant['analysisPlan'] ?? null,
            
            // Artifact plan
            'artifact_plan' => $vapiAssistant['artifactPlan'] ?? null,
            
            // Speaking plans
            'start_speaking_plan' => $vapiAssistant['startSpeakingPlan'] ?? null,
            'stop_speaking_plan' => $vapiAssistant['stopSpeakingPlan'] ?? null,
            
            // Monitor plan
            'monitor_plan' => $vapiAssistant['monitorPlan'] ?? null,
            
            // Keypad input plan
            'keypad_input_plan' => $vapiAssistant['keypadInputPlan'] ?? null,
        ];
    }

    /**
     * Enhanced shouldUpdate method with comprehensive field comparison
     */
    protected function shouldUpdate(Assistant $localAssistant, array $vapiAssistant): bool
    {
        // Check basic fields
        if ($localAssistant->name !== $vapiAssistant['name']) {
            return true;
        }

        // Check type
        $metadata = $vapiAssistant['metadata'] ?? [];
        $vapiType = $metadata['type'] ?? 'demo';
        if ($localAssistant->type !== $vapiType) {
            return true;
        }

        // Check phone number
        $vapiPhone = $metadata['assistant_phone_number'] ?? null;
        if ($localAssistant->phone_number !== $vapiPhone) {
            return true;
        }

        // Check webhook URL
        $vapiWebhook = $metadata['webhook_url'] ?? null;
        if ($localAssistant->webhook_url !== $vapiWebhook) {
            return true;
        }

        // Check JSON configuration fields
        $jsonFields = [
            'transcriber', 'model', 'voice', 'voicemail_detection',
            'client_messages', 'server_messages', 'transport_configurations',
            'observability_plan', 'credential_ids', 'credentials', 'hooks',
            'end_call_phrases', 'compliance_plan', 'background_speech_denoising_plan',
            'analysis_plan', 'artifact_plan', 'start_speaking_plan',
            'stop_speaking_plan', 'monitor_plan', 'keypad_input_plan'
        ];

        foreach ($jsonFields as $field) {
            $localValue = $localAssistant->$field;
            $vapiValue = $vapiAssistant[$this->camelToSnakeCase($field)] ?? null;
            
            if ($this->jsonValuesDiffer($localValue, $vapiValue)) {
                return true;
            }
        }

        // Check text fields
        $textFields = [
            'first_message' => 'firstMessage',
            'voicemail_message' => 'voicemailMessage',
            'end_call_message' => 'endCallMessage'
        ];

        foreach ($textFields as $dbField => $vapiField) {
            if ($localAssistant->$dbField !== ($vapiAssistant[$vapiField] ?? null)) {
                return true;
            }
        }

        // Check boolean fields
        $booleanFields = [
            'first_message_interruptions_enabled' => 'firstMessageInterruptionsEnabled',
            'model_output_in_messages_enabled' => 'modelOutputInMessagesEnabled'
        ];

        foreach ($booleanFields as $dbField => $vapiField) {
            $vapiValue = $vapiAssistant[$vapiField] ?? false;
            if ($localAssistant->$dbField != $vapiValue) {
                return true;
            }
        }

        // Check string fields
        if ($localAssistant->first_message_mode !== ($vapiAssistant['firstMessageMode'] ?? 'assistant-speaks-first')) {
            return true;
        }

        if ($localAssistant->background_sound !== ($vapiAssistant['backgroundSound'] ?? null)) {
            return true;
        }

        // Check integer fields
        if ($localAssistant->max_duration_seconds !== ($vapiAssistant['maxDurationSeconds'] ?? 600)) {
            return true;
        }

        return false;
    }

    /**
     * Convert camelCase to snake_case
     */
    protected function camelToSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Compare JSON values for differences
     */
    protected function jsonValuesDiffer($localValue, $vapiValue): bool
    {
        // Handle null values
        if ($localValue === null && $vapiValue === null) {
            return false;
        }
        
        if ($localValue === null || $vapiValue === null) {
            return true;
        }

        // Convert to arrays for comparison
        $localArray = is_string($localValue) ? json_decode($localValue, true) : $localValue;
        $vapiArray = is_string($vapiValue) ? json_decode($vapiValue, true) : $vapiValue;

        return $localArray !== $vapiArray;
    }
}
