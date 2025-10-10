<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\OverageBillingService;
use App\Services\ResellerUsageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Get all available subscription packages
     */
    public function getPackages(): JsonResponse
    {
        $packages = SubscriptionPackage::active()
            ->contentProtection()
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    /**
     * Get user's current subscription
     */
    public function getCurrentSubscription(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            // Check if there are any subscriptions for this user
            $anySubscription = $user->subscriptions()
                ->contentProtection()
                ->latest()
                ->first();
            
            if ($anySubscription) {
                return response()->json([
                    'success' => true,
                    'data' => $anySubscription->load('package'),
                    'message' => 'Latest subscription found (may not be active)'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No subscription found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription->load('package')
        ]);
    }

    /**
     * Get a specific subscription by ID
     */
    public function getSubscription($id): JsonResponse
    {
        $subscription = UserSubscription::contentProtection()
            ->where('id', $id)
            ->with('package')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $subscription->id,
                'package_name' => $subscription->package->name,
                'amount' => $subscription->custom_amount ?? $subscription->package->price,
                'status' => $subscription->status,
                'valid_until' => $subscription->current_period_end,
                'created_at' => $subscription->created_at,
            ]
        ]);
    }

    /**
     * Subscribe user to a package
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id'
        ]);

        $user = Auth::user();
        $package = SubscriptionPackage::contentProtection()
            ->findOrFail($request->package_id);

        // Validate package ownership (package must belong to user's reseller)
        if ($package->reseller_id !== $user->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not available for your reseller'
            ], 403);
        }

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription'
            ], 400);
        }

        // Create subscription (no trial period)
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'reseller_id' => $user->reseller_id,
            'subscription_package_id' => $package->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $subscription->load('package'),
            'message' => 'Subscription created successfully.'
        ], 201);
    }

    /**
     * Cancel user's subscription
     */
    public function cancel(): JsonResponse
    {
        $subscription = UserSubscription::contentProtection()
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'ends_at' => $subscription->current_period_end,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    /**
     * Upgrade user's subscription
     */
    public function upgrade(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id'
        ]);

        $user = Auth::user();
        $newPackage = SubscriptionPackage::contentProtection()
            ->findOrFail($request->package_id);

        // Validate package ownership (package must belong to user's reseller)
        if ($newPackage->reseller_id !== $user->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not available for your reseller'
            ], 403);
        }

        $currentSubscription = UserSubscription::contentProtection()
            ->where('status', 'active')
            ->first();

        if (!$currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $currentPackage = $currentSubscription->package;

        // Check if it's actually an upgrade
        if ($newPackage->price <= $currentPackage->price) {
            return response()->json([
                'success' => false,
                'message' => 'You can only upgrade to a higher tier'
            ], 400);
        }

        // Update subscription to new package
        $currentSubscription->update([
            'subscription_package_id' => $newPackage->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $currentSubscription->load('package'),
            'message' => 'Subscription upgraded successfully'
        ]);
    }

    /**
     * Get subscription usage statistics
     */
    public function getUsage(): JsonResponse
    {
        $user = Auth::user();
        
        // Handle reseller admin users
        if ($user->role === 'reseller_admin' && $user->reseller_id) {
            $reseller = $user->reseller;
            
            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found'
                ], 404);
            }
            
            $resellerUsageService = new ResellerUsageService();
            $usage = $resellerUsageService->getUsageStatistics($reseller);
            
            if (!$usage['has_active_subscription']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $usage
            ]);
        }
        
        // Handle regular users
        $subscription = UserSubscription::contentProtection()
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $overageBillingService = new OverageBillingService();
        $usage = $overageBillingService->getUsageStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $usage
        ]);
    }

    /**
     * Admin: Get all subscriptions with filtering
     */
    public function adminGetSubscriptions(Request $request): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = UserSubscription::contentProtection()->with(['user', 'package']);

        // Apply filters - handle null and 'null' values
        if ($request->filled('status') && $request->status !== 'null' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->filled('package_id') && $request->package_id !== 'null' && $request->package_id !== null) {
            $query->where('subscription_package_id', $request->package_id);
        }

        if ($request->filled('search') && $request->search !== 'null' && $request->search !== null) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_range') && $request->date_range !== 'null' && $request->date_range !== null) {
            $dateRange = $request->date_range;
            $now = Carbon::now();
            
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', $now->toDateString());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [$now->startOfWeek()->toDateTimeString(), $now->endOfWeek()->toDateTimeString()]);
                    break;
                case 'month':
                    $query->whereBetween('created_at', [$now->startOfMonth()->toDateTimeString(), $now->endOfMonth()->toDateTimeString()]);
                    break;
                case 'quarter':
                    $query->whereBetween('created_at', [$now->startOfQuarter()->toDateTimeString(), $now->endOfQuarter()->toDateTimeString()]);
                    break;
                case 'year':
                    $query->whereBetween('created_at', [$now->startOfYear()->toDateTimeString(), $now->endOfYear()->toDateTimeString()]);
                    break;
            }
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $subscriptions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Create a clone of the query for summary calculations
        $summaryQuery = clone $query;
        $summaryQuery->getQuery()->orders = null; // Remove ordering for summary
        $summaryQuery->getQuery()->limit = null; // Remove limit for summary
        $summaryQuery->getQuery()->offset = null; // Remove offset for summary

        // Get statistics based on filtered query
        $stats = [
            'total' => $summaryQuery->count(),
            'active' => $summaryQuery->where('status', 'active')->count(),
            'pending' => $summaryQuery->where('status', 'pending')->count(),
            'cancelled' => $summaryQuery->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
            'stats' => $stats,
            'debug' => [
                'filters_applied' => [
                    'status' => $request->get('status'),
                    'package_id' => $request->get('package_id'),
                    'search' => $request->get('search'),
                    'date_range' => $request->get('date_range')
                ]
            ]
        ]);
    }

    /**
     * Admin: Get all packages
     */
    public function adminGetPackages(): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $packages = SubscriptionPackage::contentProtection()->orderBy('price')->get();

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    /**
     * Admin: Create a new package
     */
    public function adminCreatePackage(Request $request): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'voice_agents_limit' => 'required|integer',
            'monthly_minutes_limit' => 'required|integer',
            'extra_per_minute_charge' => 'required|numeric|min:0|max:999.9999',
            'features' => 'nullable|string',
            'support_level' => 'required|string|in:email,priority,dedicated',
            'analytics_level' => 'required|string|in:basic,advanced,custom',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();
        $packageData = $request->all();
        $packageData['reseller_id'] = $user->reseller_id;
        
        $package = SubscriptionPackage::create($packageData);

        return response()->json([
            'success' => true,
            'data' => $package,
            'message' => 'Package created successfully'
        ], 201);
    }

    /**
     * Admin: Update a package
     */
    public function adminUpdatePackage(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'voice_agents_limit' => 'required|integer',
            'monthly_minutes_limit' => 'required|integer',
            'extra_per_minute_charge' => 'required|numeric|min:0|max:999.9999',
            'features' => 'nullable|string',
            'support_level' => 'required|string|in:email,priority,dedicated',
            'analytics_level' => 'required|string|in:basic,advanced,custom',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $package = SubscriptionPackage::contentProtection()->findOrFail($id);
        $package->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $package,
            'message' => 'Package updated successfully'
        ]);
    }

    /**
     * Admin: Get usage overview for all users
     */
    public function adminUsageOverview(Request $request): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $overageBillingService = new OverageBillingService();
        
        // Get all users with active subscriptions
        $users = User::whereHas('activeSubscription')
            ->where('reseller_id', Auth::user()->reseller_id)
            ->with(['activeSubscription.package'])
            ->get();

        $usageData = [];
        $totalOverageMinutes = 0;
        $totalOverageCost = 0;
        $usersWithOverage = 0;

        foreach ($users as $user) {
            $statistics = $overageBillingService->getUsageStatistics($user, false);
            
            if (!$statistics['has_active_subscription']) {
                continue;
            }

            $userOverage = [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'package_name' => $statistics['package']['name'],
                'minutes_used' => $statistics['minutes']['used'],
                'minutes_limit' => $statistics['minutes']['limit'],
                'overage_minutes' => $statistics['minutes']['overage_minutes'],
                'overage_cost' => $statistics['minutes']['overage_cost'],
                'extra_per_minute_charge' => $statistics['minutes']['extra_per_minute_charge'],
                'is_unlimited' => $statistics['minutes']['is_unlimited'],
                'assistants_used' => $statistics['assistants']['used'],
                'assistants_limit' => $statistics['assistants']['limit'],
                'subscription_status' => $statistics['subscription']['status'],
                'days_remaining' => $statistics['subscription']['days_remaining'],
                'billing_period_start' => $statistics['minutes']['billing_period_start'] ?? null,
                'billing_period_end' => $statistics['minutes']['billing_period_end'] ?? null,
                'is_calendar_month_fallback' => $statistics['minutes']['is_calendar_month_fallback'] ?? false,
                'interval_type' => $statistics['minutes']['interval_type'] ?? 'unknown',
                'subscription_day' => $statistics['minutes']['subscription_day'] ?? null
            ];

            $usageData[] = $userOverage;

            if ($userOverage['overage_minutes'] > 0) {
                $totalOverageMinutes += $userOverage['overage_minutes'];
                $totalOverageCost += $userOverage['overage_cost'];
                $usersWithOverage++;
            }
        }

        // Sort by overage cost descending
        usort($usageData, function($a, $b) {
            return $b['overage_cost'] <=> $a['overage_cost'];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $usageData,
                'summary' => [
                    'total_users' => count($usageData),
                    'users_with_overage' => $usersWithOverage,
                    'total_overage_minutes' => round($totalOverageMinutes, 2),
                    'total_overage_cost' => round($totalOverageCost, 2),
                    'average_overage_per_user' => $usersWithOverage > 0 ? round($totalOverageCost / $usersWithOverage, 2) : 0
                ]
            ]
        ]);
    }

    /**
     * Admin: Delete a package
     */
    public function adminDeletePackage($id): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $package = SubscriptionPackage::contentProtection()->findOrFail($id);
        
        // Check if package has active subscriptions
        $activeSubscriptions = UserSubscription::contentProtection()
            ->where('subscription_package_id', $id)
            ->whereIn('status', ['active', 'trial', 'pending'])
            ->count();

        if ($activeSubscriptions > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete package with active subscriptions'
            ], 400);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully'
        ]);
    }

    /**
     * Admin: Delete a subscription
     */
    public function adminDeleteSubscription($id): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $subscription = UserSubscription::contentProtection()->findOrFail($id);

        // Check if subscription is active
        if ($subscription->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active subscription. Please cancel it first.'
            ], 422);
        }

        // Log the deletion
        Log::info('Subscription deleted by admin', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'status' => $subscription->status,
            'admin_id' => Auth::id()
        ]);

        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription deleted successfully'
        ]);
    }

    /**
     * Admin: Initialize default packages
     */
    public function adminInitializeDefaultPackages(): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            // Check if packages already exist
            $existingPackages = SubscriptionPackage::contentProtection()->count();
            
            if ($existingPackages > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Packages already exist. Cannot initialize default packages.'
                ], 400);
            }

            // Default packages data
            $defaultPackages = [
                [
                    'name' => 'Starter',
                    'description' => 'Perfect for small businesses getting started with AI voice agents.',
                    'price' => 29.00,
                    'yearly_price' => 290.00, // 10 months worth (2 months free)
                    'voice_agents_limit' => 1,
                    'monthly_minutes_limit' => 100,
                    'extra_per_minute_charge' => 0.60,
                    'features' => '1 Voice Agent, 100 minutes/month, Basic Analytics, Email Support',
                    'support_level' => 'email',
                    'analytics_level' => 'basic',
                    'is_popular' => false,
                    'is_active' => true,
                    'reseller_id' => Auth::user()->reseller_id,
                ],
                [
                    'name' => 'Professional',
                    'description' => 'Ideal for growing businesses with multiple voice agents and higher usage.',
                    'price' => 99.00,
                    'yearly_price' => 990.00, // 10 months worth (2 months free)
                    'voice_agents_limit' => 5,
                    'monthly_minutes_limit' => 300,
                    'extra_per_minute_charge' => 0.50,
                    'features' => '5 Voice Agents, 1,000 minutes/month, Advanced Analytics, Priority Support, Custom Integrations',
                    'support_level' => 'priority',
                    'analytics_level' => 'advanced',
                    'is_popular' => true,
                    'is_active' => true,
                    'reseller_id' => Auth::user()->reseller_id,
                ],
                [
                    'name' => 'Enterprise',
                    'description' => 'Complete solution for large organizations with unlimited usage.',
                    'price' => 299.00,
                    'yearly_price' => 2990.00, // 10 months worth (2 months free)
                    'voice_agents_limit' => 10, // Unlimited
                    'monthly_minutes_limit' => 1000, // Unlimited
                    'extra_per_minute_charge' => 0.40,
                    'features' => 'Unlimited Voice Agents, Unlimited Minutes, Custom Analytics, Dedicated Support, API Access, Custom Development',
                    'support_level' => 'dedicated',
                    'analytics_level' => 'custom',
                    'is_popular' => false,
                    'is_active' => true,
                    'reseller_id' => Auth::user()->reseller_id,
                ]
            ];

            // Create packages
            $createdPackages = [];
            foreach ($defaultPackages as $packageData) {
                $package = SubscriptionPackage::create($packageData);
                $createdPackages[] = $package;
            }

            return response()->json([
                'success' => true,
                'message' => 'Default packages initialized successfully',
                'data' => $createdPackages
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error initializing default packages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error initializing default packages'
            ], 500);
        }
    }
}
