<?php

namespace App\Services;

use App\Models\User;
use App\Models\CallLog;
use App\Models\UserSubscription;
use Carbon\Carbon;

class OverageBillingService
{
    /**
     * Calculate minutes usage for a user's current billing period
     */
    public function getCurrentBillingPeriodMinutesUsage(User $user): float
    {
        $subscription = $user->activeSubscription;
        
        if (!$subscription || !$subscription->current_period_start || !$subscription->current_period_end) {
            // Fallback to calendar month if no billing period is set
            return $this->getMinutesUsageForPeriod($user, Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        }

        return $this->getMinutesUsageForPeriod(
            $user, 
            $subscription->current_period_start, 
            $subscription->current_period_end
        );
    }

    /**
     * Calculate minutes usage for a specific period
     */
    public function getMinutesUsageForPeriod(User $user, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Calculate total minutes used in the period (in minutes)
        $totalSeconds = CallLog::where('user_id', $user->id)
            ->whereBetween('start_time', [$periodStart, $periodEnd])
            ->whereNotNull('duration')
            ->sum('duration');

        return round($totalSeconds / 60, 2); // Convert seconds to minutes
    }

    /**
     * Get the current billing period for a user
     */
    public function getCurrentBillingPeriod(User $user): array
    {
        $subscription = $user->activeSubscription;
        
        if (!$subscription || !$subscription->current_period_start || !$subscription->current_period_end) {
            // Fallback to calendar month if no billing period is set
            $now = Carbon::now();
            return [
                'start' => $now->startOfMonth()->copy(),
                'end' => $now->endOfMonth()->copy(),
                'is_fallback' => true,
                'interval_type' => 'fallback'
            ];
        }

        // Get billing interval from metadata
        $billingInterval = $subscription->metadata['billing_interval'] ?? null;
        
        // If it's not explicitly yearly, check period length to determine
        if (!$billingInterval) {
            $daysDiff = $subscription->current_period_start->diffInDays($subscription->current_period_end);
            $billingInterval = $daysDiff > 300 ? 'yearly' : 'monthly';
        }

        if ($billingInterval === 'yearly') {
            return $this->getCurrentMonthlyPeriodWithinYearlySubscription($subscription);
        }

        // For monthly subscriptions, use the full period
        return [
            'start' => $subscription->current_period_start,
            'end' => $subscription->current_period_end,
            'is_fallback' => false,
            'interval_type' => 'monthly'
        ];
    }

    /**
     * Calculate the current monthly period within a yearly subscription
     */
    private function getCurrentMonthlyPeriodWithinYearlySubscription($subscription): array
    {
        $subscriptionStartDate = $subscription->current_period_start;
        $dayOfMonth = $subscriptionStartDate->day;
        $now = Carbon::now();
        
        // Get the current month's billing period
        // Example: If user subscribed on 26th, and today is Dec 15th
        // The current monthly period should be Nov 26 - Dec 26
        
        $currentMonthStart = Carbon::create($now->year, $now->month, $dayOfMonth);
        
        // If we haven't reached the billing day this month, use previous month
        if ($now->day < $dayOfMonth) {
            $currentMonthStart = $currentMonthStart->subMonth();
        }
        
        $currentMonthEnd = $currentMonthStart->copy()->addMonth();
        
        // Ensure we don't go beyond the yearly subscription end date
        $subscriptionEnd = $subscription->current_period_end;
        if ($currentMonthEnd->gt($subscriptionEnd)) {
            $currentMonthEnd = $subscriptionEnd;
        }

        // Edge case: if start and end are the same, it means today is the exact billing day
        // In this case, we want the current monthly cycle (today to next month)
        if ($currentMonthStart->eq($currentMonthEnd)) {
            $currentMonthEnd = $currentMonthStart->copy()->addMonth();
            
            // Re-check if this goes beyond subscription end
            if ($currentMonthEnd->gt($subscriptionEnd)) {
                $currentMonthEnd = $subscriptionEnd;
            }
        }

        return [
            'start' => $currentMonthStart,
            'end' => $currentMonthEnd,
            'is_fallback' => false,
            'interval_type' => 'yearly_monthly_cycle',
            'subscription_day' => $dayOfMonth
        ];
    }

    /**
     * Calculate overage charges for a user's current billing period
     */
    public function calculateOverageCharges(User $user): array
    {
        $subscription = $user->activeSubscription;
        
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
        
        $billingPeriod = $this->getCurrentBillingPeriod($user);
        $totalMinutesUsed = $this->getCurrentBillingPeriodMinutesUsage($user);

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
            'total_minutes_used' => $totalMinutesUsed,
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
     * Get usage statistics including overage information
     */
    public function getUsageStatistics(User $user, bool $useContentProtection = true): array
    {
        $subscription = $user->activeSubscription;
        
        if (!$subscription || !$subscription->package) {
            return [
                'has_active_subscription' => false
            ];
        }

        $package = $subscription->package;
        $overageData = $this->calculateOverageCharges($user);
        
        // Assistant usage
        if ($useContentProtection && app()->runningInConsole() === false) {
            $assistantsCount = $user->assistants()->contentProtection()->count();
        } else {
            $assistantsCount = $user->assistants()->count();
        }
        $remainingAssistants = $user->remaining_assistants;

        return [
            'has_active_subscription' => true,
            'assistants' => [
                'used' => $assistantsCount,
                'limit' => $package->formatted_voice_agents_limit,
                'remaining' => $remainingAssistants,
                'is_unlimited' => $package->isUnlimited('voice_agents_limit'),
            ],
            'minutes' => [
                'used' => $overageData['total_minutes_used'],
                'limit' => $overageData['monthly_limit'],
                'remaining' => $overageData['is_unlimited'] ? -1 : max(0, $overageData['monthly_limit'] - $overageData['total_minutes_used']),
                'overage_minutes' => $overageData['overage_minutes'],
                'overage_cost' => $overageData['overage_cost'],
                'extra_per_minute_charge' => $overageData['extra_per_minute_charge'],
                'is_unlimited' => $overageData['is_unlimited'],
                'billing_period_start' => $overageData['billing_period_start'] ?? null,
                'billing_period_end' => $overageData['billing_period_end'] ?? null,
                'is_calendar_month_fallback' => $overageData['is_calendar_month_fallback'] ?? false,
                'interval_type' => $overageData['interval_type'] ?? 'unknown',
                'subscription_day' => $overageData['subscription_day'] ?? null,
            ],
            'subscription' => [
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
                'days_remaining' => $subscription->daysUntilExpiration(),
            ],
            'package' => [
                'name' => $package->name,
                'price' => $package->formatted_price,
                'description' => $package->description,
                'extra_per_minute_charge' => $package->formatted_extra_per_minute_charge,
            ]
        ];
    }

    /**
     * Check if user is within their limits
     */
    public function checkUsageLimits(User $user): array
    {
        $statistics = $this->getUsageStatistics($user);
        
        if (!$statistics['has_active_subscription']) {
            return [
                'can_create_assistant' => false,
                'can_make_calls' => false,
                'reason' => 'No active subscription'
            ];
        }

        $canCreateAssistant = $statistics['assistants']['is_unlimited'] || $statistics['assistants']['remaining'] > 0;
        
        // Users can always make calls, but they'll be charged for overage
        $canMakeCalls = true;
        
        return [
            'can_create_assistant' => $canCreateAssistant,
            'can_make_calls' => $canMakeCalls,
            'usage_statistics' => $statistics
        ];
    }
}
