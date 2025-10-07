<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ResellerUsagePeriod;
use App\Models\ResellerPackage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix overage calculations for existing usage periods
        $this->fixOverageCalculations();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it fixes incorrect data
        // The original incorrect data cannot be restored
    }

    /**
     * Fix overage calculations for all existing usage periods
     */
    private function fixOverageCalculations(): void
    {
        echo "Fixing overage calculations for existing usage periods...\n";

        // Get all usage periods that need fixing
        $usagePeriods = ResellerUsagePeriod::with('package')->get();
        
        $fixedCount = 0;
        $skippedCount = 0;

        foreach ($usagePeriods as $period) {
            $package = $period->package;
            
            if (!$package) {
                echo "Skipping period {$period->id}: No package found\n";
                $skippedCount++;
                continue;
            }

            $originalData = [
                'monthly_cost_limit' => $period->monthly_cost_limit,
                'overage_cost' => $period->overage_cost,
                'overage_minutes' => $period->overage_minutes,
            ];

            // Fix monthly cost limit
            if ($package->monthly_minutes_limit > 0) {
                // For minute-limited packages: no cost limit
                $newMonthlyCostLimit = 0;
            } else {
                // For unlimited packages: cost limit = package price
                $newMonthlyCostLimit = $package->price;
            }

            // Recalculate overage based on correct logic
            $overageData = $this->calculateCorrectOverage($period, $package);

            // Update the period if changes are needed
            $needsUpdate = (
                $period->monthly_cost_limit != $newMonthlyCostLimit ||
                $period->overage_cost != $overageData['overage_cost'] ||
                $period->overage_minutes != $overageData['overage_minutes']
            );

            if ($needsUpdate) {
                $period->update([
                    'monthly_cost_limit' => $newMonthlyCostLimit,
                    'overage_cost' => $overageData['overage_cost'],
                    'overage_minutes' => $overageData['overage_minutes'],
                ]);

                echo "Fixed period {$period->id}:\n";
                echo "  Package: {$package->name} (minutes limit: {$package->monthly_minutes_limit})\n";
                echo "  Monthly cost limit: {$originalData['monthly_cost_limit']} → {$newMonthlyCostLimit}\n";
                echo "  Overage cost: {$originalData['overage_cost']} → {$overageData['overage_cost']}\n";
                echo "  Overage minutes: {$originalData['overage_minutes']} → {$overageData['overage_minutes']}\n";
                echo "  Total minutes: " . round($period->total_duration_seconds / 60, 2) . "\n";
                echo "  Total cost: {$period->total_cost}\n\n";

                $fixedCount++;
            } else {
                $skippedCount++;
            }
        }

        echo "Migration completed:\n";
        echo "  Fixed periods: {$fixedCount}\n";
        echo "  Skipped periods: {$skippedCount}\n";
    }

    /**
     * Calculate correct overage for a usage period
     */
    private function calculateCorrectOverage(ResellerUsagePeriod $period, ResellerPackage $package): array
    {
        if ($package->monthly_minutes_limit > 0) {
            // Duration-based calculation for minute packages
            $totalMinutes = $period->total_duration_seconds / 60;
            $overageMinutes = max(0, $totalMinutes - $package->monthly_minutes_limit);
            $overageCost = $overageMinutes * $package->extra_per_minute_charge;

            return [
                'overage_cost' => (float) $overageCost,
                'overage_minutes' => (int) $overageMinutes,
            ];
        } else {
            // Cost-based calculation for unlimited packages
            $overageCost = max(0, $period->total_cost - $package->price);
            $totalMinutes = $period->total_duration_seconds / 60;
            $overageMinutes = max(0, $totalMinutes - $package->monthly_minutes_limit);

            return [
                'overage_cost' => (float) $overageCost,
                'overage_minutes' => (int) $overageMinutes,
            ];
        }
    }
};