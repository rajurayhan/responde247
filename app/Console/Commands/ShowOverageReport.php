<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\OverageBillingService;

class ShowOverageReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overage:report {--user-id=} {--with-zero}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show overage report for users with exceeded minute limits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $overageBillingService = new OverageBillingService();
        
        // Get users to check
        if ($this->option('user-id')) {
            $users = User::where('id', $this->option('user-id'))->get();
        } else {
            $users = User::whereHas('activeSubscription')->get();
        }

        if ($users->isEmpty()) {
            $this->info('No users found with active subscriptions.');
            return;
        }

        $this->info('🔍 Overage Report - ' . now()->format('Y-m-d H:i:s'));
        $this->line('');

        $headers = ['User ID', 'Email', 'Package', 'Minutes Used', 'Limit', 'Overage', 'Extra Cost', 'Rate/Min', 'Billing Period'];
        $rows = [];

        foreach ($users as $user) {
            $statistics = $overageBillingService->getUsageStatistics($user, false);
            
            if (!$statistics['has_active_subscription']) {
                continue;
            }

            $minutes = $statistics['minutes'];
            
            // Skip users with no overage unless --with-zero is specified
            if (!$this->option('with-zero') && $minutes['overage_minutes'] <= 0) {
                continue;
            }

            $billingPeriod = 'Calendar Month';
            if (isset($minutes['billing_period_start']) && isset($minutes['billing_period_end'])) {
                $start = $minutes['billing_period_start']->format('M j');
                $end = $minutes['billing_period_end']->format('M j');
                $billingPeriod = $start . ' - ' . $end;
                
                // Add interval type indicator
                $intervalType = $minutes['interval_type'] ?? 'unknown';
                if ($intervalType === 'yearly_monthly_cycle') {
                    $billingPeriod .= ' (Y)';
                } elseif ($intervalType === 'monthly') {
                    $billingPeriod .= ' (M)';
                } elseif ($minutes['is_calendar_month_fallback'] ?? false) {
                    $billingPeriod .= ' (FB)';
                }
            }

            $rows[] = [
                $user->id,
                $user->email,
                $statistics['package']['name'],
                number_format($minutes['used'], 1),
                $minutes['is_unlimited'] ? 'Unlimited' : number_format($minutes['limit']),
                $minutes['overage_minutes'] > 0 ? number_format($minutes['overage_minutes'], 1) : '0',
                $minutes['overage_cost'] > 0 ? '$' . number_format($minutes['overage_cost'], 2) : '$0.00',
                '$' . number_format($minutes['extra_per_minute_charge'], 4),
                $billingPeriod
            ];
        }

        if (empty($rows)) {
            $this->info('✅ No users have overage charges this month!');
            $this->line('');
            $this->comment('💡 Use --with-zero to see all users including those without overage');
            $this->comment('💡 Use --user-id=123 to check a specific user');
            return;
        }

        $this->table($headers, $rows);
        
        // Summary
        $totalOverageMinutes = collect($rows)->sum(function($row) {
            return (float) str_replace(',', '', $row[5]);
        });
        
        $totalOverageCost = collect($rows)->sum(function($row) {
            return (float) str_replace(['$', ','], '', $row[6]);
        });

        $this->line('');
        $this->info('📊 Summary:');
        $this->line('Total Users with Overage: ' . count($rows));
        $this->line('Total Overage Minutes: ' . number_format($totalOverageMinutes, 1));
        $this->line('Total Overage Cost: $' . number_format($totalOverageCost, 2));
        
        $this->line('');
        $this->comment('💡 Tips:');
        $this->comment('- Users will see overage info in their subscription dashboard');
        $this->comment('- Red progress bars indicate overage in the UI');
        $this->comment('- Use this data for billing and customer outreach');
        $this->comment('- Billing periods are based on subscription dates (e.g., 26th to 26th)');
        $this->comment('- (M) = Monthly subscription, (Y) = Yearly (monthly cycle), (FB) = Fallback');
    }
}