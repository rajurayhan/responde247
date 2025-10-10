<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Assistant;
use App\Models\CallLog;
use App\Models\User;
use Carbon\Carbon;
use getID3;

class SyncVapiCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapi:sync-calls 
                            {--assistant-id= : Sync calls for specific assistant ID}
                            {--limit=100 : Number of calls to fetch per assistant}
                            {--dry-run : Show what would be synced without making changes}
                            {--check-recordings : Check and download missing recording files for existing calls}
                            {--fix-reseller-ids : Check and update missing reseller_id fields for existing calls}
                            {--delay=2 : Delay in seconds between assistant processing to respect rate limits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync calls from Vapi API with local call logs database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Vapi calls sync...');

        // Get Vapi API token from environment
        $vapiToken = config('services.vapi.token');
        if (!$vapiToken) {
            $this->error('VAPI_API_KEY not found in environment variables');
            return 1;
        }

        $assistantId = $this->option('assistant-id');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $checkRecordings = $this->option('check-recordings');
        $fixResellerIds = $this->option('fix-reseller-ids');
        $delay = (int) $this->option('delay');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        if ($checkRecordings) {
            $this->info('CHECKING RECORDINGS MODE - Will check and download missing recording files');
        }

        if ($fixResellerIds) {
            $this->info('FIXING RESELLER IDS MODE - Will check and update missing reseller_id fields');
        }

        if ($delay > 0) {
            $this->info("Using {$delay} second delay between assistant processing to respect rate limits");
        }

        // Get assistants to sync
        $assistants = $this->getAssistants($assistantId);
        
        if ($assistants->isEmpty()) {
            $this->error('No assistants found to sync');
            return 1;
        }

        $this->info("Found {$assistants->count()} assistant(s) to sync");

        $totalSynced = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        if ($checkRecordings) {
            // Check recordings for existing calls
            $this->info("\nChecking recordings for existing calls...");
            $result = $this->checkAndDownloadMissingRecordings($assistantId, $dryRun);
            $totalSynced += $result['downloaded'];
            $totalSkipped += $result['skipped'];
            $totalErrors += $result['errors'];
        } elseif ($fixResellerIds) {
            // Fix missing reseller IDs for existing calls
            $this->info("\nFixing missing reseller_id fields for existing calls...");
            $result = $this->fixMissingResellerIds($assistantId, $dryRun);
            $totalSynced += $result['updated'];
            $totalSkipped += $result['skipped'];
            $totalErrors += $result['errors'];
        } else {
            // Normal sync process
            foreach ($assistants as $index => $assistant) {
                $this->info("\nSyncing calls for assistant: {$assistant->name} (ID: {$assistant->vapi_assistant_id})");
                
                try {
                    $result = $this->syncAssistantCalls($assistant, $limit, $dryRun);
                    $totalSynced += $result['synced'];
                    $totalSkipped += $result['skipped'];
                    $totalErrors += $result['errors'];
                    
                    $this->info("Assistant {$assistant->name}: {$result['synced']} synced, {$result['skipped']} skipped, {$result['errors']} errors");
                    
                    // Add delay between assistants (except for the last one)
                    if ($delay > 0 && $index < $assistants->count() - 1) {
                        $this->line("Waiting {$delay} seconds before processing next assistant...");
                        sleep($delay);
                    }
                } catch (\Exception $e) {
                    $this->error("Error syncing assistant {$assistant->name}: " . $e->getMessage());
                    $totalErrors++;
                    
                    // Still add delay even on error to respect rate limits
                    if ($delay > 0 && $index < $assistants->count() - 1) {
                        $this->line("Waiting {$delay} seconds before processing next assistant...");
                        sleep($delay);
                    }
                }
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        if ($checkRecordings) {
            $this->info("RECORDINGS CHECK SUMMARY:");
            $this->info("Total Downloaded: {$totalSynced}");
            $this->info("Total Skipped: {$totalSkipped}");
            $this->info("Total Errors: {$totalErrors}");
        } elseif ($fixResellerIds) {
            $this->info("RESELLER IDS FIX SUMMARY:");
            $this->info("Total Updated: {$totalSynced}");
            $this->info("Total Skipped: {$totalSkipped}");
            $this->info("Total Errors: {$totalErrors}");
        } else {
            $this->info("SYNC SUMMARY:");
            $this->info("Total Synced: {$totalSynced}");
            $this->info("Total Skipped: {$totalSkipped}");
            $this->info("Total Errors: {$totalErrors}");
        }
        
        if ($dryRun) {
            $this->warn('This was a dry run - no actual changes were made');
        }

        return 0;
    }

    /**
     * Get assistants to sync
     */
    private function getAssistants($assistantId = null)
    {
        $query = Assistant::whereNotNull('vapi_assistant_id');
        
        if ($assistantId) {
            $query->where('id', $assistantId);
        }

        return $query->get();
    }

    /**
     * Sync calls for a specific assistant
     */
    private function syncAssistantCalls(Assistant $assistant, int $limit, bool $dryRun)
    {
        $synced = 0;
        $skipped = 0;
        $errors = 0;

        try {
            // Fetch calls from Vapi API
            $calls = $this->fetchVapiCalls($assistant->vapi_assistant_id, $limit);
            
            if (empty($calls)) {
                $this->warn("No calls found for assistant {$assistant->name}");
                return ['synced' => 0, 'skipped' => 0, 'errors' => 0];
            }

            $this->info("Found " . count($calls) . " calls for assistant {$assistant->name}");

            foreach ($calls as $call) {
                try {
                    $result = $this->processCall($call, $assistant, $dryRun);
                    
                    if ($result === 'synced') {
                        $synced++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing call {$call['id']}: " . $e->getMessage());
                    $errors++;
                }
            }

        } catch (\Exception $e) {
            $this->error("Error fetching calls for assistant {$assistant->name}: " . $e->getMessage());
            $errors++;
        }

        return ['synced' => $synced, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Fetch calls from Vapi API with retry logic for rate limits
     */
    private function fetchVapiCalls(string $assistantId, int $limit)
    {
        $vapiToken = config('services.vapi.token');
        $baseUrl = config('services.vapi.base_url', 'https://api.vapi.ai');
        
        $maxRetries = 3;
        $baseDelay = 5; // Base delay in seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$vapiToken}",
                'Content-Type' => 'application/json',
            ])->get("{$baseUrl}/call", [
                'assistantId' => $assistantId,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!is_array($data)) {
                    throw new \Exception("Invalid response format from Vapi API");
                }

                return $data;
            }
            
            // Handle rate limit errors (429) with exponential backoff
            if ($response->status() === 429) {
                if ($attempt < $maxRetries) {
                    $delay = $baseDelay * pow(2, $attempt - 1); // Exponential backoff
                    $this->warn("Rate limit exceeded (429). Retrying in {$delay} seconds... (Attempt {$attempt}/{$maxRetries})");
                    sleep($delay);
                    continue;
                } else {
                    throw new \Exception("Rate limit exceeded (429) after {$maxRetries} attempts. Please try again later.");
                }
            }
            
            // For other errors, throw immediately
            throw new \Exception("Vapi API error: " . $response->status() . " - " . $response->body());
        }
    }

    /**
     * Process a single call
     */
    private function processCall(array $call, Assistant $assistant, bool $dryRun)
    {
        $callId = $call['id'];
        
        // Check if call already exists
        $existingCall = CallLog::where('call_id', $callId)->first();
        
        if ($existingCall) {
            if ($dryRun) {
                $this->line("Would skip existing call: {$callId}");
            } else {
                $this->line("Skipped existing call: {$callId}");
                
                // Check and update reseller_id if missing
                if (!$existingCall->reseller_id && $assistant->user->reseller_id) {
                    $existingCall->reseller_id = $assistant->user->reseller_id;
                    $existingCall->save();
                    $this->line("Updated reseller_id for call: {$callId}");
                    
                    Log::info('Updated reseller_id for existing call', [
                        'call_id' => $callId,
                        'assistant_id' => $assistant->id,
                        'reseller_id' => $assistant->user->reseller_id,
                        'user_id' => $assistant->user_id
                    ]);
                }
                
                // Check if recording file exists and download if missing
                if ($existingCall->call_record_file_name && $existingCall->status === 'completed') {
                    $this->checkAndDownloadCallRecording($existingCall, false);
                }
            }
            return 'skipped';
        }

        if ($dryRun) {
            $this->line("Would create new call: {$callId}");
        } else {
            $this->createCallLog($call, $assistant);
            $this->line("Created call: {$callId}");
        }
        
        return 'synced';
    }

    /**
     * Create a new call log entry
     */
    private function createCallLog(array $call, Assistant $assistant)
    {
        $callLog = new CallLog();
        $this->mapCallData($callLog, $call, $assistant);
        $callLog->save();

        Log::info('Created call log from Vapi sync', [
            'call_id' => $call['id'],
            'assistant_id' => $assistant->id,
            'reseller_id' => $assistant->user->reseller_id,
            'user_id' => $assistant->user_id
        ]);
    }

    /**
     * Update existing call log entry
     */
    private function updateCallLog(CallLog $callLog, array $call, Assistant $assistant)
    {
        $this->mapCallData($callLog, $call, $assistant);
        $callLog->save();

        Log::info('Updated call log from Vapi sync', [
            'call_id' => $call['id'],
            'assistant_id' => $assistant->id,
            'user_id' => $assistant->user_id
        ]);
    }

    /**
     * Map Vapi call data to CallLog model
     */
    private function mapCallData(CallLog $callLog, array $call, Assistant $assistant)
    {
        try {
            // Basic call information
            $callLog->call_id = $call['id'] ?? 'unknown';
            $callLog->assistant_id = $assistant->id;
            $callLog->user_id = $assistant->user_id;
            $callLog->reseller_id = $assistant->user->reseller_id;
        
            // Phone numbers - with proper null checks
            $callLog->phone_number = $call['phoneNumber']['number'] ?? null;
            $callLog->caller_number = $call['customer']['number'] ?? null;
            
            // Timing - with proper null checks
            $callLog->start_time = isset($call['startedAt']) && $call['startedAt'] ? Carbon::parse($call['startedAt']) : null;
            $callLog->end_time = isset($call['endedAt']) && $call['endedAt'] ? Carbon::parse($call['endedAt']) : null;
        
            // Calculate duration from timestamps (fallback)
            if ($callLog->start_time && $callLog->end_time) {
                $duration = $callLog->end_time->diffInSeconds($callLog->start_time);
                $callLog->duration = max(0, $duration); // Ensure non-negative duration
            }
        
            // Note: Duration will be updated from audio file if available during download
            
            // Status mapping - with proper null checks
            $callLog->status = $this->mapVapiStatus($call['status'] ?? null);
            $callLog->direction = $this->mapVapiDirection($call['type'] ?? null);
        
            // Cost information
            $callLog->cost = $call['cost'] ?? null;
            $callLog->currency = 'USD'; // Vapi costs are in USD
            
            // Store full webhook data
            $callLog->webhook_data = $call;
            
            // Extract transcript if available
            if (isset($call['artifact']['transcript'])) {
                $callLog->transcript = $call['artifact']['transcript'];
            }
            
            // Extract summary if available
            if (isset($call['analysis']['summary'])) {
                $callLog->summary = $call['analysis']['summary'];
            }
        
            // Extract metadata
            $metadata = [];
            if (isset($call['assistant']['metadata'])) {
                $metadata['assistant_metadata'] = $call['assistant']['metadata'];
            }
            if (isset($call['endedReason'])) {
                $metadata['ended_reason'] = $call['endedReason'];
            }
            if (isset($call['costBreakdown'])) {
                $metadata['cost_breakdown'] = $call['costBreakdown'];
            }
            
            if (!empty($metadata)) {
                $callLog->metadata = $metadata;
            }

            // Download call recording if available
            $this->downloadCallRecording($callLog, $call);
            
        } catch (\Exception $e) {
            $callId = $call['id'] ?? 'unknown';
            $this->error("Error mapping call data for call {$callId}: " . $e->getMessage());
            Log::error('Error mapping call data', [
                'call_id' => $call['id'] ?? 'unknown',
                'assistant_id' => $assistant->id,
                'error' => $e->getMessage(),
                'call_data_keys' => array_keys($call)
            ]);
            
            // Set minimal required fields to prevent complete failure
            $callLog->call_id = $call['id'] ?? 'unknown';
            $callLog->assistant_id = $assistant->id;
            $callLog->user_id = $assistant->user_id;
            $callLog->reseller_id = $assistant->user->reseller_id;
            $callLog->status = 'initiated';
            $callLog->direction = 'inbound';
            $callLog->webhook_data = $call;
        }
    }

    /**
     * Map Vapi status to our status enum
     */
    private function mapVapiStatus(?string $vapiStatus): string
    {
        if (!$vapiStatus) {
            return 'initiated';
        }

        $statusMap = [
            'scheduled' => 'initiated',
            'ringing' => 'ringing',
            'in-progress' => 'in-progress',
            'completed' => 'completed',
            'failed' => 'failed',
            'busy' => 'busy',
            'no-answer' => 'no-answer',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$vapiStatus] ?? 'initiated';
    }

    /**
     * Map Vapi call type to our direction enum
     */
    private function mapVapiDirection(?string $vapiType): string
    {
        if (!$vapiType) {
            return 'inbound';
        }

        return str_contains($vapiType, 'outbound') ? 'outbound' : 'inbound';
    }

    /**
     * Download call recording if available
     */
    private function downloadCallRecording(CallLog $callLog, array $call)
    {
        try {
            // Check for recording URL in different possible locations
            $recordingUrl = $call['recordingUrl'] ?? 
                           $call['artifact']['recordingUrl'] ?? 
                           $call['messages'][0]['artifact']['recordingUrl'] ?? 
                           null;

            if (!$recordingUrl) {
                return; // No recording available
            }

            // Generate alphanumeric filename (same as webhook processor)
            $fileName = $this->generateAlphanumericFileName();
            $fileExtension = 'wav'; // Vapi recordings are typically WAV
            $fullFileName = $fileName . '.' . $fileExtension;
            
            // Create recordings directory if it doesn't exist
            $directory = 'recordings';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            $filePath = $directory . '/' . $fullFileName;
            
            // Download the file
            $fileContent = file_get_contents($recordingUrl);
            if ($fileContent === false) {
                $this->warn("Failed to download recording for call: {$callLog->call_id}");
                return;
            }
            
            // Store the file
            $stored = Storage::disk('public')->put($filePath, $fileContent);
            if (!$stored) {
                $this->warn("Failed to store recording for call: {$callLog->call_id}");
                return;
            }
            
                    // Update call log with filename
        $callLog->call_record_file_name = $fullFileName;
        
        // Extract duration from audio file and update call log
        $duration = $this->extractAudioDuration($filePath);
        if ($duration !== null) {
            $callLog->duration = $duration;
            $this->line("Extracted duration from audio: {$duration} seconds");
        }
        
        $callLog->save();
        
        $this->line("Downloaded recording for call: {$callLog->call_id} -> {$fullFileName}");
        
        Log::info('Downloaded call recording from Vapi sync', [
            'call_id' => $callLog->call_id,
            'filename' => $fullFileName,
            'file_path' => $filePath,
            'file_size' => strlen($fileContent),
            'duration' => $duration
        ]);

        } catch (\Exception $e) {
            $this->error("Error downloading recording for call {$callLog->call_id}: " . $e->getMessage());
            Log::error('Error downloading call recording', [
                'call_id' => $callLog->call_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate alphanumeric filename with mix of capital and small letters
     */
    private function generateAlphanumericFileName(): string
    {
        $length = 12; // 12 characters for good uniqueness
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $fileName = '';
        
        for ($i = 0; $i < $length; $i++) {
            $fileName .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $fileName;
    }

    /**
     * Extract duration from audio file
     */
    private function extractAudioDuration(string $filePath): ?int
    {
        try {
            // Get full path to the file
            $fullPath = Storage::disk('public')->path($filePath);
            
            if (!file_exists($fullPath)) {
                $this->warn("Audio file not found: {$fullPath}");
                return null;
            }

            // Method 1: Try using getID3 library
            if (class_exists('getID3')) {
                $getID3 = new getID3();
                $fileInfo = $getID3->analyze($fullPath);
                
                if (isset($fileInfo['playtime_seconds'])) {
                    return (int) $fileInfo['playtime_seconds'];
                }
            }

            // Method 2: Try using FFmpeg if available
            $ffmpegPath = $this->findFFmpeg();
            if ($ffmpegPath) {
                $command = "{$ffmpegPath} -i " . escapeshellarg($fullPath) . " 2>&1";
                $output = shell_exec($command);
                
                // Parse duration from FFmpeg output
                if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $output, $matches)) {
                    $hours = (int) $matches[1];
                    $minutes = (int) $matches[2];
                    $seconds = (int) $matches[3];
                    $centiseconds = (int) $matches[4];
                    
                    $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds + ($centiseconds / 100);
                    return (int) $totalSeconds;
                }
            }

            // Method 3: Try using soxi (Sound eXchange Info) if available
            $soxiPath = $this->findSoxi();
            if ($soxiPath) {
                $command = "{$soxiPath} -D " . escapeshellarg($fullPath) . " 2>/dev/null";
                $output = shell_exec($command);
                
                if (is_numeric($output)) {
                    return (int) $output;
                }
            }

            $this->warn("Could not extract duration from audio file: {$filePath}");
            return null;

        } catch (\Exception $e) {
            $this->error("Error extracting audio duration: " . $e->getMessage());
            Log::error('Error extracting audio duration', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find FFmpeg executable
     */
    private function findFFmpeg(): ?string
    {
        $possiblePaths = [
            'ffmpeg',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            '/usr/local/opt/ffmpeg/bin/ffmpeg'
        ];

        foreach ($possiblePaths as $path) {
            if (is_executable($path) || shell_exec("which {$path} 2>/dev/null")) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Find soxi executable
     */
    private function findSoxi(): ?string
    {
        $possiblePaths = [
            'soxi',
            '/usr/bin/soxi',
            '/usr/local/bin/soxi',
            '/opt/homebrew/bin/soxi'
        ];

        foreach ($possiblePaths as $path) {
            if (is_executable($path) || shell_exec("which {$path} 2>/dev/null")) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Check and download missing recording files for existing calls
     */
    private function checkAndDownloadMissingRecordings($assistantId = null, bool $dryRun = false): array
    {
        $downloaded = 0;
        $skipped = 0;
        $errors = 0;

        try {
            // Get calls that have recording file names but files might be missing
            $query = CallLog::with('assistant.user') // Load assistant and user relationships
                          ->whereNotNull('call_record_file_name')
                          ->where('status', 'completed'); // Only check completed calls

            if ($assistantId) {
                $query->where('assistant_id', $assistantId);
            }

            $calls = $query->get();

            if ($calls->isEmpty()) {
                $this->warn('No calls with recording file names found');
                return ['downloaded' => 0, 'skipped' => 0, 'errors' => 0];
            }

            $this->info("Found {$calls->count()} calls with recording file names to check");

            foreach ($calls as $callLog) {
                try {
                    $result = $this->checkAndDownloadCallRecording($callLog, $dryRun);
                    
                    if ($result === 'downloaded') {
                        $downloaded++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error checking recording for call {$callLog->call_id}: " . $e->getMessage());
                    $errors++;
                }
            }

        } catch (\Exception $e) {
            $this->error("Error checking recordings: " . $e->getMessage());
            $errors++;
        }

        return ['downloaded' => $downloaded, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Check if recording file exists and download if missing
     */
    private function checkAndDownloadCallRecording(CallLog $callLog, bool $dryRun = false): string
    {
        $fileName = $callLog->call_record_file_name;
        $filePath = 'recordings/' . $fileName;

        // Check and update reseller_id if missing
        if (!$dryRun && !$callLog->reseller_id && $callLog->assistant && $callLog->assistant->user->reseller_id) {
            $callLog->reseller_id = $callLog->assistant->user->reseller_id;
            $callLog->save();
            $this->line("Updated reseller_id for call: {$callLog->call_id}");
            
            Log::info('Updated reseller_id for call during recording check', [
                'call_id' => $callLog->call_id,
                'assistant_id' => $callLog->assistant_id,
                'reseller_id' => $callLog->assistant->user->reseller_id,
                'user_id' => $callLog->user_id
            ]);
        }

        // Check if file exists in storage
        if (Storage::disk('public')->exists($filePath)) {
            if ($dryRun) {
                $this->line("Would skip existing recording file: {$fileName}");
            } else {
                $this->line("Recording file exists: {$fileName}");
            }
            return 'skipped';
        }

        // File doesn't exist, try to download it
        if ($dryRun) {
            $this->line("Would download missing recording: {$fileName}");
            return 'downloaded';
        }

        // Get recording URL from webhook data
        $recordingUrl = $this->extractRecordingUrlFromWebhookData($callLog->webhook_data);
        
        if (!$recordingUrl) {
            $this->warn("No recording URL found in webhook data for call: {$callLog->call_id}");
            return 'error';
        }

        try {
            // Create recordings directory if it doesn't exist
            $directory = 'recordings';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Download the file
            $fileContent = file_get_contents($recordingUrl);
            if ($fileContent === false) {
                $this->warn("Failed to download recording for call: {$callLog->call_id}");
                return 'error';
            }

            // Store the file
            $stored = Storage::disk('public')->put($filePath, $fileContent);
            if (!$stored) {
                $this->warn("Failed to store recording for call: {$callLog->call_id}");
                return 'error';
            }

            // Extract duration from audio file and update call log if not set
            if (!$callLog->duration) {
                $duration = $this->extractAudioDuration($filePath);
                if ($duration !== null) {
                    $callLog->duration = $duration;
                    $callLog->save();
                    $this->line("Updated duration from audio: {$duration} seconds");
                }
            }

            $this->line("Downloaded missing recording: {$fileName}");
            
            Log::info('Downloaded missing call recording', [
                'call_id' => $callLog->call_id,
                'filename' => $fileName,
                'file_path' => $filePath,
                'file_size' => strlen($fileContent)
            ]);

            return 'downloaded';

        } catch (\Exception $e) {
            $this->error("Error downloading recording for call {$callLog->call_id}: " . $e->getMessage());
            Log::error('Error downloading missing call recording', [
                'call_id' => $callLog->call_id,
                'filename' => $fileName,
                'error' => $e->getMessage()
            ]);
            return 'error';
        }
    }

    /**
     * Extract recording URL from webhook data
     */
    private function extractRecordingUrlFromWebhookData(array $webhookData): ?string
    {
        // Check for recording URL in different possible locations
        return $webhookData['recordingUrl'] ?? 
               $webhookData['artifact']['recordingUrl'] ?? 
               $webhookData['messages'][0]['artifact']['recordingUrl'] ?? 
               null;
    }

    /**
     * Fix missing reseller_id fields for existing calls
     */
    private function fixMissingResellerIds($assistantId = null, bool $dryRun = false): array
    {
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        try {
            // Get calls that are missing reseller_id
            $query = CallLog::with('assistant.user') // Load assistant and user relationships
                          ->whereNull('reseller_id');

            if ($assistantId) {
                $query->where('assistant_id', $assistantId);
            }

            $calls = $query->get();

            if ($calls->isEmpty()) {
                $this->warn('No calls with missing reseller_id found');
                return ['updated' => 0, 'skipped' => 0, 'errors' => 0];
            }

            $this->info("Found {$calls->count()} calls with missing reseller_id to fix");

            foreach ($calls as $callLog) {
                try {
                    $result = $this->fixCallResellerId($callLog, $dryRun);
                    
                    if ($result === 'updated') {
                        $updated++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error fixing reseller_id for call {$callLog->call_id}: " . $e->getMessage());
                    $errors++;
                }
            }

        } catch (\Exception $e) {
            $this->error("Error fixing reseller IDs: " . $e->getMessage());
            $errors++;
        }

        return ['updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Fix reseller_id for a single call
     */
    private function fixCallResellerId(CallLog $callLog, bool $dryRun = false): string
    {
        // Check if assistant and user exist and have reseller_id
        if (!$callLog->assistant || !$callLog->assistant->user || !$callLog->assistant->user->reseller_id) {
            if ($dryRun) {
                $this->line("Would skip call {$callLog->call_id} - no reseller_id available from assistant user");
            } else {
                $this->warn("Skipped call {$callLog->call_id} - no reseller_id available from assistant user");
            }
            return 'skipped';
        }

        $resellerId = $callLog->assistant->user->reseller_id;

        if ($dryRun) {
            $this->line("Would update reseller_id for call {$callLog->call_id} to {$resellerId}");
            return 'updated';
        }

        try {
            $callLog->reseller_id = $resellerId;
            $callLog->save();

            $this->line("Updated reseller_id for call {$callLog->call_id} to {$resellerId}");
            
            Log::info('Fixed missing reseller_id for call', [
                'call_id' => $callLog->call_id,
                'assistant_id' => $callLog->assistant_id,
                'reseller_id' => $resellerId,
                'user_id' => $callLog->user_id
            ]);

            return 'updated';

        } catch (\Exception $e) {
            $this->error("Error updating reseller_id for call {$callLog->call_id}: " . $e->getMessage());
            Log::error('Error fixing reseller_id for call', [
                'call_id' => $callLog->call_id,
                'assistant_id' => $callLog->assistant_id,
                'error' => $e->getMessage()
            ]);
            return 'error';
        }
    }
} 