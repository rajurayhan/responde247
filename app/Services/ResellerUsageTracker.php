<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Reseller;
use App\Models\ResellerSubscription;
use App\Models\ResellerUsagePeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Reflector;

class ResellerUsageTracker
{
    /**
     * Track call usage for a reseller
     * 
     * Called from VapiWebhookController after call log creation
     */
    public function trackCallUsage(CallLog $callLog): void
    {
        try {
            // Validate call log has required data
            if (!$callLog->reseller_id || !$callLog->cost) {
                Log::warning('Call log missing required data for usage tracking', [
                    'call_id' => $callLog->call_id,
                    'reseller_id' => $callLog->reseller_id,
                    'cost' => $callLog->cost,
                ]);
                return;
            }
            // Get or create current usage period
            $usagePeriod = $this->getCurrentUsagePeriod($callLog->reseller_id);
            
            if (!$usagePeriod) {
                Log::warning('No usage period found for reseller, cannot track usage', [
                    'reseller_id' => $callLog->reseller_id,
                    'call_id' => $callLog->call_id,
                ]);
                return;
            }

            // Use database transaction to ensure data consistency
            DB::transaction(function () use ($callLog, $usagePeriod) {
                // Lock the usage period row to prevent race conditions
                $usagePeriod = ResellerUsagePeriod::where('id', $usagePeriod->id)
                    ->lockForUpdate()
                    ->first();

                // Update usage metrics
                $usagePeriod->total_calls += 1;
                $usagePeriod->total_duration_seconds += $callLog->duration ?? 0;
                $usagePeriod->total_cost += $callLog->cost;

                // Calculate overage
                $overageData = $this->calculateOverage($usagePeriod);
                $usagePeriod->overage_cost = $overageData['overage_cost'];
                $usagePeriod->overage_minutes = $overageData['overage_minutes'];

                // Update overage status
                if ($usagePeriod->overage_cost > 0) {
                    $usagePeriod->overage_status = 'pending';
                }

                $usagePeriod->save();

                // Update subscription's real-time counters
                $this->updateSubscriptionCounters($usagePeriod);

                // Check if billing threshold is met
                if ($this->shouldBillOverage($usagePeriod->getOverageAmount())) {
                    $this->triggerOverageBilling($usagePeriod);
                }
            });

            if (config('reseller-billing.detailed_logging', true)) {
                Log::info('Usage tracked successfully', [
                    'call_id' => $callLog->call_id,
                    'reseller_id' => $callLog->reseller_id,
                    'cost' => $callLog->cost,
                    'total_cost' => $usagePeriod->total_cost,
                    'overage_cost' => $usagePeriod->overage_cost,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error tracking call usage', [
                'call_id' => $callLog->call_id,
                'reseller_id' => $callLog->reseller_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get current usage period for reseller
     * Creates one if it doesn't exist and subscription is active
     */
    public function getCurrentUsagePeriod(string $resellerId): ?ResellerUsagePeriod
    {
        // Try to find existing current period
        $usagePeriod = ResellerUsagePeriod::where('reseller_id', $resellerId)
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->where('overage_status', '!=', 'billed')
            ->first();

        if ($usagePeriod) {
            return $usagePeriod;
        }

        // No current period found, try to create one from active subscription
        $subscription = ResellerSubscription::where('reseller_id', $resellerId)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            Log::info('No active subscription found for reseller', [
                'reseller_id' => $resellerId,
            ]);
            return null;
        }

        // Create new usage period from subscription
        return $this->createUsagePeriod($subscription, null, 0);
    }

    /**
     * Create new usage period when subscription starts/renews
     */
    public function createUsagePeriod(ResellerSubscription $subscription, ?float $carriedForwardAmount = null, ?float $monthlyPackageLimit = null): ResellerUsagePeriod
    {
        $package = $subscription->package;
        $monthlyLimit = $monthlyPackageLimit === null? $package->monthly_minutes_limit: $monthlyPackageLimit;

        // Calculate monthly cost limit based on package type
        if ($package->monthly_minutes_limit > 0) {
            // For minute-limited packages: no cost limit, only minutes limit
            $monthlyCostLimit = 0;
        } else {
            // For unlimited packages: cost limit = package price
            $monthlyCostLimit = $package->price;
        }

        $usagePeriod = ResellerUsagePeriod::create([
            'reseller_id' => $subscription->reseller_id,
            'reseller_subscription_id' => $subscription->id,
            'reseller_package_id' => $subscription->reseller_package_id,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'total_calls' => 0,
            'total_duration_seconds' => 0,
            'total_cost' => 0,
            'monthly_cost_limit' => $monthlyCostLimit,
            'monthly_minutes_limit' => $monthlyLimit,
            'extra_per_minute_charge' => $package->extra_per_minute_charge,
            'overage_cost' => 0,
            'overage_minutes' => 0,
            'overage_status' => 'none',
            'carried_forward_amount' => $carriedForwardAmount ?? 0,
            'metadata' => [
                'package_name' => $package->name,
                'package_price' => $package->price,
                'created_reason' => 'auto_created_from_subscription',
                'created_at_iso' => now()->toISOString(),
            ],
        ]);

        Log::info('Usage period created', [
            'usage_period_id' => $usagePeriod->id,
            'reseller_id' => $subscription->reseller_id,
            'period_start' => $usagePeriod->period_start,
            'period_end' => $usagePeriod->period_end,
            'carried_forward_amount' => $carriedForwardAmount,
        ]);

        return $usagePeriod;
    }

    /**
     * Calculate overage for current period
     * 
     * @return array ['overage_cost' => float, 'overage_minutes' => int]
     */
    public function calculateOverage(ResellerUsagePeriod $period): array
    {
        // If unlimited package, no overage
        if ($period->monthly_minutes_limit === -1) {
            return [
                'overage_cost' => 0,
                'overage_minutes' => 0,
            ];
        }

        // Use duration-based calculation for minute packages
        return $this->calculateDurationBasedOverage($period);
        /* if ($period->monthly_minutes_limit > 0) {
            return $this->calculateDurationBasedOverage($period);
        } else {
            // Cost-based only for unlimited packages
            return $this->calculateCostBasedOverage($period);
        } */
    }

    /**
     * Calculate overage based on actual call costs
     */
    private function calculateCostBasedOverage(ResellerUsagePeriod $period): array
    {
        // For unlimited packages: calculate overage based on cost limit
        $overageCost = max(0, $period->total_cost - $period->monthly_cost_limit);
        
        // Calculate overage minutes for reference
        $totalMinutes = (int) ($period->total_duration_seconds / 60);
        $overageMinutes = max(0, $totalMinutes - $period->monthly_minutes_limit);

        return [
            'overage_cost' => (float) $overageCost,
            'overage_minutes' => $overageMinutes,
        ];
    }

    /**
     * Calculate overage based on call duration
     */
    private function calculateDurationBasedOverage(ResellerUsagePeriod $period): array
    {
        $totalMinutes = (int) ($period->total_duration_seconds / 60);
        $includedMinutes = $period->monthly_minutes_limit;
        $overageMinutes = max(0, $totalMinutes - $includedMinutes);
        
        // Calculate overage cost based on per-minute charge
        $overageCost = $overageMinutes * $period->extra_per_minute_charge;

        return [
            'overage_cost' => (float) $overageCost,
            'overage_minutes' => $overageMinutes,
        ];
    }

    /**
     * Check if billing threshold is met
     */
    public function shouldBillOverage(float $overageAmount): bool
    {
        if (!config('reseller-billing.auto_billing_enabled', true)) {
            return false;
        }

        $threshold = config('reseller-billing.overage_threshold', 10.00);
        return $overageAmount >= $threshold;
    }

    /**
     * Trigger overage billing
     */
    private function triggerOverageBilling(ResellerUsagePeriod $usagePeriod): void
    {
        try {
            $billingService = new ResellerBillingService();
            $billingService->billImmediateOverage($usagePeriod);
        } catch (\Exception $e) {
            Log::error('Failed to trigger overage billing', [
                'usage_period_id' => $usagePeriod->id,
                'reseller_id' => $usagePeriod->reseller_id,
                'overage_amount' => $usagePeriod->getOverageAmount(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update subscription's real-time usage counters
     */
    private function updateSubscriptionCounters(ResellerUsagePeriod $usagePeriod): void
    {
        $subscription = $usagePeriod->subscription;
        
        if (!$subscription) {
            return;
        }

        $subscription->update([
            'current_period_usage_cost' => $usagePeriod->total_cost,
            'current_period_calls' => $usagePeriod->total_calls,
            'current_period_duration' => $usagePeriod->total_duration_seconds,
            'pending_overage_cost' => $usagePeriod->overage_cost,
            'last_usage_calculated_at' => now(),
        ]);
    }

    /**
     * Get usage statistics for a reseller
     * 
     * @param string $resellerId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getUsageStats(string $resellerId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ResellerUsagePeriod::where('reseller_id', $resellerId);

        if ($startDate) {
            $query->where('period_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('period_end', '<=', $endDate);
        }

        $periods = $query->orderBy('period_start', 'desc')->get();

        return [
            'reseller_id' => $resellerId,
            'period_count' => $periods->count(),
            'total_calls' => $periods->sum('total_calls'),
            'total_duration_seconds' => $periods->sum('total_duration_seconds'),
            'total_duration_minutes' => round($periods->sum('total_duration_seconds') / 60, 2),
            'total_cost' => $periods->sum('total_cost'),
            'total_overage_cost' => $periods->sum('overage_cost'),
            'total_billed_overage' => $periods->where('overage_status', 'billed')->sum('overage_cost'),
            'pending_overage' => $periods->where('overage_status', 'pending')->sum('overage_cost'),
            'periods' => $periods->map(function ($period) {
                return [
                    'id' => $period->id,
                    'period_start' => $period->period_start,
                    'period_end' => $period->period_end,
                    'total_calls' => $period->total_calls,
                    'total_cost' => $period->total_cost,
                    'overage_cost' => $period->overage_cost,
                    'overage_status' => $period->overage_status,
                    'is_current' => $period->isCurrent(),
                ];
            }),
        ];
    }

    /**
     * Get current usage summary for dashboard
     */
    public function getCurrentUsageSummary(string $resellerId): array
    {
        $usagePeriod = $this->getCurrentUsagePeriod($resellerId);

        if (!$usagePeriod) {
            return [
                'has_active_period' => false,
                'message' => 'No active billing period found',
            ];
        }

        $usagePercentage = $usagePeriod->getUsagePercentage();
        $isUnlimited = $usagePeriod->isUnlimitedPackage();

        return [
            'has_active_period' => true,
            'usage_period_id' => $usagePeriod->id,
            'period_start' => $usagePeriod->period_start,
            'period_end' => $usagePeriod->period_end,
            'days_remaining' => now()->diffInDays($usagePeriod->period_end),
            'is_unlimited' => $isUnlimited,
            'total_calls' => $usagePeriod->total_calls,
            'total_duration_minutes' => round($usagePeriod->total_duration_seconds / 60, 2),
            'total_cost' => $usagePeriod->total_cost,
            'monthly_minutes_limit' => $usagePeriod->monthly_minutes_limit,
            'usage_percentage' => round($usagePercentage, 2),
            'overage_cost' => $usagePeriod->overage_cost,
            'carried_forward_amount' => $usagePeriod->carried_forward_amount,
            'total_overage' => $usagePeriod->getOverageAmount(),
            'overage_status' => $usagePeriod->overage_status,
            'formatted_total_cost' => $usagePeriod->formatted_total_cost,
            'formatted_overage_cost' => $usagePeriod->formatted_overage_cost,
        ];
    }
}

