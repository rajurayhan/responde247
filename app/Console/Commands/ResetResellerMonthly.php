<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ResellerSubscription;
use App\Models\ResellerUsagePeriod;
use App\Services\ResellerBillingService;
use App\Services\ResellerUsageTracker;
use Illuminate\Support\Facades\Log;

class ResetResellerMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:reset-monthly
                            {--subscription= : Reset specific subscription}
                            {--reseller= : Reset specific reseller}
                            {--dry-run : Run without making actual changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually trigger monthly reset and billing for resellers';

    private ResellerBillingService $billingService;
    private ResellerUsageTracker $usageTracker;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->billingService = new ResellerBillingService();
        $this->usageTracker = new ResellerUsageTracker();
        
        $isDryRun = $this->option('dry-run');
        $subscriptionId = $this->option('subscription');
        $resellerId = $this->option('reseller');

        $this->info('Starting monthly reset process...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            // Find subscriptions to reset
            $query = ResellerSubscription::with(['reseller', 'package'])
                ->whereIn('status', ['active', 'pending']);

            if ($subscriptionId) {
                $query->where('id', $subscriptionId);
                $this->info("Processing specific subscription: {$subscriptionId}");
            } elseif ($resellerId) {
                $query->where('reseller_id', $resellerId);
                $this->info("Processing specific reseller: {$resellerId}");
            }

            $subscriptions = $query->get();

            if ($subscriptions->isEmpty()) {
                $this->info('No subscriptions found to process');
                return Command::SUCCESS;
            }

            $this->info("Found {$subscriptions->count()} subscriptions to process");

            // Process each subscription
            foreach ($subscriptions as $subscription) {
                $this->processSubscription($subscription, $isDryRun);
            }

            $this->info('Monthly reset process completed!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error in monthly reset process: ' . $e->getMessage());
            Log::error('Fatal error in monthly reset process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process a single subscription for monthly reset
     */
    private function processSubscription(ResellerSubscription $subscription, bool $isDryRun): void
    {
        try {
            $this->newLine();
            $this->info("Processing: {$subscription->reseller->org_name} (ID: {$subscription->reseller_id})");

            // Get current usage period
            $currentPeriod = $subscription->getCurrentUsagePeriod();
            
            if (!$currentPeriod) {
                $this->warn('  No current usage period found');
                return;
            }

            $overageAmount = $currentPeriod->getOverageAmount();

            $this->line("  Current Period: {$currentPeriod->period_start->format('Y-m-d')} to {$currentPeriod->period_end->format('Y-m-d')}");
            $this->line("  Total calls: {$currentPeriod->total_calls}");
            $this->line("  Total minutes: " . round($currentPeriod->total_duration_seconds / 60, 2));
            $this->line("  Total cost: \${$currentPeriod->total_cost}");
            $this->line("  Overage amount: \${$overageAmount}");

            if (!$isDryRun) {
                // Step 1: Process any remaining overage
                if ($overageAmount > 0) {
                    $threshold = config('reseller-billing.overage_threshold', 10.00);
                    
                    if ($overageAmount >= $threshold) {
                        // Bill remaining overage
                        $transaction = $this->billingService->billImmediateOverage($currentPeriod);
                        if ($transaction && $transaction->isCompleted()) {
                            $this->info("  ✓ Billed remaining overage: \${$overageAmount}");
                        } else {
                            $this->error("  ✗ Failed to bill remaining overage");
                        }
                    } else {
                        // Carry forward small overage
                        $this->billingService->carryForwardOverage($currentPeriod);
                        $this->info("  → Carried forward: \${$overageAmount}");
                    }
                }

                // Step 2: Mark current period as ended
                $currentPeriod->update([
                    'period_end' => now(),
                    'overage_status' => 'none',
                ]);

                // Step 3: Reset subscription counters
                $subscription->resetPeriodUsage();

                // Step 4: Create new usage period for next month
                if ($subscription->isActive() && !$subscription->hasExpired()) {
                    $newPeriod = $this->usageTracker->createUsagePeriod($subscription);
                    $this->info("  ✓ New usage period created (ID: {$newPeriod->id})");
                    $this->line("  New period: {$newPeriod->period_start->format('Y-m-d')} to {$newPeriod->period_end->format('Y-m-d')}");
                }

                $this->info("  ✓ Monthly reset completed for {$subscription->reseller->org_name}");
            } else {
                $this->line("  [DRY RUN] Would process overage and create new period");
            }

        } catch (\Exception $e) {
            $this->error("  Error processing subscription: {$e->getMessage()}");
            
            Log::error('Error processing subscription in monthly reset', [
                'subscription_id' => $subscription->id,
                'reseller_id' => $subscription->reseller_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
