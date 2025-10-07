<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\UserSubscription;
use App\Models\SubscriptionPackage;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransactionController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get user's transactions
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Debug: Log the user and check all transactions
        Log::info('Transaction index called', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'total_transactions_in_db' => Transaction::count(),
            'transactions_for_user' => Transaction::where('user_id', $user->id)->count(),
            'all_transaction_user_ids' => Transaction::pluck('user_id')->toArray(),
            'request_filters' => $request->all()
        ]);
        
        // Build query using content protection scope
        $query = Transaction::contentProtection()
            ->with(['package', 'subscription', 'user'])
            ->orderBy('created_at', 'desc');
        
        // If admin specifies a user_id, filter by that user - handle null and 'null' values
        if ($user->isContentAdmin() && $request->filled('user_id') && $request->user_id !== 'null' && $request->user_id !== null) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status - handle null and 'null' values
        if ($request->filled('status') && $request->status !== 'null' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Filter by payment method - handle null and 'null' values
        if ($request->filled('payment_method') && $request->payment_method !== 'null' && $request->payment_method !== null) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by type - handle null and 'null' values
        if ($request->filled('type') && $request->type !== 'null' && $request->type !== null) {
            $query->where('type', $request->type);
        }

        // Filter by date range - handle null and 'null' values
        if ($request->filled('start_date') && $request->start_date !== 'null' && $request->start_date !== null) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date') && $request->end_date !== 'null' && $request->end_date !== null) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->paginate(20);

        // Calculate summary statistics using content protection scope
        $summaryQuery = Transaction::contentProtection();
        if ($user->isContentAdmin() && $request->filled('user_id') && $request->user_id !== 'null' && $request->user_id !== null) {
            $summaryQuery->where('user_id', $request->user_id);
        }
        $summary = [
            'total_transactions' => $summaryQuery->count(),
            'total_amount' => $summaryQuery->where('status', Transaction::STATUS_COMPLETED)->sum('amount'),
            'pending_transactions' => $summaryQuery->where('status', Transaction::STATUS_PENDING)->count(),
            'failed_transactions' => $summaryQuery->where('status', Transaction::STATUS_FAILED)->count(),
            'completed_transactions' => $summaryQuery->where('status', Transaction::STATUS_COMPLETED)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'summary' => $summary,
            'debug' => [
                'user_id' => $user->id,
                'is_admin' => $user->isContentAdmin(),
                'total_in_db' => Transaction::count(),
                'for_user' => Transaction::where('user_id', $user->id)->count(),
                'requested_user_id' => $request->get('user_id'),
                'filters_applied' => [
                    'status' => $request->get('status'),
                    'payment_method' => $request->get('payment_method'),
                    'type' => $request->get('type'),
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date')
                ]
            ]
        ]);
    }

    /**
     * Get specific transaction
     */
    public function show(Request $request, $transactionId): JsonResponse
    {
        $transaction = Transaction::contentProtection()
            ->where('transaction_id', $transactionId)
            ->with(['package', 'subscription'])
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Create a new transaction
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:subscription_packages,id',
            'payment_method' => 'required|in:stripe,paypal,manual',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'type' => 'required|in:subscription,upgrade,renewal,refund,trial',
            'billing_email' => 'required|email',
            'billing_name' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string|max:500',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'external_transaction_id' => 'nullable|string|max:255',
            'payment_details' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $package = SubscriptionPackage::contentProtection()
            ->findOrFail($request->package_id);

        // Validate package ownership (package must belong to user's reseller)
        if ($package->reseller_id !== $user->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not available for your reseller'
            ], 403);
        }
        
        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'reseller_id' => $user->reseller_id,
            'subscription_package_id' => $package->id,
            'user_subscription_id' => $request->user_subscription_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => $request->payment_method,
            'payment_method_id' => $request->payment_method_id,
            'payment_details' => $request->payment_details,
            'billing_email' => $request->billing_email,
            'billing_name' => $request->billing_name,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_state' => $request->billing_state,
            'billing_country' => $request->billing_country,
            'billing_postal_code' => $request->billing_postal_code,
            'type' => $request->type,
            'description' => $request->description,
            'external_transaction_id' => $request->external_transaction_id,
            'metadata' => $request->metadata,
        ]);

        // If this is a subscription transaction, create a pending subscription
        if ($request->type === Transaction::TYPE_SUBSCRIPTION) {
            $this->createPendingSubscription($user, $transaction);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['package', 'subscription']),
            'message' => 'Transaction created successfully'
        ], 201);
    }

    /**
     * Update transaction status
     */
    public function updateStatus(Request $request, $transactionId): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded,cancelled'
        ]);

        $transaction = Transaction::contentProtection()
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $transaction->update([
            'status' => $request->status,
            'processed_at' => $request->status === Transaction::STATUS_COMPLETED ? Carbon::now() : null,
            'failed_at' => $request->status === Transaction::STATUS_FAILED ? Carbon::now() : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['package', 'subscription']),
            'message' => 'Transaction status updated successfully'
        ]);
    }

    /**
     * Process payment with Stripe integration
     */
    public function processPayment(Request $request, $transactionId): JsonResponse
    {
        $user = Auth::user();
        
        $transaction = Transaction::contentProtection()
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        if ($transaction->status !== Transaction::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not pending'
            ], 400);
        }

        try {
            if ($transaction->payment_method === Transaction::PAYMENT_STRIPE) {
                // Process with Stripe
                return $this->processStripePayment($transaction, $user);
            } else {
                // For other payment methods, simulate processing
                return $this->processMockPayment($transaction, $user);
            }
        } catch (\Exception $e) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Process payment with Stripe
     */
    private function processStripePayment(Transaction $transaction, $user): JsonResponse
    {
        $package = $transaction->package;

        // Create Stripe subscription
        $stripeResult = $this->stripeService->createSubscription($user, $package);
        Log::info('Stripe subscription result: ' . json_encode($stripeResult));

        if (!$stripeResult) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Stripe subscription'
            ], 400);
        }

        // Update transaction with Stripe data
        $transaction->update([
            'status' => Transaction::STATUS_COMPLETED,
            'external_transaction_id' => $stripeResult['subscription_id'],
            'processed_at' => Carbon::now(),
        ]);

        // Update or create local subscription
        $this->createOrUpdateSubscriptionWithStripe($user, $transaction, $stripeResult);

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['package', 'subscription']),
            'message' => 'Payment processed successfully and subscription activated!'
        ]);
    }

    /**
     * Process mock payment for non-Stripe methods
     */
    private function processMockPayment(Transaction $transaction, $user): JsonResponse
    {
        // Simulate payment processing
        $success = rand(1, 10) > 2; // 80% success rate for demo

        if ($success) {
            $transaction->update([
                'status' => Transaction::STATUS_COMPLETED,
                'processed_at' => Carbon::now(),
                'external_transaction_id' => 'EXT-' . strtoupper(uniqid()),
            ]);

            // Create or update subscription if this is a subscription transaction
            if ($transaction->type === Transaction::TYPE_SUBSCRIPTION) {
                $this->createOrUpdateSubscription($user, $transaction);
            }

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['package', 'subscription']),
                'message' => 'Payment processed successfully and subscription activated!'
            ]);
        } else {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed'
            ], 400);
        }
    }

    /**
     * Create or update subscription with Stripe data
     */
    private function createOrUpdateSubscriptionWithStripe($user, $transaction, $stripeResult): void
    {
        // Cancel any existing active subscription
        $existingSubscription = $user->activeSubscription;
        if ($existingSubscription) {
            $existingSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => $existingSubscription->current_period_end,
            ]);
        }

        // Create new subscription with Stripe data
        $package = $transaction->package;
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => $stripeResult['status'],
            'current_period_start' => $stripeResult['current_period_start'],
            'current_period_end' => $stripeResult['current_period_end'],
            'stripe_subscription_id' => $stripeResult['subscription_id'],
            'stripe_customer_id' => $stripeResult['customer_id'],
            'created_by' => $user->id,
        ]);

        // Update transaction with subscription reference
        $transaction->update([
            'user_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Create or update subscription based on transaction
     */
    private function createOrUpdateSubscription($user, $transaction): void
    {
        // Cancel any existing active subscription
        $existingSubscription = $user->activeSubscription;
        if ($existingSubscription) {
            $existingSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => $existingSubscription->current_period_end,
            ]);
        }

        // Create new subscription
        $package = $transaction->package;
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = Carbon::now()->addMonth();

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'active',
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'created_by' => $user->id,
        ]);

        // Update transaction with subscription reference
        $transaction->update([
            'user_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Create a pending subscription for a new transaction.
     * This is used when a subscription transaction is created but payment is not yet processed.
     */
    private function createPendingSubscription($user, $transaction): void
    {
        // Cancel any existing active subscription
        $existingSubscription = $user->activeSubscription;
        if ($existingSubscription) {
            $existingSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => $existingSubscription->current_period_end,
            ]);
        }

        // Create new pending subscription
        $package = $transaction->package;
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = Carbon::now()->addMonth();

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'pending', // Set status to pending
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'created_by' => $user->id,
        ]);

        // Update transaction with subscription reference
        $transaction->update([
            'user_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Admin: Get all transactions
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Transaction::contentProtection()
            ->with(['user', 'package', 'subscription'])
            ->orderBy('created_at', 'desc');

        // Filter by status - handle null and 'null' values
        if ($request->filled('status') && $request->status !== 'null' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Filter by payment method - handle null and 'null' values
        if ($request->filled('payment_method') && $request->payment_method !== 'null' && $request->payment_method !== null) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by type - handle null and 'null' values
        if ($request->filled('type') && $request->type !== 'null' && $request->type !== null) {
            $query->where('type', $request->type);
        }

        // Filter by user - handle null and 'null' values
        if ($request->filled('user_id') && $request->user_id !== 'null' && $request->user_id !== null) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range - handle null and 'null' values
        if ($request->filled('start_date') && $request->start_date !== 'null' && $request->start_date !== null) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date') && $request->end_date !== 'null' && $request->end_date !== null) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'debug' => [
                'filters_applied' => [
                    'status' => $request->get('status'),
                    'payment_method' => $request->get('payment_method'),
                    'type' => $request->get('type'),
                    'user_id' => $request->get('user_id'),
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date')
                ]
            ]
        ]);
    }

    /**
     * Admin: Get transaction statistics
     */
    public function adminStats(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());

        $baseQuery = Transaction::contentProtection()->whereBetween('created_at', [$startDate, $endDate]);
        
        $stats = [
            'total_transactions' => $baseQuery->count(),
            'total_amount' => $baseQuery->where('status', Transaction::STATUS_COMPLETED)->sum('amount'),
            'completed_transactions' => $baseQuery->where('status', Transaction::STATUS_COMPLETED)->count(),
            'failed_transactions' => $baseQuery->where('status', Transaction::STATUS_FAILED)->count(),
            'pending_transactions' => $baseQuery->where('status', Transaction::STATUS_PENDING)->count(),
            'payment_methods' => [
                'stripe' => Transaction::contentProtection()->byPaymentMethod(Transaction::PAYMENT_STRIPE)->count(),
                'paypal' => Transaction::contentProtection()->byPaymentMethod(Transaction::PAYMENT_PAYPAL)->count(),
                'manual' => Transaction::contentProtection()->byPaymentMethod(Transaction::PAYMENT_MANUAL)->count(),
            ],
            'transaction_types' => [
                'subscription' => Transaction::contentProtection()->byType(Transaction::TYPE_SUBSCRIPTION)->count(),
                'upgrade' => Transaction::contentProtection()->byType(Transaction::TYPE_UPGRADE)->count(),
                'renewal' => Transaction::contentProtection()->byType(Transaction::TYPE_RENEWAL)->count(),
                'refund' => Transaction::contentProtection()->byType(Transaction::TYPE_REFUND)->count(),
                'trial' => Transaction::contentProtection()->byType(Transaction::TYPE_TRIAL)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
