<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Assistant;

class TestVapiSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapi:test-sync 
                            {--assistant-id= : Test sync for specific assistant ID}
                            {--limit=5 : Number of calls to fetch for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Vapi API connection and call fetching';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Vapi API connection...');

        // Get Vapi API token from environment
        $vapiToken = config('services.vapi.token');
        if (!$vapiToken) {
            $this->error('VAPI_API_KEY not found in environment variables');
            return 1;
        }

        $assistantId = $this->option('assistant-id');
        $limit = (int) $this->option('limit');

        // Get assistants to test
        $assistants = $this->getAssistants($assistantId);
        
        if ($assistants->isEmpty()) {
            $this->error('No assistants found with vapi_assistant_id');
            return 1;
        }

        $this->info("Found {$assistants->count()} assistant(s) to test");

        foreach ($assistants as $assistant) {
            $this->info("\nTesting assistant: {$assistant->name} (Vapi ID: {$assistant->vapi_assistant_id})");
            
            try {
                $this->testAssistantCalls($assistant, $limit);
            } catch (\Exception $e) {
                $this->error("Error testing assistant {$assistant->name}: " . $e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Get assistants to test
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
     * Test calls for a specific assistant
     */
    private function testAssistantCalls(Assistant $assistant, int $limit)
    {
        try {
            // Test API connection
            $calls = $this->fetchVapiCalls($assistant->vapi_assistant_id, $limit);
            
            if (empty($calls)) {
                $this->warn("No calls found for assistant {$assistant->name}");
                return;
            }

            $this->info("Found " . count($calls) . " calls for assistant {$assistant->name}");

            // Display sample call data
            $this->displaySampleCall($calls[0]);

        } catch (\Exception $e) {
            $this->error("Error fetching calls for assistant {$assistant->name}: " . $e->getMessage());
        }
    }

    /**
     * Fetch calls from Vapi API
     */
    private function fetchVapiCalls(string $assistantId, int $limit)
    {
        $vapiToken = config('services.vapi.token');
        $baseUrl = config('services.vapi.base_url', 'https://api.vapi.ai');

        $this->info("Making API request to: {$baseUrl}/call");
        $this->info("Parameters: assistantId={$assistantId}, limit={$limit}");

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$vapiToken}",
            'Content-Type' => 'application/json',
        ])->get("{$baseUrl}/call", [
            'assistantId' => $assistantId,
            'limit' => $limit,
        ]);

        $this->info("Response status: " . $response->status());

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
     * Display sample call data
     */
    private function displaySampleCall(array $call)
    {
        $this->info("\nSample Call Data:");
        $this->info("ID: " . ($call['id'] ?? 'N/A'));
        $this->info("Status: " . ($call['status'] ?? 'N/A'));
        $this->info("Type: " . ($call['type'] ?? 'N/A'));
        $this->info("Started At: " . ($call['startedAt'] ?? 'N/A'));
        $this->info("Ended At: " . ($call['endedAt'] ?? 'N/A'));
        $this->info("Cost: " . ($call['cost'] ?? 'N/A'));
        
        if (isset($call['phoneNumber']['number'])) {
            $this->info("Phone Number: " . $call['phoneNumber']['number']);
        }
        
        if (isset($call['customer']['number'])) {
            $this->info("Caller Number: " . $call['customer']['number']);
        }
        
        if (isset($call['artifact']['transcript'])) {
            $this->info("Has Transcript: Yes");
        } else {
            $this->info("Has Transcript: No");
        }
        
        if (isset($call['analysis']['summary'])) {
            $this->info("Has Summary: Yes");
        } else {
            $this->info("Has Summary: No");
        }
        
        $this->info("Webhook Data Size: " . strlen(json_encode($call)) . " bytes");
    }
} 