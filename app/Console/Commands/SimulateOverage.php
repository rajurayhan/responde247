<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CallLog;
use App\Models\Assistant;
use Carbon\Carbon;

class SimulateOverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overage:simulate {user-id} {minutes=150} {--reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate overage by creating call logs for a user (for testing)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user-id');
        $targetMinutes = (int) $this->argument('minutes');
        $reset = $this->option('reset');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        // Check if user has active subscription
        $subscription = $user->subscriptions()->where('status', 'active')->first();
        if (!$subscription) {
            $this->error("User {$user->email} does not have an active subscription.");
            return 1;
        }

        $package = $subscription->package;
        $this->info("User: {$user->email}");
        $this->info("Package: {$package->name}");
        $this->info("Monthly Limit: {$package->monthly_minutes_limit} minutes");
        $this->info("Extra Per Minute: \${$package->extra_per_minute_charge}");
        $this->line('');

        // Reset existing call logs if requested
        if ($reset) {
            $deleted = CallLog::where('user_id', $userId)
                ->whereBetween('start_time', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->delete();
            $this->info("🗑️  Deleted {$deleted} existing call logs for this month");
        }

        // Get user's first assistant or create one
        $assistant = $user->assistants()->first();
        if (!$assistant) {
            $this->error("User has no assistants. Create an assistant first.");
            return 1;
        }

        // Calculate total seconds needed
        $targetSeconds = $targetMinutes * 60;

        // Create call logs to reach target minutes
        $callsToCreate = 5; // Create 5 calls
        $secondsPerCall = $targetSeconds / $callsToCreate;

        $this->info("📞 Creating {$callsToCreate} call logs totaling {$targetMinutes} minutes...");

        $createdCalls = 0;
        for ($i = 0; $i < $callsToCreate; $i++) {
            $startTime = Carbon::now()->startOfMonth()->addDays(rand(1, 25))->addMinutes(rand(0, 1440));
            
            CallLog::create([
                'call_id' => 'sim_' . uniqid(),
                'user_id' => $userId,
                'assistant_id' => $assistant->id,
                'phone_number_from' => '+1234567890',
                'phone_number_to' => '+0987654321',
                'status' => 'completed',
                'duration' => (int) $secondsPerCall,
                'cost' => 0.00,
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addSeconds($secondsPerCall),
                'transcript' => 'Simulated call for overage testing.',
                'metadata' => ['simulated' => true]
            ]);
            
            $createdCalls++;
        }

        $this->info("✅ Created {$createdCalls} simulated call logs");
        
        // Calculate overage
        $totalMinutesUsed = CallLog::where('user_id', $userId)
            ->whereBetween('start_time', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('duration') / 60;

        $overageMinutes = max(0, $totalMinutesUsed - $package->monthly_minutes_limit);
        $overageCost = $overageMinutes * $package->extra_per_minute_charge;

        $this->line('');
        $this->info('📊 Results:');
        $this->line("Total Minutes Used: " . number_format($totalMinutesUsed, 1));
        $this->line("Monthly Limit: " . number_format($package->monthly_minutes_limit));
        $this->line("Overage Minutes: " . number_format($overageMinutes, 1));
        $this->line("Overage Cost: $" . number_format($overageCost, 2));

        if ($overageMinutes > 0) {
            $this->line('');
            $this->comment('🎯 Success! User now has overage charges.');
            $this->comment("💡 View overage in the UI at: /subscription-manager");
            $this->comment("💡 Or run: php artisan overage:report --user-id={$userId}");
        } else {
            $this->line('');
            $this->comment("ℹ️  No overage generated. User is still within limits.");
            $this->comment("💡 Try with higher minutes: php artisan overage:simulate {$userId} 200");
        }

        return 0;
    }
}