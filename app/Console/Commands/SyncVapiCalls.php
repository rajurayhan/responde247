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
                            {--dry-run : Show what would be synced without making changes}';

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

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
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

        foreach ($assistants as $assistant) {
            $this->info("\nSyncing calls for assistant: {$assistant->name} (ID: {$assistant->vapi_assistant_id})");
            
            try {
                $result = $this->syncAssistantCalls($assistant, $limit, $dryRun);
                $totalSynced += $result['synced'];
                $totalSkipped += $result['skipped'];
                $totalErrors += $result['errors'];
                
                $this->info("Assistant {$assistant->name}: {$result['synced']} synced, {$result['skipped']} skipped, {$result['errors']} errors");
            } catch (\Exception $e) {
                $this->error("Error syncing assistant {$assistant->name}: " . $e->getMessage());
                $totalErrors++;
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("SYNC SUMMARY:");
        $this->info("Total Synced: {$totalSynced}");
        $this->info("Total Skipped: {$totalSkipped}");
        $this->info("Total Errors: {$totalErrors}");
        
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
     * Fetch calls from Vapi API
     */
    private function fetchVapiCalls(string $assistantId, int $limit)
    {
        $vapiToken = config('services.vapi.token');
        $baseUrl = config('services.vapi.base_url', 'https://api.vapi.ai');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$vapiToken}",
            'Content-Type' => 'application/json',
        ])->get("{$baseUrl}/call", [
            'assistantId' => $assistantId,
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Vapi API error: " . $response->status() . " - " . $response->body());
        }

        $data = $response->json();
        
        if (!is_array($data)) {
            throw new \Exception("Invalid response format from Vapi API");
        }

        return $data;
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
        // Basic call information
        $callLog->call_id = $call['id'];
        $callLog->assistant_id = $assistant->id;
        $callLog->user_id = $assistant->user_id;
        
        // Phone numbers
        $callLog->phone_number = $call['phoneNumber']['number'] ?? null;
        $callLog->caller_number = $call['customer']['number'] ?? null;
        
        // Timing
        $callLog->start_time = $call['startedAt'] ? Carbon::parse($call['startedAt']) : null;
        $callLog->end_time = $call['endedAt'] ? Carbon::parse($call['endedAt']) : null;
        
        // Calculate duration from timestamps (fallback)
        if ($callLog->start_time && $callLog->end_time) {
            $duration = $callLog->end_time->diffInSeconds($callLog->start_time);
            $callLog->duration = max(0, $duration); // Ensure non-negative duration
        }
        
        // Note: Duration will be updated from audio file if available during download
        
        // Status mapping
        $callLog->status = $this->mapVapiStatus($call['status']);
        $callLog->direction = $this->mapVapiDirection($call['type']);
        
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
} 