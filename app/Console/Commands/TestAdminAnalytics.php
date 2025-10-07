<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\CallLog;
use App\Models\Assistant;

class TestAdminAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:admin-analytics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test admin analytics data structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing admin analytics data structure...');

        // Test top performing assistants query
        $this->testTopAssistants();

        // Test assistant data loading
        $this->testAssistantData();

        return 0;
    }

    /**
     * Test top performing assistants query
     */
    private function testTopAssistants()
    {
        $this->info("\n1. Testing top performing assistants query...");

        $query = CallLog::query();

        // Get top performing assistants
        $topAssistants = $query
            ->select(
                'assistant_id',
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when status = "completed" then 1 else 0 end) as completed_calls')
            )
            ->groupBy('assistant_id')
            ->having('total_calls', '>', 0)
            ->orderBy('total_calls', 'desc')
            ->limit(5)
            ->get();

        $this->info("Found {$topAssistants->count()} assistants with calls");

        foreach ($topAssistants as $item) {
            $this->info("Assistant ID: {$item->assistant_id}");
            $this->info("Total Calls: {$item->total_calls}");
            $this->info("Completed Calls: {$item->completed_calls}");
            
            // Load assistant data
            $assistant = Assistant::with('user')->find($item->assistant_id);
            
            if ($assistant) {
                $this->info("Assistant Name: {$assistant->name}");
                $this->info("User Name: " . ($assistant->user ? $assistant->user->name : 'No User'));
            } else {
                $this->warn("Assistant not found for ID: {$item->assistant_id}");
            }
            
            $this->info("---");
        }
    }

    /**
     * Test assistant data loading
     */
    private function testAssistantData()
    {
        $this->info("\n2. Testing assistant data loading...");

        // Get all assistants with their users
        $assistants = Assistant::with('user')->get();

        $this->info("Found {$assistants->count()} total assistants");

        foreach ($assistants as $assistant) {
            $this->info("Assistant ID: {$assistant->id}");
            $this->info("Assistant Name: {$assistant->name}");
            $this->info("User Name: " . ($assistant->user ? $assistant->user->name : 'No User'));
            $this->info("---");
        }
    }
} 