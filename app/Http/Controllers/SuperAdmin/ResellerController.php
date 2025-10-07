<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\CallLog;
use App\Models\User;
use App\Models\Assistant;
use App\Services\ResellerUsageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResellerController extends Controller
{
    protected $usageService;

    public function __construct(ResellerUsageService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Display a listing of resellers
     */
    public function index()
    {
        $resellers = Reseller::with(['adminUser', 'activeSubscription'])
            ->withCount(['users', 'assistants'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resellers
        ]);
    }

    /**
     * Display the specified reseller with detailed information
     */
    public function show(Request $request, $resellerId)
    {
        $reseller = Reseller::with([
            'adminUser',
            'activeSubscription.package',
            'subscriptions.package',
            'transactions'
        ])->findOrFail($resellerId);

        // Get basic counts
        $totalUsers = $reseller->users()->count();
        $activeUsers = $reseller->activeUsers()->count();
        $totalAssistants = $reseller->assistants()->count();
        $activeAssistants = $reseller->assistants()->where('type', 'production')->count();

        // Get usage statistics
        $usageStats = $this->getUsageStatistics($reseller);

        // Get assistants with pagination
        $assistantsPerPage = $request->get('assistants_per_page', 10);
        $assistantsPage = $request->get('assistants_page', 1);
        $assistants = $this->getAssistantsWithUsage($reseller, $assistantsPerPage, $assistantsPage);

        // Get users with pagination
        $usersPerPage = $request->get('users_per_page', 10);
        $usersPage = $request->get('users_page', 1);
        $users = $this->getUsersWithUsage($reseller, $usersPerPage, $usersPage);

        // Get recent activity
        $recentActivity = $this->getRecentActivity($reseller);

        // Get subscription and billing information
        $billingInfo = $this->getBillingInformation($reseller);

        // Get billing period usage for reseller
        $resellerBillingUsage = $this->getResellerBillingPeriodUsage($reseller);

        // Get billing period usage for users
        $usersBillingUsage = $this->getUsersBillingPeriodUsage($reseller, $usersPerPage, $usersPage);

        return response()->json([
            'success' => true,
            'data' => [
                'reseller' => $reseller,
                'statistics' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'total_assistants' => $totalAssistants,
                    'active_assistants' => $activeAssistants,
                    'usage_stats' => $usageStats,
                ],
                'assistants' => $assistants,
                'users' => $users,
                'recent_activity' => $recentActivity,
                'billing_info' => $billingInfo,
                'reseller_billing_usage' => $resellerBillingUsage,
                'users_billing_usage' => $usersBillingUsage,
            ]
        ]);
    }

    /**
     * Get usage statistics for the reseller
     */
    private function getUsageStatistics(Reseller $reseller)
    {
        $userIds = $reseller->users()->pluck('id');
        
        if ($userIds->isEmpty()) {
            return [
                'total_calls' => 0,
                'total_minutes' => 0,
                'this_month_calls' => 0,
                'this_month_minutes' => 0,
                'average_call_duration' => 0,
                'current_billing_period_minutes' => 0,
            ];
        }

        // Total calls and minutes
        $totalCalls = CallLog::whereIn('user_id', $userIds)->count();
        $totalMinutes = CallLog::whereIn('user_id', $userIds)
            ->whereNotNull('duration')
            ->sum('duration') / 60;

        // This month's data
        $thisMonth = Carbon::now()->startOfMonth();
        $thisMonthCalls = CallLog::whereIn('user_id', $userIds)
            ->where('start_time', '>=', $thisMonth)
            ->count();
        $thisMonthMinutes = CallLog::whereIn('user_id', $userIds)
            ->where('start_time', '>=', $thisMonth)
            ->whereNotNull('duration')
            ->sum('duration') / 60;

        // Average call duration
        $avgDuration = CallLog::whereIn('user_id', $userIds)
            ->whereNotNull('duration')
            ->avg('duration');

        // Current billing period usage
        $currentBillingPeriodMinutes = $this->usageService->getCurrentBillingPeriodMinutesUsage($reseller);

        return [
            'total_calls' => $totalCalls,
            'total_minutes' => round($totalMinutes, 2),
            'this_month_calls' => $thisMonthCalls,
            'this_month_minutes' => round($thisMonthMinutes, 2),
            'average_call_duration' => round($avgDuration ?: 0, 2),
            'current_billing_period_minutes' => $currentBillingPeriodMinutes,
        ];
    }

    /**
     * Get assistants with their usage statistics
     */
    private function getAssistantsWithUsage(Reseller $reseller, $perPage = 10, $page = 1)
    {
        $assistants = $reseller->assistants()
            ->with(['user:id,name,email'])
            ->withCount('callLogs')
            ->withSum('callLogs', 'duration')
            ->orderBy('call_logs_count', 'desc')
            ->paginate($perPage, ['*'], 'assistants_page', $page);

        return [
            'data' => $assistants->map(function ($assistant) {
                return [
                    'id' => $assistant->id,
                    'name' => $assistant->name,
                    'phone_number' => $assistant->phone_number,
                    'type' => $assistant->type,
                    'user_name' => $assistant->user->name ?? 'Unknown',
                    'user_email' => $assistant->user->email ?? 'Unknown',
                    'total_calls' => $assistant->call_logs_count,
                    'total_minutes' => round(($assistant->call_logs_sum_duration ?: 0) / 60, 2),
                    'created_at' => $assistant->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $assistants->currentPage(),
                'last_page' => $assistants->lastPage(),
                'per_page' => $assistants->perPage(),
                'total' => $assistants->total(),
                'from' => $assistants->firstItem(),
                'to' => $assistants->lastItem(),
            ]
        ];
    }

    /**
     * Get users with their assistants and usage
     */
    private function getUsersWithUsage(Reseller $reseller, $perPage = 10, $page = 1)
    {
        $users = $reseller->users()
            ->withCount('assistants')
            ->with(['assistants' => function ($query) {
                $query->withCount('callLogs')
                      ->withSum('callLogs', 'duration');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'users_page', $page);

        return [
            'data' => $users->map(function ($user) {
                $totalCalls = $user->assistants->sum('call_logs_count');
                $totalMinutes = $user->assistants->sum('call_logs_sum_duration') / 60;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'total_assistants' => $user->assistants_count,
                    'total_calls' => $totalCalls,
                    'total_minutes' => round($totalMinutes, 2),
                    'created_at' => $user->created_at,
                    'last_login' => $user->last_login_at,
                ];
            }),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ]
        ];
    }

    /**
     * Get recent activity for the reseller
     */
    private function getRecentActivity(Reseller $reseller)
    {
        $userIds = $reseller->users()->pluck('id');
        
        if ($userIds->isEmpty()) {
            return [
                'recent_calls' => [],
                'recent_users' => [],
                'recent_assistants' => [],
            ];
        }

        $recentCalls = CallLog::whereIn('user_id', $userIds)
            ->with(['user:id,name', 'assistant:id,name'])
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'user_name' => $call->user->name ?? 'Unknown',
                    'assistant_name' => $call->assistant->name ?? 'Unknown',
                    'status' => $call->status,
                    'duration' => $call->duration,
                    'start_time' => $call->start_time,
                ];
            });

        $recentUsers = $reseller->users()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ];
            });

        $recentAssistants = $reseller->assistants()
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($assistant) {
                return [
                    'id' => $assistant->id,
                    'name' => $assistant->name,
                    'phone_number' => $assistant->phone_number,
                    'type' => $assistant->type,
                    'user_name' => $assistant->user->name ?? 'Unknown',
                    'created_at' => $assistant->created_at,
                ];
            });

        return [
            'recent_calls' => $recentCalls,
            'recent_users' => $recentUsers,
            'recent_assistants' => $recentAssistants,
        ];
    }

    /**
     * Get billing information for the reseller
     */
    private function getBillingInformation(Reseller $reseller)
    {
        $activeSubscription = $reseller->activeSubscription;
        
        if (!$activeSubscription) {
            return [
                'has_active_subscription' => false,
                'subscription_status' => 'No active subscription',
                'package_name' => null,
                'billing_period' => null,
                'total_revenue' => 0,
                'this_month_revenue' => 0,
            ];
        }

        $totalRevenue = $reseller->transactions()
            ->where('status', 'completed')
            ->sum('amount');

        $thisMonthRevenue = $reseller->transactions()
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');

        $billingPeriod = $this->usageService->getCurrentBillingPeriod($reseller);

        return [
            'has_active_subscription' => true,
            'subscription_status' => $activeSubscription->status,
            'package_name' => $activeSubscription->package->name ?? 'Custom Package',
            'billing_period' => $billingPeriod,
            'total_revenue' => round($totalRevenue, 2),
            'this_month_revenue' => round($thisMonthRevenue, 2),
            'subscription_details' => [
                'current_period_start' => $activeSubscription->current_period_start,
                'current_period_end' => $activeSubscription->current_period_end,
                'trial_ends_at' => $activeSubscription->trial_ends_at,
                'stripe_subscription_id' => $activeSubscription->stripe_subscription_id,
            ],
        ];
    }

    /**
     * Get reseller's billing period usage
     */
    private function getResellerBillingPeriodUsage(Reseller $reseller)
    {
        $activeSubscription = $reseller->activeSubscription;
        
        if (!$activeSubscription) {
            return [
                'has_active_subscription' => false,
                'billing_period' => null,
                'usage_stats' => null,
            ];
        }

        // Calculate billing period dates
        $billingPeriod = $this->usageService->getCurrentBillingPeriod($reseller);
        
        if (!$billingPeriod) {
            return [
                'has_active_subscription' => true,
                'billing_period' => null,
                'usage_stats' => null,
            ];
        }

        $startDate = Carbon::parse($billingPeriod['start']);
        $endDate = Carbon::parse($billingPeriod['end']);

        // Get usage statistics for the billing period
        $usageStats = CallLog::whereHas('assistant', function ($query) use ($reseller) {
                $query->where('reseller_id', $reseller->id);
            })
            ->whereBetween('start_time', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_calls,
                SUM(duration) as total_duration,
                SUM(cost) as total_cost,
                AVG(duration) as avg_duration,
                COUNT(DISTINCT assistant_id) as unique_assistants,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->first();

        // Get package limits
        $package = $activeSubscription->package;
        $limits = [
            'monthly_minutes_limit' => $package->monthly_minutes_limit ?? 0,
            'voice_agents_limit' => $package->voice_agents_limit ?? 0,
            'users_limit' => $package->users_limit ?? 0,
        ];

        return [
            'has_active_subscription' => true,
            'billing_period' => $billingPeriod,
            'usage_stats' => [
                'total_calls' => $usageStats->total_calls ?? 0,
                'total_minutes' => round(($usageStats->total_duration ?? 0) / 60, 2),
                'total_cost' => round($usageStats->total_cost ?? 0, 2),
                'avg_duration' => round($usageStats->avg_duration ?? 0, 2),
                'unique_assistants' => $usageStats->unique_assistants ?? 0,
                'unique_users' => $usageStats->unique_users ?? 0,
            ],
            'limits' => $limits,
            'usage_percentages' => [
                'minutes_used_percent' => $limits['monthly_minutes_limit'] > 0 
                    ? round((($usageStats->total_duration ?? 0) / 60) / $limits['monthly_minutes_limit'] * 100, 1)
                    : 0,
                'assistants_used_percent' => $limits['voice_agents_limit'] > 0 
                    ? round(($usageStats->unique_assistants ?? 0) / $limits['voice_agents_limit'] * 100, 1)
                    : 0,
                'users_used_percent' => $limits['users_limit'] > 0 
                    ? round(($usageStats->unique_users ?? 0) / $limits['users_limit'] * 100, 1)
                    : 0,
            ],
        ];
    }

    /**
     * Get users' billing period usage
     */
    private function getUsersBillingPeriodUsage(Reseller $reseller, $perPage = 10, $page = 1)
    {
        $activeSubscription = $reseller->activeSubscription;
        
        if (!$activeSubscription) {
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                ]
            ];
        }

        // Calculate billing period dates
        $billingPeriod = $this->usageService->getCurrentBillingPeriod($reseller);
        
        if (!$billingPeriod) {
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                ]
            ];
        }

        $startDate = Carbon::parse($billingPeriod['start']);
        $endDate = Carbon::parse($billingPeriod['end']);

        // Get users with their billing period usage
        $users = $reseller->users()
            ->withCount('assistants')
            ->with(['assistants' => function ($query) {
                $query->withCount('callLogs')
                      ->withSum('callLogs', 'duration')
                      ->withSum('callLogs', 'cost');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'users_page', $page);

        $transformedUsers = $users->map(function ($user) use ($startDate, $endDate) {
            // Calculate billing period usage for this user
            $billingUsage = CallLog::where('user_id', $user->id)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(duration) as total_duration,
                    SUM(cost) as total_cost,
                    AVG(duration) as avg_duration,
                    COUNT(DISTINCT assistant_id) as unique_assistants
                ')
                ->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'total_assistants' => $user->assistants_count,
                'billing_period_usage' => [
                    'total_calls' => $billingUsage->total_calls ?? 0,
                    'total_minutes' => round(($billingUsage->total_duration ?? 0) / 60, 2),
                    'total_cost' => round($billingUsage->total_cost ?? 0, 2),
                    'avg_duration' => round($billingUsage->avg_duration ?? 0, 2),
                    'unique_assistants' => $billingUsage->unique_assistants ?? 0,
                ],
                'created_at' => $user->created_at,
                'last_login' => $user->last_login_at,
            ];
        });

        return [
            'data' => $transformedUsers,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ]
        ];
    }
}
