<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\ResellerPackage;
use App\Models\ResellerSubscription;
use App\Models\ResellerTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResellerTransactionController extends Controller
{
    /**
     * Display a listing of reseller transactions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ResellerTransaction::with(['reseller', 'package', 'subscription']);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'LIKE', "%{$search}%")
                      ->orWhere('external_transaction_id', 'LIKE', "%{$search}%")
                      ->orWhere('billing_email', 'LIKE', "%{$search}%")
                      ->orWhereHas('reseller', function ($resellerQuery) use ($search) {
                          $resellerQuery->where('org_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Apply status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Apply type filter
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Apply reseller filter
            if ($request->has('reseller_id') && !empty($request->reseller_id)) {
                $query->where('reseller_id', $request->reseller_id);
            }

            // Apply date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller transactions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reseller transactions'
            ], 500);
        }
    }

    /**
     * Store a newly created reseller transaction
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reseller_id' => 'required|exists:resellers,id',
                'reseller_package_id' => 'required|exists:reseller_packages,id',
                'reseller_subscription_id' => 'nullable|exists:reseller_subscriptions,id',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'status' => 'required|string|in:pending,completed,failed,refunded,cancelled',
                'payment_method' => 'nullable|string|in:stripe,paypal,manual',
                'payment_method_id' => 'nullable|string',
                'payment_details' => 'nullable|array',
                'billing_email' => 'required|email',
                'billing_name' => 'nullable|string|max:255',
                'billing_address' => 'nullable|string|max:255',
                'billing_city' => 'nullable|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_country' => 'nullable|string|max:100',
                'billing_postal_code' => 'nullable|string|max:20',
                'type' => 'required|string|in:subscription,upgrade,renewal,refund,trial,overage',
                'description' => 'nullable|string',
                'metadata' => 'nullable|array',
                'external_transaction_id' => 'nullable|string',
            ]);

            $transaction = ResellerTransaction::create($validated);

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['reseller', 'package', 'subscription']),
                'message' => 'Reseller transaction created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating reseller transaction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating reseller transaction'
            ], 500);
        }
    }

    /**
     * Display the specified reseller transaction
     */
    public function show(ResellerTransaction $resellerTransaction): JsonResponse
    {
        $resellerTransaction->load(['reseller', 'package', 'subscription']);
        
        return response()->json([
            'success' => true,
            'data' => $resellerTransaction
        ]);
    }

    /**
     * Update the specified reseller transaction
     */
    public function update(Request $request, ResellerTransaction $resellerTransaction): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|required|string|in:pending,completed,failed,refunded,cancelled',
                'payment_method' => 'nullable|string|in:stripe,paypal,manual',
                'payment_method_id' => 'nullable|string',
                'payment_details' => 'nullable|array',
                'billing_email' => 'sometimes|required|email',
                'billing_name' => 'nullable|string|max:255',
                'billing_address' => 'nullable|string|max:255',
                'billing_city' => 'nullable|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_country' => 'nullable|string|max:100',
                'billing_postal_code' => 'nullable|string|max:20',
                'description' => 'nullable|string',
                'metadata' => 'nullable|array',
                'external_transaction_id' => 'nullable|string',
            ]);

            // Set processed_at if status is being changed to completed
            if (isset($validated['status']) && $validated['status'] === 'completed' && !$resellerTransaction->processed_at) {
                $validated['processed_at'] = now();
            }

            // Set failed_at if status is being changed to failed
            if (isset($validated['status']) && $validated['status'] === 'failed' && !$resellerTransaction->failed_at) {
                $validated['failed_at'] = now();
            }

            $resellerTransaction->update($validated);

            return response()->json([
                'success' => true,
                'data' => $resellerTransaction->load(['reseller', 'package', 'subscription']),
                'message' => 'Reseller transaction updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating reseller transaction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating reseller transaction'
            ], 500);
        }
    }

    /**
     * Mark transaction as completed
     */
    public function markCompleted(ResellerTransaction $resellerTransaction): JsonResponse
    {
        try {
            if ($resellerTransaction->status === ResellerTransaction::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is already completed'
                ], 400);
            }

            $resellerTransaction->update([
                'status' => ResellerTransaction::STATUS_COMPLETED,
                'processed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $resellerTransaction->load(['reseller', 'package', 'subscription']),
                'message' => 'Transaction marked as completed'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking transaction as completed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction status'
            ], 500);
        }
    }

    /**
     * Mark transaction as failed
     */
    public function markFailed(ResellerTransaction $resellerTransaction): JsonResponse
    {
        try {
            if ($resellerTransaction->status === ResellerTransaction::STATUS_FAILED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is already marked as failed'
                ], 400);
            }

            $resellerTransaction->update([
                'status' => ResellerTransaction::STATUS_FAILED,
                'failed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $resellerTransaction->load(['reseller', 'package', 'subscription']),
                'message' => 'Transaction marked as failed'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking transaction as failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction status'
            ], 500);
        }
    }

    /**
     * Process refund for transaction
     */
    public function processRefund(Request $request, ResellerTransaction $resellerTransaction): JsonResponse
    {
        try {
            if ($resellerTransaction->status !== ResellerTransaction::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed transactions can be refunded'
                ], 400);
            }

            $validated = $request->validate([
                'refund_amount' => 'nullable|numeric|min:0|max:' . $resellerTransaction->amount,
                'refund_reason' => 'nullable|string|max:500',
            ]);

            $refundAmount = $validated['refund_amount'] ?? $resellerTransaction->amount;
            $refundReason = $validated['refund_reason'] ?? 'Manual refund';

            // Create refund transaction
            $refundTransaction = ResellerTransaction::create([
                'reseller_id' => $resellerTransaction->reseller_id,
                'reseller_package_id' => $resellerTransaction->reseller_package_id,
                'reseller_subscription_id' => $resellerTransaction->reseller_subscription_id,
                'amount' => -$refundAmount, // Negative amount for refund
                'currency' => $resellerTransaction->currency,
                'status' => ResellerTransaction::STATUS_REFUNDED,
                'payment_method' => $resellerTransaction->payment_method,
                'billing_email' => $resellerTransaction->billing_email,
                'billing_name' => $resellerTransaction->billing_name,
                'type' => ResellerTransaction::TYPE_REFUND,
                'description' => "Refund for transaction {$resellerTransaction->transaction_id}: {$refundReason}",
                'metadata' => [
                    'original_transaction_id' => $resellerTransaction->transaction_id,
                    'refund_reason' => $refundReason,
                    'refund_amount' => $refundAmount
                ],
                'processed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $refundTransaction->load(['reseller', 'package', 'subscription']),
                'message' => 'Refund processed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing refund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing refund'
            ], 500);
        }
    }

    /**
     * Get financial summary for resellers
     */
    public function financialSummary(Request $request): JsonResponse
    {
        try {
            $query = ResellerTransaction::query();

            // Apply date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->get();

            $summary = [
                'total_revenue' => $transactions->where('status', ResellerTransaction::STATUS_COMPLETED)->sum('amount'),
                'total_transactions' => $transactions->count(),
                'completed_transactions' => $transactions->where('status', ResellerTransaction::STATUS_COMPLETED)->count(),
                'failed_transactions' => $transactions->where('status', ResellerTransaction::STATUS_FAILED)->count(),
                'refunded_amount' => abs($transactions->where('type', ResellerTransaction::TYPE_REFUND)->sum('amount')),
                'pending_amount' => $transactions->where('status', ResellerTransaction::STATUS_PENDING)->sum('amount'),
                'by_type' => $transactions->groupBy('type')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_amount' => $group->sum('amount')
                    ];
                }),
                'by_status' => $transactions->groupBy('status')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_amount' => $group->sum('amount')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating financial summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating financial summary'
            ], 500);
        }
    }
}
