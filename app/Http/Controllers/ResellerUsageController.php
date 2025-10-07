<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ResellerUsageTracker;
use App\Services\ResellerBillingService;
use App\Models\ResellerUsagePeriod;
use App\Models\ResellerTransaction;
use Carbon\Carbon;

class ResellerUsageController extends Controller
{
    private ResellerUsageTracker $usageTracker;
    private ResellerBillingService $billingService;

    public function __construct()
    {
        $this->usageTracker = new ResellerUsageTracker();
        $this->billingService = new ResellerBillingService();
    }

    public function currentUsage(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->reseller_id) {
                return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
            }
            $summary = $this->usageTracker->getCurrentUsageSummary($user->reseller_id);
            return response()->json(['success' => true, 'data' => $summary]);
        } catch (\Exception $e) {
            Log::error('Error fetching current usage', ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error fetching usage data'], 500);
        }
    }

    public function usageHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->reseller_id) {
                return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
            }
            $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : null;
            $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : null;
            $limit = $request->input('limit', 12);
            $query = ResellerUsagePeriod::where('reseller_id', $user->reseller_id)->with(['package', 'subscription'])->orderBy('period_start', 'desc');
            if ($startDate) $query->where('period_start', '>=', $startDate);
            if ($endDate) $query->where('period_end', '<=', $endDate);
            $periods = $query->limit($limit)->get();
            $chartData = $periods->map(function ($period) {
                return [
                    'period' => $period->period_start->format('M Y'),
                    'period_start' => $period->period_start->format('Y-m-d'),
                    'period_end' => $period->period_end->format('Y-m-d'),
                    'calls' => $period->total_calls,
                    'duration_minutes' => round($period->total_duration_seconds / 60, 2),
                    'cost' => (float) $period->total_cost,
                    'overage' => (float) $period->overage_cost,
                    'overage_status' => $period->overage_status,
                    'is_current' => $period->isCurrent(),
                ];
            });
            $totals = [
                'total_calls' => $periods->sum('total_calls'),
                'total_duration_minutes' => round($periods->sum('total_duration_seconds') / 60, 2),
                'total_cost' => $periods->sum('total_cost'),
                'total_overage' => $periods->sum('overage_cost'),
            ];
            return response()->json(['success' => true, 'data' => ['periods' => $chartData, 'totals' => $totals, 'count' => $periods->count()]]);
        } catch (\Exception $e) {
            Log::error('Error fetching usage history', ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error fetching usage history'], 500);
        }
    }

    public function overages(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->reseller_id) {
                return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
            }
            $status = $request->input('status');
            $limit = $request->input('limit', 20);
            $query = ResellerTransaction::where('reseller_id', $user->reseller_id)->where('type', ResellerTransaction::TYPE_OVERAGE)->with(['usagePeriod', 'package'])->orderBy('created_at', 'desc');
            if ($status) $query->where('status', $status);
            $transactions = $query->limit($limit)->get();
            $data = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (float) $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                    'processed_at' => $transaction->processed_at,
                    'overage_details' => $transaction->overage_details,
                ];
            });
            $summary = [
                'total_amount' => $transactions->where('status', ResellerTransaction::STATUS_COMPLETED)->sum('amount'),
                'pending_amount' => $transactions->where('status', ResellerTransaction::STATUS_PENDING)->sum('amount'),
                'failed_amount' => $transactions->where('status', ResellerTransaction::STATUS_FAILED)->sum('amount'),
            ];
            return response()->json(['success' => true, 'data' => ['transactions' => $data, 'summary' => $summary]]);
        } catch (\Exception $e) {
            Log::error('Error fetching overages', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error fetching overage transactions'], 500);
        }
    }

    public function alerts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->reseller_id) {
                return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
            }
            $currentUsage = $this->usageTracker->getCurrentUsageSummary($user->reseller_id);
            if (!$currentUsage['has_active_period']) {
                return response()->json(['success' => true, 'data' => ['has_alerts' => false, 'alerts' => []]]);
            }
            $alerts = [];
            $thresholds = config('reseller-billing.usage_alert_thresholds', [75, 90, 100]);
            $usagePercentage = $currentUsage['usage_percentage'];
            foreach ($thresholds as $threshold) {
                if ($usagePercentage >= $threshold) {
                    $alerts[] = [
                        'type' => 'usage_limit',
                        'severity' => $threshold >= 100 ? 'critical' : ($threshold >= 90 ? 'warning' : 'info'),
                        'message' => "You have used {$usagePercentage}% of your monthly limit",
                        'threshold' => $threshold,
                    ];
                    break;
                }
            }
            if ($currentUsage['total_overage'] > 0) {
                $alerts[] = [
                    'type' => 'overage',
                    'severity' => 'warning',
                    'message' => "Pending overage: \$" . $currentUsage['total_overage'],
                    'overage_amount' => $currentUsage['total_overage'],
                ];
            }
            return response()->json(['success' => true, 'data' => ['has_alerts' => count($alerts) > 0, 'alerts' => $alerts, 'usage_summary' => $currentUsage]]);
        } catch (\Exception $e) {
            Log::error('Error fetching alerts', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error fetching alerts'], 500);
        }
    }
}
