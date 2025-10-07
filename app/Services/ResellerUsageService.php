<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\CallLog;
use App\Models\User;
use Carbon\Carbon;

class ResellerUsageService
{
    /**
     * Calculate total minutes usage for a reseller's current billing period
     */
    public function getCurrentBillingPeriodMinutesUsage(Reseller $reseller): float
    {
        $subscription = $reseller->activeSubscription;
        
        if (!$subscription || !$subscription->current_period_start || !$subscription->current_period_end) {
            // Fallback to calendar month if no billing period is set
            return $this->getMinutesUsageForPeriod($reseller, Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        }

        return $this->getMinutesUsageForPeriod(
            $reseller, 
            $subscription->current_period_start, 
            $subscription->current_period_end
        );
    }

    /**
     * Calculate total minutes usage for a reseller in a specific period
     */
    public function getMinutesUsageForPeriod(Reseller $reseller, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Get all user IDs for this reseller
        $userIds = $reseller->users()->pluck('id');
        
        if ($userIds->isEmpty()) {
            return 0;
        }

        // Calculate total minutes used by all users under this reseller
        $totalSeconds = CallLog::whereIn('user_id', $userIds)
            ->whereBetween('start_time', [$periodStart, $periodEnd])
            ->whereNotNull('duration')
            ->sum('duration');

        return round($totalSeconds / 60, 2); // Convert seconds to minutes
    }

    /**
     * Get the current billing period for a reseller
     */
    public function getCurrentBillingPeriod(Reseller $reseller): array
    {
        $subscription = $reseller->activeSubscription;
        
        if (!$subscription || !$subscription->current_period_start || !$subscription->current_period_end) {
            // Fallback to calendar month
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            
            return [
                'start' => $start,
                'end' => $end,
                'is_fallback' => true,
                'interval_type' => 'calendar_month',
                'subscription_day' => null
            ];
        }

        return [
            'start' => $subscription->current_period_start,
            'end' => $subscription->current_period_end,
            'is_fallback' => false,
            'interval_type' => 'subscription_based',
            'subscription_day' => $subscription->current_period_start->day
        ];
    }

    /**
     * Calculate overage charges for a reseller
     */
    public function calculateOverageCharges(Reseller $reseller): array
    {
        $subscription = $reseller->activeSubscription;
        
        if (!$subscription || !$subscription->package) {
            return [
                'overage_minutes' => 0,
                'overage_cost' => 0,
                'total_minutes_used' => 0,
                'monthly_limit' => 0,
                'extra_per_minute_charge' => 0
            ];
        }

        $package = $subscription->package;
        
        $billingPeriod = $this->getCurrentBillingPeriod($reseller);
        $totalMinutesUsed = $this->getCurrentBillingPeriodMinutesUsage($reseller);

        // If unlimited minutes, no overage
        if ($package->isUnlimited('monthly_minutes_limit')) {
            return [
                'overage_minutes' => 0,
                'overage_cost' => 0,
                'total_minutes_used' => $totalMinutesUsed,
                'monthly_limit' => -1,
                'extra_per_minute_charge' => $package->extra_per_minute_charge,
                'is_unlimited' => true,
                'billing_period_start' => $billingPeriod['start'],
                'billing_period_end' => $billingPeriod['end'],
                'is_calendar_month_fallback' => $billingPeriod['is_fallback'],
                'interval_type' => $billingPeriod['interval_type'],
                'subscription_day' => $billingPeriod['subscription_day'] ?? null
            ];
        }

        $monthlyLimit = $package->monthly_minutes_limit;
        $overageMinutes = max(0, $totalMinutesUsed - $monthlyLimit);
        $overageCost = $overageMinutes * $package->extra_per_minute_charge;

        return [
            'overage_minutes' => round($overageMinutes, 2),
            'overage_cost' => round($overageCost, 2),
            'total_minutes_used' => round($totalMinutesUsed, 2),
            'monthly_limit' => $monthlyLimit,
            'extra_per_minute_charge' => $package->extra_per_minute_charge,
            'is_unlimited' => false,
            'billing_period_start' => $billingPeriod['start'],
            'billing_period_end' => $billingPeriod['end'],
            'is_calendar_month_fallback' => $billingPeriod['is_fallback'],
            'interval_type' => $billingPeriod['interval_type'],
            'subscription_day' => $billingPeriod['subscription_day'] ?? null
        ];
    }

    /**
     * Get comprehensive usage statistics for a reseller
     */
    public function getUsageStatistics(Reseller $reseller): array
    {
        $subscription = $reseller->activeSubscription;
        
        if (!$subscription || !$subscription->package) {
            return [
                'has_active_subscription' => false
            ];
        }

        $package = $subscription->package;
        $overageData = $this->calculateOverageCharges($reseller);
        
        // Get user and assistant counts
        $totalUsers = $reseller->users()->count();
        $activeUsers = $reseller->activeUsers()->count();
        $totalAssistants = $reseller->users()->withCount('assistants')->get()->sum('assistants_count');
        
        // Calculate remaining users (if there's a limit)
        $remainingUsers = $package->isUnlimited('voice_agents_limit') 
            ? -1 
            : max(0, $package->voice_agents_limit - $totalUsers);

        return [
            'has_active_subscription' => true,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'price' => $package->price,
                'yearly_price' => $package->yearly_price,
            ],
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'days_remaining' => $subscription->current_period_end ? 
                    max(0, $subscription->current_period_end->diffInDays(now())) : 0,
            ],
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'limit' => $package->formatted_voice_agents_limit,
                'remaining' => $remainingUsers,
                'is_unlimited' => $package->isUnlimited('voice_agents_limit'),
            ],
            'assistants' => [
                'total' => $totalAssistants,
            ],
            'minutes' => [
                'used' => $overageData['total_minutes_used'],
                'limit' => $overageData['monthly_limit'],
                'overage_minutes' => $overageData['overage_minutes'],
                'overage_cost' => $overageData['overage_cost'],
                'extra_per_minute_charge' => $overageData['extra_per_minute_charge'],
                'is_unlimited' => $overageData['is_unlimited'],
                'billing_period_start' => $overageData['billing_period_start'],
                'billing_period_end' => $overageData['billing_period_end'],
                'is_calendar_month_fallback' => $overageData['is_calendar_month_fallback'],
                'interval_type' => $overageData['interval_type'],
                'subscription_day' => $overageData['subscription_day'],
            ]
        ];
    }

    /**
     * Get usage statistics for all resellers (for superadmin)
     */
    public function getAllResellersUsageStatistics(): array
    {
        $resellers = Reseller::with(['activeSubscription.package', 'users'])->get();
        $usageData = [];

        foreach ($resellers as $reseller) {
            $statistics = $this->getUsageStatistics($reseller);
            
            $resellerData = [
                'reseller_id' => $reseller->id,
                'reseller_name' => $reseller->org_name,
                'reseller_email' => $reseller->company_email,
                'reseller_status' => $reseller->status,
                'statistics' => $statistics
            ];

            $usageData[] = $resellerData;
        }

        return $usageData;
    }

    /**
     * Check if reseller can create more users
     */
    public function canCreateMoreUsers(Reseller $reseller): bool
    {
        $subscription = $reseller->activeSubscription;
        
        if (!$subscription || !$subscription->package) {
            return false;
        }

        $package = $subscription->package;
        
        // If unlimited users, always allow
        if ($package->isUnlimited('voice_agents_limit')) {
            return true;
        }

        $currentUserCount = $reseller->users()->count();
        return $currentUserCount < $package->voice_agents_limit;
    }
}
