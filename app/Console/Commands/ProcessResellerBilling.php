<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ResellerSubscription;
use App\Models\ResellerUsagePeriod;
use App\Services\ResellerBillingService;
use App\Services\ResellerUsageTracker;
use Illuminate\Support\Facades\Log;

class ProcessResellerBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:process-billing
                            {--dry-run : Run without making actual changes}
                            {--package-end-bill : Run end of the package billing}
                            {--reseller= : Process specific reseller only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process reseller billing cycles, handle end-of-period billing, and create new usage periods';

    private ResellerBillingService $billingService;
    private ResellerUsageTracker $usageTracker;
    private int $processedCount = 0;
    private int $errorCount = 0;
    private int $billedCount = 0;
    private int $carriedForwardCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->billingService = new ResellerBillingService();
        $this->usageTracker = new ResellerUsageTracker();
        
        $packageEndBill = $this->option('package-end-bill');
        $isDryRun = $this->option('dry-run');
        $specificReseller = $this->option('reseller');
        

        $this->info('Starting reseller billing process...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            // Find subscriptions with ended billing periods
            /* if( $packageEndBill ) {
                $query = ResellerSubscription::with(['reseller', 'package'])
                    ->whereIn('status', ['active', 'pending'])
                    ->where('current_period_end', '<=', now())
                    ->whereHas('usagePeriods', function ($usageQuery) {
                        $usageQuery->where('overage_status', 'pending')
                            ->where('period_end', '<=', now());
                    });
            } else {
                $query = ResellerSubscription::with(['reseller', 'package'])
                    ->whereIn('status', ['active', 'pending'])
                    ->where('current_period_end', '<=', now());
            } */
            $query = ResellerSubscription::with(['reseller', 'package'])
                ->whereIn('status', ['active', 'pending'])
                ->where('current_period_end', '<=', now())
                ->whereHas('usagePeriods', function ($usageQuery) {
                    $usageQuery->whereIn('overage_status', ['pending', 'none'])->where('period_end', '<=', now());
                })->limit(50);
            
            
            if ($specificReseller) {
                $query->where('reseller_id', $specificReseller);
                $this->info("Processing specific reseller: {$specificReseller}");
            }

            $subscriptions = $query->get();

            $this->info("Found {$subscriptions->count()} subscriptions with ended billing periods");

            if ($subscriptions->isEmpty()) {
                $this->info('No subscriptions to process');
                return Command::SUCCESS;
            }

            // Process each subscription
            $progressBar = $this->output->createProgressBar($subscriptions->count());
            $progressBar->start();

            foreach ($subscriptions as $subscription) {
                $this->processSubscription($subscription, $isDryRun, $packageEndBill);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display summary
            $this->displaySummary();

            Log::info('Reseller billing process completed', [
                'processed' => $this->processedCount,
                'billed' => $this->billedCount,
                'carried_forward' => $this->carriedForwardCount,
                'errors' => $this->errorCount,
                'dry_run' => $isDryRun,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error in billing process: ' . $e->getMessage());
            Log::error('Fatal error in reseller billing process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process a single subscription
     */
    private function processSubscription(ResellerSubscription $subscription, bool $isDryRun, bool $isPackageEndBill = false): void
    {
        try {
            $this->newLine();
            $this->info("Processing: {$subscription->reseller->org_name} (ID: {$subscription->reseller_id})");

            // Get ending usage period
            $endingPeriod = ResellerUsagePeriod::where('reseller_subscription_id', $subscription->id)
                ->where('period_end', '<=', now())
                ->where('overage_status', '!=', 'billed')
                ->where('overage_status', '!=', 'carried_forward')
                ->orderBy('period_end', 'desc')
                ->first();

            if (!$endingPeriod) {
                $this->warn('  No usage period found to process');
                return;
            }

            $overageAmount = $endingPeriod->getOverageAmount();

            $this->line("  Period: {$endingPeriod->period_start->format('Y-m-d')} to {$endingPeriod->period_end->format('Y-m-d')}");
            $this->line("  Total calls: {$endingPeriod->total_calls}");
            $this->line("  Total cost: \${$endingPeriod->total_cost}");
            $this->line("  Overage amount: \${$overageAmount}");

            if ($overageAmount > 0) {
                $threshold = config('reseller-billing.overage_threshold', 10.00);
                
                if ($overageAmount >= $threshold) {
                    // Bill immediately - NO reset, NO new period
                    if (!$isDryRun) {
                        $transaction = $this->billingService->billImmediateOverage($endingPeriod);
                        if ($transaction && $transaction->isCompleted()) {
                            $this->info("  ✓ Billed overage: \${$overageAmount}");
                            $this->billedCount++;
                        } else {
                            $this->error("  ✗ Failed to bill overage");
                            $this->errorCount++;
                        }
                    } else {
                        $this->line("  [DRY RUN] Would bill overage: \${$overageAmount}");
                    }
                } else {
                    // Carry forward - NO reset, NO new period
                    if (!$isDryRun) {
                        if($isPackageEndBill){
                            $transaction = $this->billingService->billImmediateOverage($endingPeriod);
                            if ($transaction && $transaction->isCompleted()) {
                                $this->info("  ✓ Billed overage: \${$overageAmount}");
                                $this->billedCount++;
                            } else {
                                $this->error("  ✗ Failed to bill overage");
                                $this->errorCount++;
                            }
                        }else{
                            $this->billingService->carryForwardOverage($endingPeriod);
                            $this->info("  → Carried forward: \${$overageAmount}");
                            $this->carriedForwardCount++;
                        }
                        
                    } else {
                        $this->line("  [DRY RUN] Would carry forward: \${$overageAmount}");
                    }
                }
            } else {
                $this->line("  No overage to process");
            }

            // IMPORTANT: For immediate overage billing, we do NOT reset or create new periods
            // The reseller continues using the same period and gets billed for additional overage
            // Only end-of-period billing should reset and create new periods

            $this->processedCount++;

        } catch (\Exception $e) {
            $this->error("  Error processing subscription: {$e->getMessage()}");
            $this->errorCount++;
            
            Log::error('Error processing subscription in billing command', [
                'subscription_id' => $subscription->id,
                'reseller_id' => $subscription->reseller_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display processing summary
     */
    private function displaySummary(): void
    {
        $this->info('═══════════════════════════════════════');
        $this->info('Billing Process Summary');
        $this->info('═══════════════════════════════════════');
        $this->line("Subscriptions processed: {$this->processedCount}");
        $this->line("Overages billed: {$this->billedCount}");
        $this->line("Amounts carried forward: {$this->carriedForwardCount}");
        
        if ($this->errorCount > 0) {
            $this->error("Errors encountered: {$this->errorCount}");
        } else {
            $this->info("Errors: 0");
        }
        
        $this->info('═══════════════════════════════════════');
    }
}
