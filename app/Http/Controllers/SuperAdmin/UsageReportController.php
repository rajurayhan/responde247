<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\User;
use App\Models\Assistant;
use App\Models\CallLog;
use App\Services\ResellerUsageService;
use App\Services\OverageBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageReportController extends Controller
{
    /**
     * Display usage report with filtering options
     */
    public function index(Request $request)
    {
        $query = CallLog::query()
            ->with(['assistant.user', 'assistant.reseller', 'user'])
            ->select([
                'assistant_id',
                'user_id',
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(duration) as total_duration'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('AVG(duration) as avg_duration'),
                DB::raw('MIN(start_time) as first_call'),
                DB::raw('MAX(start_time) as last_call')
            ])
            ->groupBy('assistant_id', 'user_id');

        // Apply filters
        if ($request->filled('reseller_id')) {
            $query->whereHas('assistant.reseller', function ($q) use ($request) {
                $q->where('id', $request->reseller_id);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Get paginated results
        $perPage = $request->get('per_page', 25);
        $usageData = $query->paginate($perPage);

        // Transform the data
        $transformedData = $usageData->map(function ($item) {
            return [
                'assistant_id' => $item->assistant_id,
                'assistant_name' => $item->assistant->name ?? 'Unknown',
                'assistant_phone' => $item->assistant->phone_number ?? 'N/A',
                'assistant_type' => $item->assistant->type ?? 'N/A',
                'user_id' => $item->user_id,
                'user_name' => $item->user->name ?? 'Unknown',
                'user_email' => $item->user->email ?? 'Unknown',
                'reseller_id' => $item->assistant->reseller_id ?? null,
                'reseller_name' => $item->assistant->reseller->org_name ?? 'Unknown',
                'total_calls' => $item->total_calls,
                'total_duration' => $item->total_duration,
                'total_minutes' => round(($item->total_duration ?? 0) / 60, 2),
                'total_cost' => round($item->total_cost ?? 0, 2),
                'avg_duration' => round($item->avg_duration ?? 0, 2),
                'first_call' => $item->first_call,
                'last_call' => $item->last_call,
            ];
        });

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        // Get summary statistics
        $summaryStats = $this->getSummaryStatistics($request);

        // Get billing period usage if reseller or user is selected
        $billingPeriodUsage = $this->getBillingPeriodUsage($request);

        return response()->json([
            'success' => true,
            'data' => [
                'usage_data' => [
                    'data' => $transformedData,
                    'pagination' => [
                        'current_page' => $usageData->currentPage(),
                        'last_page' => $usageData->lastPage(),
                        'per_page' => $usageData->perPage(),
                        'total' => $usageData->total(),
                        'from' => $usageData->firstItem(),
                        'to' => $usageData->lastItem(),
                    ]
                ],
                'filter_options' => $filterOptions,
                'summary_stats' => $summaryStats,
                'billing_period_usage' => $billingPeriodUsage,
            ]
        ]);
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions()
    {
        $resellers = Reseller::select('id', 'org_name')
            ->orderBy('org_name')
            ->get()
            ->map(function ($reseller) {
                return [
                    'value' => $reseller->id,
                    'label' => $reseller->org_name
                ];
            });

        $users = User::select('id', 'name', 'email', 'reseller_id')
            ->with('reseller:id,org_name')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'label' => $user->name . ' (' . $user->email . ')',
                    'reseller_name' => $user->reseller->org_name ?? 'No Reseller'
                ];
            });

        $assistants = Assistant::select('id', 'name', 'phone_number', 'user_id', 'reseller_id')
            ->with(['user:id,name', 'reseller:id,org_name'])
            ->orderBy('name')
            ->get()
            ->map(function ($assistant) {
                return [
                    'value' => $assistant->id,
                    'label' => $assistant->name . ' (' . $assistant->phone_number . ')',
                    'user_name' => $assistant->user->name ?? 'Unknown',
                    'reseller_name' => $assistant->reseller->org_name ?? 'Unknown'
                ];
            });

        return [
            'resellers' => $resellers,
            'users' => $users,
            'assistants' => $assistants,
        ];
    }

    /**
     * Get summary statistics for the filtered data
     */
    private function getSummaryStatistics(Request $request)
    {
        $query = CallLog::query();

        // Apply same filters as main query
        if ($request->filled('reseller_id')) {
            $query->whereHas('assistant.reseller', function ($q) use ($request) {
                $q->where('id', $request->reseller_id);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        $stats = $query->select([
            DB::raw('COUNT(*) as total_calls'),
            DB::raw('COUNT(DISTINCT assistant_id) as unique_assistants'),
            DB::raw('COUNT(DISTINCT user_id) as unique_users'),
            DB::raw('COUNT(DISTINCT reseller_id) as unique_resellers'),
            DB::raw('SUM(duration) as total_duration'),
            DB::raw('SUM(cost) as total_cost'),
            DB::raw('AVG(duration) as avg_duration')
        ])->first();

        return [
            'total_calls' => $stats->total_calls ?? 0,
            'unique_assistants' => $stats->unique_assistants ?? 0,
            'unique_users' => $stats->unique_users ?? 0,
            'unique_resellers' => $stats->unique_resellers ?? 0,
            'total_minutes' => round(($stats->total_duration ?? 0) / 60, 2),
            'total_cost' => round($stats->total_cost ?? 0, 2),
            'avg_duration' => round($stats->avg_duration ?? 0, 2),
        ];
    }

    /**
     * Get billing period usage for selected reseller or user
     */
    private function getBillingPeriodUsage(Request $request): ?array
    {
        $resellerId = $request->get('reseller_id');
        $userId = $request->get('user_id');

        // Prioritize user selection over reseller selection
        if ($userId) {
            return $this->getUserBillingPeriodUsage($userId);
        } elseif ($resellerId) {
            return $this->getResellerBillingPeriodUsage($resellerId);
        }

        return null;
    }

    /**
     * Get billing period usage for a specific reseller
     */
    private function getResellerBillingPeriodUsage(string $resellerId): array
    {
        $reseller = Reseller::with(['activeSubscription.package'])->find($resellerId);
        
        if (!$reseller) {
            return [
                'type' => 'reseller',
                'entity' => null,
                'billing_period' => null,
                'usage' => null,
                'error' => 'Reseller not found'
            ];
        }

        $resellerUsageService = new ResellerUsageService();
        $billingPeriod = $resellerUsageService->getCurrentBillingPeriod($reseller);
        $usage = $resellerUsageService->getCurrentBillingPeriodMinutesUsage($reseller);
        $overageCharges = $resellerUsageService->calculateOverageCharges($reseller);

        // Get additional usage statistics for the billing period
        $userIds = $reseller->users()->pluck('id')->toArray();
        $periodStats = $this->getPeriodStatistics($userIds, $billingPeriod['start'], $billingPeriod['end']);

        return [
            'type' => 'reseller',
            'entity' => [
                'id' => $reseller->id,
                'name' => $reseller->org_name,
                'email' => $reseller->adminUser->email ?? 'N/A',
                'subscription_status' => $reseller->activeSubscription ? 'active' : 'inactive',
                'package_name' => $reseller->activeSubscription?->package?->name ?? 'No Package'
            ],
            'billing_period' => [
                'start' => $billingPeriod['start']->format('Y-m-d'),
                'end' => $billingPeriod['end']->format('Y-m-d'),
                'is_fallback' => $billingPeriod['is_fallback'] ?? false,
                'interval_type' => $billingPeriod['interval_type'] ?? 'unknown',
                'subscription_day' => $billingPeriod['subscription_day'] ?? null
            ],
            'usage' => [
                'total_minutes' => $usage,
                'total_calls' => $periodStats['total_calls'],
                'total_cost' => $periodStats['total_cost'],
                'avg_duration' => $periodStats['avg_duration'],
                'unique_assistants' => $periodStats['unique_assistants'],
                'unique_users' => $periodStats['unique_users']
            ],
            'overage' => $overageCharges,
            'package_limits' => [
                'monthly_minutes_limit' => $reseller->activeSubscription?->package?->monthly_minutes_limit ?? 0,
                'extra_per_minute_charge' => $reseller->activeSubscription?->package?->extra_per_minute_charge ?? 0
            ]
        ];
    }

    /**
     * Get billing period usage for a specific user
     */
    private function getUserBillingPeriodUsage(string $userId): array
    {
        $user = User::with(['activeSubscription.package', 'reseller'])->find($userId);
        
        if (!$user) {
            return [
                'type' => 'user',
                'entity' => null,
                'billing_period' => null,
                'usage' => null,
                'error' => 'User not found'
            ];
        }

        $overageBillingService = new OverageBillingService();
        $billingPeriod = $overageBillingService->getCurrentBillingPeriod($user);
        $usage = $overageBillingService->getCurrentBillingPeriodMinutesUsage($user);
        $overageCharges = $overageBillingService->calculateOverageCharges($user);

        // Get additional usage statistics for the billing period
        $periodStats = $this->getPeriodStatistics([$user->id], $billingPeriod['start'], $billingPeriod['end']);

        return [
            'type' => 'user',
            'entity' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'reseller_name' => $user->reseller?->org_name ?? 'No Reseller',
                'subscription_status' => $user->activeSubscription ? 'active' : 'inactive',
                'package_name' => $user->activeSubscription?->package?->name ?? 'No Package'
            ],
            'billing_period' => [
                'start' => $billingPeriod['start']->format('Y-m-d'),
                'end' => $billingPeriod['end']->format('Y-m-d'),
                'is_fallback' => $billingPeriod['is_fallback'] ?? false,
                'interval_type' => $billingPeriod['interval_type'] ?? 'unknown',
                'subscription_day' => $billingPeriod['subscription_day'] ?? null
            ],
            'usage' => [
                'total_minutes' => $usage,
                'total_calls' => $periodStats['total_calls'],
                'total_cost' => $periodStats['total_cost'],
                'avg_duration' => $periodStats['avg_duration'],
                'unique_assistants' => $periodStats['unique_assistants'],
                'unique_users' => 1 // Single user
            ],
            'overage' => $overageCharges,
            'package_limits' => [
                'monthly_minutes_limit' => $user->activeSubscription?->package?->monthly_minutes_limit ?? 0,
                'extra_per_minute_charge' => $user->activeSubscription?->package?->extra_per_minute_charge ?? 0
            ]
        ];
    }

    /**
     * Get period statistics for given user IDs
     */
    private function getPeriodStatistics(array $userIds, Carbon $start, Carbon $end): array
    {
        $stats = CallLog::whereIn('user_id', $userIds)
            ->whereBetween('start_time', [$start, $end])
            ->select([
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(duration) as total_duration'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('AVG(duration) as avg_duration'),
                DB::raw('COUNT(DISTINCT assistant_id) as unique_assistants'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            ])
            ->first();

        return [
            'total_calls' => $stats->total_calls ?? 0,
            'total_duration' => $stats->total_duration ?? 0,
            'total_cost' => round($stats->total_cost ?? 0, 2),
            'avg_duration' => round($stats->avg_duration ?? 0, 2),
            'unique_assistants' => $stats->unique_assistants ?? 0,
            'unique_users' => $stats->unique_users ?? 0
        ];
    }

    /**
     * Export usage report to CSV
     */
    public function export(Request $request)
    {
        // This would implement CSV export functionality
        // For now, return a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'Export functionality will be implemented'
        ]);
    }
}
