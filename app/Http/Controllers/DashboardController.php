<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CallLog;
use App\Models\Assistant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        
        // Get basic counts using content protection
        $totalAssistants = Assistant::contentProtection()->count();
        $totalCalls = CallLog::contentProtection()->count();
        
        // Get today's data
        $today = Carbon::today();
        $callsToday = CallLog::contentProtection()
            ->whereDate('start_time', $today)
            ->count();
        
        // Get this month's data
        $thisMonth = Carbon::now()->startOfMonth();
        $callsThisMonth = CallLog::contentProtection()
            ->where('start_time', '>=', $thisMonth)
            ->count();
        
        // Calculate total minutes
        $totalMinutes = CallLog::contentProtection()
            ->whereNotNull('duration')
            ->sum('duration') / 60; // Convert seconds to minutes
        
        $thisMonthMinutes = CallLog::contentProtection()
            ->where('start_time', '>=', $thisMonth)
            ->whereNotNull('duration')
            ->sum('duration') / 60; // Convert seconds to minutes
        
        // Calculate average call duration
        $avgDuration = CallLog::contentProtection()
            ->whereNotNull('duration')
            ->avg('duration');
        
        // Get recent calls (last 7 days)
        $recentCalls = CallLog::contentProtection()
            ->where('start_time', '>=', Carbon::now()->subDays(7))
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();
        
        // Get call trends (last 30 days)
        $callTrends = CallLog::contentProtection()
            ->where('start_time', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(start_time) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get assistant performance
        $assistantStats = Assistant::contentProtection()
            ->withCount(['callLogs as total_calls'])
            ->get()
            ->map(function($assistant) {
                return [
                    'id' => $assistant->id,
                    'name' => $assistant->name,
                    'phone_number' => $assistant->phone_number,
                    'total_calls' => $assistant->total_calls,
                    'type' => $assistant->type
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_assistants' => $totalAssistants,
                    'total_calls' => $totalCalls,
                    'calls_today' => $callsToday,
                    'calls_this_month' => $callsThisMonth,
                    'total_minutes' => round($totalMinutes, 1),
                    'this_month_minutes' => round($thisMonthMinutes, 1),
                    'avg_duration' => round($avgDuration ?? 0, 1)
                ],
                'recent_calls' => $recentCalls->map(function($call) {
                    return [
                        'id' => $call->id,
                        'call_id' => $call->call_id,
                        'phone_number' => $call->phone_number,
                        'duration' => $call->duration,
                        'status' => $call->status,
                        'start_time' => $call->start_time,
                        'cost' => $call->cost,
                        'assistant_name' => $call->assistant->name ?? 'Unknown'
                    ];
                }),
                'call_trends' => $callTrends,
                'assistant_performance' => $assistantStats
            ]
        ]);
    }
    
    public function activity(Request $request)
    {
        $user = $request->user();
        
        // Get recent activity from multiple sources
        $activities = collect();
        
        // Recent calls
        $recentCalls = CallLog::contentProtection()
            ->with('assistant')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get()
            ->map(function($call) {
                return [
                    'id' => 'call_' . $call->id,
                    'type' => 'call',
                    'title' => 'Call to ' . $call->phone_number,
                    'description' => "Call with {$call->assistant->name}",
                    'time' => $call->start_time,
                    'status' => $call->status,
                    'duration' => $call->duration,
                    'cost' => $call->cost
                ];
            });
        
        $activities = $activities->concat($recentCalls);
        
        // Recent transactions
        $recentTransactions = Transaction::contentProtection()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => 'transaction_' . $transaction->id,
                    'type' => 'transaction',
                    'title' => ucfirst($transaction->type) . ' Transaction',
                    'description' => "{$transaction->status} transaction for {$transaction->amount}",
                    'time' => $transaction->created_at,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount
                ];
            });
        
        $activities = $activities->concat($recentTransactions);
        
        // Recent assistant creations
        $recentAssistants = Assistant::contentProtection()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($assistant) {
                return [
                    'id' => 'assistant_' . $assistant->id,
                    'type' => 'assistant',
                    'title' => 'Assistant Created',
                    'description' => "Created assistant: {$assistant->name}",
                    'time' => $assistant->created_at,
                    'assistant_name' => $assistant->name
                ];
            });
        
        $activities = $activities->concat($recentAssistants);
        
        // Sort all activities by time and take the most recent 15
        $sortedActivities = $activities->sortByDesc('time')->take(15)->values();
        
        return response()->json([
            'success' => true,
            'data' => $sortedActivities
        ]);
    }
    
    public function adminStats(Request $request)
    {
        // Get reseller-scoped statistics using content protection
        $totalUsers = User::where('reseller_id', $request->user()->reseller_id)->count();
        $activeUsers = User::where('reseller_id', $request->user()->reseller_id)->where('status', 'active')->count();
        $totalAssistants = Assistant::contentProtection()->count();
        $totalCalls = CallLog::contentProtection()->count();
        
        // Get today's data
        $today = Carbon::today();
        $callsToday = CallLog::contentProtection()->whereDate('start_time', $today)->count();
        $newUsersToday = User::where('reseller_id', $request->user()->reseller_id)->whereDate('created_at', $today)->count();
        $newAssistantsToday = Assistant::contentProtection()->whereDate('created_at', $today)->count();
        
        // Get this month's data
        $thisMonth = Carbon::now()->startOfMonth();
        $callsThisMonth = CallLog::contentProtection()->where('start_time', '>=', $thisMonth)->count();
        $newUsersThisMonth = User::where('reseller_id', $request->user()->reseller_id)->where('created_at', '>=', $thisMonth)->count();
        $revenueThisMonth = Transaction::contentProtection()->where('status', 'completed')
            ->where('created_at', '>=', $thisMonth)
            ->sum('amount');
        
        // Calculate total minutes
        $totalMinutes = CallLog::contentProtection()->whereNotNull('duration')->sum('duration') / 60; // Convert seconds to minutes
        $thisMonthMinutes = CallLog::contentProtection()->where('start_time', '>=', $thisMonth)
            ->whereNotNull('duration')
            ->sum('duration') / 60; // Convert seconds to minutes
        
        // Calculate average call duration
        $avgDuration = CallLog::contentProtection()->whereNotNull('duration')->avg('duration');
        
        // Get total revenue
        $totalRevenue = Transaction::contentProtection()->where('status', 'completed')->sum('amount');
        
        // Get subscription statistics
        $activeSubscriptions = UserSubscription::contentProtection()->where('status', 'active')->count();
        $trialSubscriptions = UserSubscription::contentProtection()->where('status', 'trial')->count();
        $cancelledSubscriptions = UserSubscription::contentProtection()->where('status', 'cancelled')->count();
        
        // Get top 5 performing assistants based on call count
        $topAssistants = Assistant::contentProtection()->withCount('callLogs')
            ->orderBy('call_logs_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function($assistant) {
                return [
                    'id' => $assistant->id,
                    'name' => $assistant->name,
                    'phone_number' => $assistant->phone_number,
                    'total_calls' => $assistant->call_logs_count,
                    'type' => $assistant->type,
                    'user_name' => $assistant->user->name ?? 'Unknown'
                ];
            });
        
        // Get recent system activity
        $recentCalls = CallLog::contentProtection()->with(['user', 'assistant'])
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();
        
        $recentUsers = User::where('reseller_id', $request->user()->reseller_id)->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $recentTransactions = Transaction::contentProtection()->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get call trends (last 30 days)
        $callTrends = CallLog::contentProtection()->where('start_time', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(start_time) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get user growth trends (last 30 days)
        $userGrowthTrends = User::where('reseller_id', $request->user()->reseller_id)->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'total_assistants' => $totalAssistants,
                    'total_calls' => $totalCalls,
                    'calls_today' => $callsToday,
                    'calls_this_month' => $callsThisMonth,
                    'new_users_today' => $newUsersToday,
                    'new_users_this_month' => $newUsersThisMonth,
                    'new_assistants_today' => $newAssistantsToday,
                    'total_minutes' => round($totalMinutes, 1),
                    'this_month_minutes' => round($thisMonthMinutes, 1),
                    'avg_duration' => round($avgDuration ?? 0, 1),
                    'total_revenue' => round($totalRevenue, 2),
                    'revenue_this_month' => round($revenueThisMonth, 2),
                    'active_subscriptions' => $activeSubscriptions,
                    'trial_subscriptions' => $trialSubscriptions,
                    'cancelled_subscriptions' => $cancelledSubscriptions
                ],
                'top_assistants' => $topAssistants,
                'recent_calls' => $recentCalls->map(function($call) {
                    return [
                        'id' => $call->id,
                        'call_id' => $call->call_id,
                        'phone_number' => $call->phone_number,
                        'duration' => $call->duration,
                        'status' => $call->status,
                        'start_time' => $call->start_time,
                        'cost' => $call->cost,
                        'user_name' => $call->user->name ?? 'Unknown',
                        'assistant_name' => $call->assistant->name ?? 'Unknown'
                    ];
                }),
                'recent_users' => $recentUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'status' => $user->status,
                        'created_at' => $user->created_at
                    ];
                }),
                'recent_transactions' => $recentTransactions->map(function($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'status' => $transaction->status,
                        'type' => $transaction->type,
                        'user_name' => $transaction->user->name ?? 'Unknown',
                        'created_at' => $transaction->created_at
                    ];
                }),
                'call_trends' => $callTrends,
                'user_growth_trends' => $userGrowthTrends
            ]
        ]);
    }
    
    public function adminActivity(Request $request)
    {
        // Get recent system activity from multiple sources
        $activities = collect();
        
        // Recent calls
        $recentCalls = CallLog::contentProtection()->with(['user', 'assistant'])
            ->orderBy('start_time', 'desc')
            ->limit(15)
            ->get()
            ->map(function($call) {
                return [
                    'id' => 'call_' . $call->id,
                    'type' => 'call',
                    'title' => 'Call to ' . $call->phone_number,
                    'description' => "Call by {$call->user->name} with {$call->assistant->name}",
                    'time' => $call->start_time,
                    'status' => $call->status,
                    'duration' => $call->duration,
                    'cost' => $call->cost
                ];
            });
        
        $activities = $activities->concat($recentCalls);
        
        // Recent transactions
        $recentTransactions = Transaction::contentProtection()->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => 'transaction_' . $transaction->id,
                    'type' => 'transaction',
                    'title' => ucfirst($transaction->type) . ' Transaction',
                    'description' => "{$transaction->status} transaction for {$transaction->amount} by {$transaction->user->name}",
                    'time' => $transaction->created_at,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount
                ];
            });
        
        $activities = $activities->concat($recentTransactions);
        
        // Recent user registrations
        $recentUsers = User::where('reseller_id', $request->user()->reseller_id)->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => 'user_' . $user->id,
                    'type' => 'user',
                    'title' => 'New User Registration',
                    'description' => "New user registered: {$user->name} ({$user->email})",
                    'time' => $user->created_at,
                    'user_name' => $user->name,
                    'email' => $user->email
                ];
            });
        
        $activities = $activities->concat($recentUsers);
        
        // Recent assistant creations
        $recentAssistants = Assistant::contentProtection()->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($assistant) {
                return [
                    'id' => 'assistant_' . $assistant->id,
                    'type' => 'assistant',
                    'title' => 'Assistant Created',
                    'description' => "Assistant '{$assistant->name}' created by {$assistant->user->name}",
                    'time' => $assistant->created_at,
                    'assistant_name' => $assistant->name,
                    'user_name' => $assistant->user->name
                ];
            });
        
        $activities = $activities->concat($recentAssistants);
        
        // Sort all activities by time and take the most recent 20
        $sortedActivities = $activities->sortByDesc('time')->take(20)->values();
        
        return response()->json([
            'success' => true,
            'data' => $sortedActivities
        ]);
    }
}
