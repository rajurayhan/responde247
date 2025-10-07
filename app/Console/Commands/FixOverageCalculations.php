<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ResellerUsagePeriod;
use App\Models\ResellerPackage;
use Illuminate\Support\Facades\Log;

class FixOverageCalculations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:fix-overage-calculations
                            {--dry-run : Run without making actual changes}
                            {--reseller= : Fix specific reseller only}
                            {--period= : Fix specific usage period only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix overage calculations for existing usage periods';

    private int $fixedCount = 0;
    private int $skippedCount = 0;
    private int $errorCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $specificReseller = $this->option('reseller');
        $specificPeriod = $this->option('period');

        $this->info('Starting overage calculation fix...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            // Build query
            $query = ResellerUsagePeriod::with('package');
            
            if ($specificReseller) {
                $query->where('reseller_id', $specificReseller);
                $this->info("Processing specific reseller: {$specificReseller}");
            }
            
            if ($specificPeriod) {
                $query->where('id', $specificPeriod);
                $this->info("Processing specific period: {$specificPeriod}");
            }

            $usagePeriods = $query->get();
            $this->info("Found {$usagePeriods->count()} usage periods to process");

            if ($usagePeriods->isEmpty()) {
                $this->info('No usage periods to process');
                return Command::SUCCESS;
            }

            // Process each period
            $progressBar = $this->output->createProgressBar($usagePeriods->count());
            $progressBar->start();

            foreach ($usagePeriods as $period) {
                $this->processUsagePeriod($period, $isDryRun);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display summary
            $this->displaySummary($isDryRun);

            Log::info('Overage calculation fix completed', [
                'fixed' => $this->fixedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errorCount,
                'dry_run' => $isDryRun,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error in overage fix: ' . $e->getMessage());
            Log::error('Fatal error in overage fix', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process a single usage period
     */
    private function processUsagePeriod(ResellerUsagePeriod $period, bool $isDryRun): void
    {
        try {
            $package = $period->package;
            
            if (!$package) {
                $this->warn("  Skipping period {$period->id}: No package found");
                $this->skippedCount++;
                return;
            }

            $originalData = [
                'monthly_cost_limit' => $period->monthly_cost_limit,
                'overage_cost' => $period->overage_cost,
                'overage_minutes' => $period->overage_minutes,
            ];

            // Calculate correct values
            $correctData = $this->calculateCorrectOverage($period, $package);

            // Check if changes are needed
            $needsUpdate = (
                $period->monthly_cost_limit != $correctData['monthly_cost_limit'] ||
                $period->overage_cost != $correctData['overage_cost'] ||
                $period->overage_minutes != $correctData['overage_minutes']
            );

            if (!$needsUpdate) {
                $this->skippedCount++;
                return;
            }

            // Show what will be changed
            $this->newLine();
            $this->info("Period {$period->id} ({$package->name}):");
            $this->line("  Monthly cost limit: {$originalData['monthly_cost_limit']} → {$correctData['monthly_cost_limit']}");
            $this->line("  Overage cost: {$originalData['overage_cost']} → {$correctData['overage_cost']}");
            $this->line("  Overage minutes: {$originalData['overage_minutes']} → {$correctData['overage_minutes']}");
            $this->line("  Total minutes: " . round($period->total_duration_seconds / 60, 2));
            $this->line("  Total cost: {$period->total_cost}");

            if (!$isDryRun) {
                // Apply the fix
                $period->update([
                    'monthly_cost_limit' => $correctData['monthly_cost_limit'],
                    'overage_cost' => $correctData['overage_cost'],
                    'overage_minutes' => $correctData['overage_minutes'],
                ]);

                $this->info("  ✓ Fixed");
                $this->fixedCount++;
            } else {
                $this->line("  [DRY RUN] Would fix");
                $this->fixedCount++;
            }

        } catch (\Exception $e) {
            $this->error("  Error processing period {$period->id}: {$e->getMessage()}");
            $this->errorCount++;
            
            Log::error('Error processing usage period in fix command', [
                'period_id' => $period->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate correct overage for a usage period
     */
    private function calculateCorrectOverage(ResellerUsagePeriod $period, ResellerPackage $package): array
    {
        // Fix monthly cost limit
        if ($package->monthly_minutes_limit > 0) {
            // For minute-limited packages: no cost limit
            $monthlyCostLimit = 0;
        } else {
            // For unlimited packages: cost limit = package price
            $monthlyCostLimit = $package->price;
        }

        // Calculate overage
        if ($package->monthly_minutes_limit > 0) {
            // Duration-based calculation for minute packages
            $totalMinutes = $period->total_duration_seconds / 60;
            $overageMinutes = max(0, $totalMinutes - $package->monthly_minutes_limit);
            $overageCost = $overageMinutes * $package->extra_per_minute_charge;

            return [
                'monthly_cost_limit' => $monthlyCostLimit,
                'overage_cost' => (float) $overageCost,
                'overage_minutes' => (int) $overageMinutes,
            ];
        } else {
            // Cost-based calculation for unlimited packages
            $overageCost = max(0, $period->total_cost - $package->price);
            $totalMinutes = $period->total_duration_seconds / 60;
            $overageMinutes = max(0, $totalMinutes - $package->monthly_minutes_limit);

            return [
                'monthly_cost_limit' => $monthlyCostLimit,
                'overage_cost' => (float) $overageCost,
                'overage_minutes' => (int) $overageMinutes,
            ];
        }
    }

    /**
     * Display processing summary
     */
    private function displaySummary(bool $isDryRun): void
    {
        $this->info('═══════════════════════════════════════');
        $this->info('Overage Fix Summary');
        $this->info('═══════════════════════════════════════');
        $this->line("Periods processed: " . ($this->fixedCount + $this->skippedCount));
        $this->line("Periods fixed: {$this->fixedCount}");
        $this->line("Periods skipped: {$this->skippedCount}");
        $this->line("Errors: {$this->errorCount}");
        
        if ($isDryRun) {
            $this->warn('DRY RUN - No actual changes were made');
        }
        
        $this->info('═══════════════════════════════════════');
    }
}