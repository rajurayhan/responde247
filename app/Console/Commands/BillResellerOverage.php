<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ResellerUsagePeriod;
use App\Models\ResellerSubscription;
use App\Services\ResellerBillingService;
use Illuminate\Support\Facades\Log;

class BillResellerOverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:bill-overage
                            {--subscription= : Bill specific subscription ID}
                            {--period= : Bill specific usage period ID}
                            {--reseller= : Bill specific reseller ID}
                            {--dry-run : Run without making actual changes}
                            {--force : Force billing even if period hasn\'t ended}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually bill reseller overages for specific subscriptions or periods';

    private ResellerBillingService $billingService;
    private int $processedCount = 0;
    private int $billedCount = 0;
    private int $carriedForwardCount = 0;
    private int $errorCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->billingService = new ResellerBillingService();
        
        $subscriptionId = $this->option('subscription');
        $periodId = $this->option('period');
        $resellerId = $this->option('reseller');
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Starting manual reseller overage billing...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            if ($periodId) {
                $this->billSpecificPeriod($periodId, $isDryRun);
            } elseif ($subscriptionId) {
                $this->billSpecificSubscription($subscriptionId, $isDryRun, $force);
            } elseif ($resellerId) {
                $this->billResellerPeriods($resellerId, $isDryRun, $force);
            } else {
                $this->error('Please specify --subscription, --period, or --reseller');
                return Command::FAILURE;
            }

            // Display summary
            $this->displaySummary($isDryRun);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error in manual billing: ' . $e->getMessage());
            Log::error('Fatal error in manual billing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Bill a specific usage period
     */
    private function billSpecificPeriod(int $periodId, bool $isDryRun): void
    {
        $period = ResellerUsagePeriod::with(['reseller', 'subscription'])->find($periodId);
        
        if (!$period) {
            $this->error("Usage period {$periodId} not found");
            return;
        }

        $this->info("Billing usage period {$periodId} for reseller {$period->reseller->org_name}");

        $overageAmount = $period->getOverageAmount();
        
        if ($overageAmount <= 0) {
            $this->warn("  No overage to bill (amount: \${$overageAmount})");
            return;
        }

        $this->line("  Period: {$period->period_start->format('Y-m-d')} to {$period->period_end->format('Y-m-d')}");
        $this->line("  Total calls: {$period->total_calls}");
        $this->line("  Total minutes: " . round($period->total_duration_seconds / 60, 2));
        $this->line("  Overage minutes: {$period->overage_minutes}");
        $this->line("  Overage amount: \${$overageAmount}");

        $threshold = config('reseller-billing.overage_threshold', 10.00);
        
        if ($overageAmount >= $threshold) {
            if (!$isDryRun) {
                $transaction = $this->billingService->billImmediateOverage($period);
                if ($transaction && $transaction->isCompleted()) {
                    $this->info("  ✓ Billed overage: \${$overageAmount}");
                    $this->billedCount++;
                } else {
                    $this->error("  ✗ Failed to bill overage");
                    $this->errorCount++;
                }
            } else {
                $this->line("  [DRY RUN] Would bill overage: \${$overageAmount}");
                $this->billedCount++;
            }
        } else {
            if (!$isDryRun) {
                $this->billingService->carryForwardOverage($period);
                $this->info("  → Carried forward: \${$overageAmount}");
                $this->carriedForwardCount++;
            } else {
                $this->line("  [DRY RUN] Would carry forward: \${$overageAmount}");
                $this->carriedForwardCount++;
            }
        }

        $this->processedCount++;
    }

    /**
     * Bill a specific subscription
     */
    private function billSpecificSubscription(int $subscriptionId, bool $isDryRun, bool $force): void
    {
        $subscription = ResellerSubscription::with(['reseller', 'package'])->find($subscriptionId);
        
        if (!$subscription) {
            $this->error("Subscription {$subscriptionId} not found");
            return;
        }

        $this->info("Billing subscription {$subscriptionId} for reseller {$subscription->reseller->org_name}");

        // Find usage periods for this subscription
        $query = ResellerUsagePeriod::where('reseller_subscription_id', $subscriptionId)
            ->where('overage_status', 'pending');

        if (!$force) {
            $query->where('period_end', '<=', now());
        }

        $periods = $query->get();

        if ($periods->isEmpty()) {
            $this->warn("  No pending overage periods found");
            return;
        }

        foreach ($periods as $period) {
            $this->billSpecificPeriod($period->id, $isDryRun);
        }
    }

    /**
     * Bill all periods for a reseller
     */
    private function billResellerPeriods(string $resellerId, bool $isDryRun, bool $force): void
    {
        $this->info("Billing all periods for reseller {$resellerId}");

        $query = ResellerUsagePeriod::with(['reseller', 'subscription'])
            ->where('reseller_id', $resellerId)
            ->where('overage_status', 'pending');

        if (!$force) {
            $query->where('period_end', '<=', now());
        }

        $periods = $query->get();

        if ($periods->isEmpty()) {
            $this->warn("  No pending overage periods found");
            return;
        }

        $this->info("Found {$periods->count()} periods to process");

        foreach ($periods as $period) {
            $this->billSpecificPeriod($period->id, $isDryRun);
        }
    }

    /**
     * Display processing summary
     */
    private function displaySummary(bool $isDryRun): void
    {
        $this->info('═══════════════════════════════════════');
        $this->info('Manual Billing Summary');
        $this->info('═══════════════════════════════════════');
        $this->line("Periods processed: {$this->processedCount}");
        $this->line("Overages billed: {$this->billedCount}");
        $this->line("Amounts carried forward: {$this->carriedForwardCount}");
        $this->line("Errors: {$this->errorCount}");
        
        if ($isDryRun) {
            $this->warn('DRY RUN - No actual changes were made');
        }
        
        $this->info('═══════════════════════════════════════');
    }
}