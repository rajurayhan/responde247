<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerSubscription;
use App\Models\ResellerUsagePeriod;
use App\Models\ResellerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ResellerBillingService
{
    protected StripeService $stripeService;

    public function __construct()
    {
        $this->stripeService = new StripeService();
    }

    /**
     * Bill immediate overage (>= $10 threshold)
     */
    public function billImmediateOverage(ResellerUsagePeriod $period): ?ResellerTransaction
    {
        try {
            // Check if already billed
            if ($period->overage_status === 'billed') {
                Log::info('Overage already billed, skipping', [
                    'usage_period_id' => $period->id,
                    'reseller_id' => $period->reseller_id,
                ]);
                return null;
            }

            $overageAmount = $period->getOverageAmount();

            // Verify threshold
            // if (!$this->meetsThreshold($overageAmount)) {
            //     Log::info('Overage below threshold, not billing immediately', [
            //         'usage_period_id' => $period->id,
            //         'overage_amount' => $overageAmount,
            //         'threshold' => config('reseller-billing.overage_threshold', 10.00),
            //     ]);
            //     return null;
            // }

            $reseller = $period->reseller;
            $subscription = $period->subscription;

            if (!$subscription || !$subscription->stripe_customer_id) {
                Log::error('Cannot bill overage: missing subscription or Stripe customer', [
                    'usage_period_id' => $period->id,
                    'reseller_id' => $period->reseller_id,
                    'subscription'=> json_encode($subscription),
                    
                ]);
                return null;
            }

            // Create transaction record
            $transaction = $this->createOverageTransaction(
                $reseller,
                $subscription,
                $period,
                $overageAmount
            );

            // Charge via Stripe
            $stripeChargeId = $this->chargeResellerStripe(
                $reseller,
                $subscription,
                $overageAmount,
                "Usage overage billing - Period: {$period->period_label}"
            );

            if ($stripeChargeId) {
                // Payment successful
                DB::transaction(function () use ($transaction, $period, $stripeChargeId) {
                    $transaction->update([
                        'status' => ResellerTransaction::STATUS_COMPLETED,
                        'external_transaction_id' => $stripeChargeId,
                        'processed_at' => now(),
                    ]);

                    // After billing overage, reset the effective usage limits
                    // so the reseller can continue using from their current level
                    $newEffectiveLimit = $period->total_duration_seconds; // Current usage becomes new baseline
                    
                    $period->update([
                        'overage_status' => 'billed',
                        'overage_billed_at' => now(),
                        'overage_transaction_id' => $transaction->id,
                    ]);

                    // Reset subscription's pending overage
                    $period->subscription->update([
                        'pending_overage_cost' => 0,
                    ]);
                });

                Log::info('Overage billed successfully', [
                    'usage_period_id' => $period->id,
                    'reseller_id' => $reseller->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $overageAmount,
                    'stripe_charge_id' => $stripeChargeId,
                ]);

                // TODO: Send notification to reseller
                // event(new ResellerOverageBilled($reseller, $transaction, $period));

                return $transaction;
            } else {
                // Payment failed
                $transaction->update([
                    'status' => ResellerTransaction::STATUS_FAILED,
                    'failed_at' => now(),
                ]);

                Log::error('Overage billing failed', [
                    'usage_period_id' => $period->id,
                    'reseller_id' => $reseller->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $overageAmount,
                ]);

                // TODO: Send notification to reseller and admin
                // event(new ResellerPaymentFailed($reseller, $transaction, $period));

                return null;
            }

        } catch (\Exception $e) {
            Log::error('Error billing immediate overage', [
                'usage_period_id' => $period->id,
                'reseller_id' => $period->reseller_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Carry forward small overage to next period
     */
    public function carryForwardOverage(ResellerUsagePeriod $period): void
    {
        try {
            if (!config('reseller-billing.carry_forward_enabled', true)) {
                Log::info('Carry forward disabled, not carrying forward overage', [
                    'usage_period_id' => $period->id,
                ]);
                return;
            }

            $overageAmount = $period->getOverageAmount();

            if ($overageAmount <= 0) {
                return;
            }

            // Mark this period as carried forward
            $period->update([
                'overage_status' => 'carried_forward',
            ]);

            Log::info('Overage carried forward to next period', [
                'usage_period_id' => $period->id,
                'reseller_id' => $period->reseller_id,
                'carried_forward_amount' => $overageAmount,
            ]);

            // Create next period with carried forward amount
            $subscription = $period->subscription;
            
            if ($subscription && $subscription->isActive()) {
                $usageTracker = new ResellerUsageTracker();
                $usageTracker->createUsagePeriod($subscription, $overageAmount, 0);
            }

        } catch (\Exception $e) {
            Log::error('Error carrying forward overage', [
                'usage_period_id' => $period->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process end-of-period billing (monthly)
     * Called by scheduled command
     */
    public function processEndOfPeriodBilling(ResellerSubscription $subscription): void
    {
        try {
            // Get the current (ending) usage period
            $endingPeriod = ResellerUsagePeriod::where('reseller_subscription_id', $subscription->id)
                ->where('period_end', '<=', now())
                ->where('overage_status', '!=', 'billed')
                ->where('overage_status', '!=', 'carried_forward')
                ->orderBy('period_end', 'desc')
                ->first();

            if (!$endingPeriod) {
                return;
            }

            $overageAmount = $endingPeriod->getOverageAmount();

            if ($overageAmount <= 0) {
                // No overage, just mark period as complete
                $endingPeriod->update(['overage_status' => 'none']);
                return;
            }

            // Check if overage meets billing threshold
            if ($this->meetsThreshold($overageAmount)) {
                // Bill it
                $this->billImmediateOverage($endingPeriod);
            } else {
                // Carry forward
                $this->carryForwardOverage($endingPeriod);
            }

            // Reset subscription counters
            $subscription->resetPeriodUsage();

            // Create new usage period for next cycle if subscription is still active
            if ($subscription->isActive() && !$subscription->hasExpired()) {
                $usageTracker = new ResellerUsageTracker();
                $usageTracker->createUsagePeriod($subscription);
            }

            Log::info('End-of-period billing processed', [
                'subscription_id' => $subscription->id,
                'reseller_id' => $subscription->reseller_id,
                'usage_period_id' => $endingPeriod->id,
                'overage_amount' => $overageAmount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing end-of-period billing', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create overage transaction record
     */
    private function createOverageTransaction(
        Reseller $reseller,
        ResellerSubscription $subscription,
        ResellerUsagePeriod $period,
        float $amount
    ): ResellerTransaction {
        return ResellerTransaction::create([
            'reseller_id' => $reseller->id,
            'reseller_package_id' => $subscription->reseller_package_id,
            'reseller_subscription_id' => $subscription->id,
            'usage_period_id' => $period->id,
            'amount' => $amount,
            'currency' => config('reseller-billing.currency', 'USD'),
            'status' => ResellerTransaction::STATUS_PENDING,
            'payment_method' => ResellerTransaction::PAYMENT_STRIPE,
            'type' => ResellerTransaction::TYPE_OVERAGE,
            'description' => "Usage overage billing for period {$period->period_label}",
            'billing_email' => $reseller->company_email,
            'billing_name' => $reseller->org_name,
            'metadata' => [
                'period_start' => $period->period_start->toISOString(),
                'period_end' => $period->period_end->toISOString(),
                'total_calls' => $period->total_calls,
                'total_duration_seconds' => $period->total_duration_seconds,
                'total_cost' => $period->total_cost,
            ],
            'overage_details' => [
                'overage_cost' => $period->overage_cost,
                'overage_minutes' => $period->overage_minutes,
                'carried_forward_amount' => $period->carried_forward_amount,
                'total_overage' => $period->getOverageAmount(),
                'monthly_minutes_limit' => $period->monthly_minutes_limit,
                'extra_per_minute_charge' => $period->extra_per_minute_charge,
                'tracking_method' => config('reseller-billing.tracking_method', 'cost'),
            ],
        ]);
    }

    /**
     * Charge reseller via Stripe using Invoices
     */
    private function chargeResellerStripe(
        Reseller $reseller,
        ResellerSubscription $subscription,
        float $amount,
        string $description
    ): ?string {
        try {
            // Check if test mode is enabled or if this is test data
            $isTestMode = config('reseller-billing.test_mode', false) || 
                         (isset($subscription->metadata['test_data']) && $subscription->metadata['test_data']) ||
                         (strpos($subscription->stripe_customer_id, 'cus_test_') === 0);
            
            if ($isTestMode) {
                Log::info('Test mode: simulating Stripe charge', [
                    'reseller_id' => $reseller->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $amount,
                    'description' => $description,
                    'reason' => 'test_mode_enabled',
                ]);
                
                // Return a fake invoice ID for test mode
                return 'in_test_' . time() . '_' . uniqid();
            }

            if (!$subscription->stripe_customer_id) {
                Log::error('Cannot charge: no Stripe customer ID', [
                    'reseller_id' => $reseller->id,
                    'subscription_id' => $subscription->id,
                ]);
                return null;
            }

            // Initialize Stripe with reseller context
            $this->stripeService->setResellerContext($reseller->id);

            // Create Invoice with line items directly
            $invoice = \Stripe\Invoice::create([
                'customer' => $subscription->stripe_customer_id,
                'auto_advance' => false, // We'll finalize manually
                'collection_method' => 'charge_automatically',
                'description' => "Usage Overage Billing - {$reseller->org_name}",
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'subscription_id' => $subscription->id,
                    'type' => 'usage_overage',
                ],
            ]);

            Log::info('Invoice created', [
                'reseller_id' => $reseller->id,
                'invoice_id' => $invoice->id,
                'status' => $invoice->status,
            ]);

            // Add line item to the invoice
            $invoiceItem = \Stripe\InvoiceItem::create([
                'customer' => $subscription->stripe_customer_id,
                'invoice' => $invoice->id, // Attach to specific invoice
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => strtolower(config('reseller-billing.currency', 'USD')),
                'description' => $description,
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'subscription_id' => $subscription->id,
                    'type' => 'usage_overage',
                ],
            ]);

            Log::info('Invoice item added', [
                'reseller_id' => $reseller->id,
                'invoice_id' => $invoice->id,
                'invoice_item_id' => $invoiceItem->id,
                'amount' => $amount,
            ]);

            // Step 3: Finalize and pay the invoice
            $invoice = $invoice->finalizeInvoice();

            Log::info('Invoice finalized', [
                'reseller_id' => $reseller->id,
                'invoice_id' => $invoice->id,
                'status' => $invoice->status,
                'total' => $invoice->total / 100,
                'amount_due' => $invoice->amount_due / 100,
                'amount_paid' => $invoice->amount_paid / 100,
            ]);

            // Step 4: Attempt to pay the invoice
            if ($invoice->status === 'open') {
                try {
                    $invoice = $invoice->pay();
                    
                    Log::info('Invoice payment attempted', [
                        'reseller_id' => $reseller->id,
                        'invoice_id' => $invoice->id,
                        'status' => $invoice->status,
                        'paid' => $invoice->paid,
                        'amount_paid' => $invoice->amount_paid / 100,
                        'amount_remaining' => $invoice->amount_remaining / 100,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to pay invoice', [
                        'reseller_id' => $reseller->id,
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            }

            // Check if payment succeeded
            if ($invoice->status === 'paid' || $invoice->paid) {
                Log::info('Invoice paid successfully', [
                    'reseller_id' => $reseller->id,
                    'invoice_id' => $invoice->id,
                    'status' => $invoice->status,
                    'amount_paid' => $invoice->amount_paid / 100,
                    'amount_due' => $invoice->amount_due / 100,
                    'amount_remaining' => $invoice->amount_remaining / 100,
                ]);
                
                return $invoice->id;
            }

            // Payment failed or pending
            Log::warning('Invoice not paid', [
                'reseller_id' => $reseller->id,
                'invoice_id' => $invoice->id,
                'status' => $invoice->status,
                'paid' => $invoice->paid,
                'amount_paid' => $invoice->amount_paid / 100,
                'amount_due' => $invoice->amount_due / 100,
                'amount_remaining' => $invoice->amount_remaining / 100,
            ]);
            
            return null;

        } catch (\Stripe\Exception\CardException $e) {
            Log::error('Card error charging reseller', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage(),
                'decline_code' => $e->getDeclineCode(),
            ]);
            return null;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Invalid request to Stripe', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage(),
                'param' => $e->getStripeParam(),
            ]);
            return null;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error charging reseller', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error charging reseller via Stripe', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Check if overage amount meets billing threshold
     */
    private function meetsThreshold(float $amount): bool
    {
        $threshold = config('reseller-billing.overage_threshold', 10.00);
        return $amount >= $threshold;
    }

    /**
     * Retry failed payment
     */
    public function retryFailedPayment(ResellerTransaction $transaction): bool
    {
        if ($transaction->status !== ResellerTransaction::STATUS_FAILED) {
            return false;
        }

        $usagePeriod = $transaction->usagePeriod;
        if (!$usagePeriod) {
            return false;
        }

        // Reset transaction status
        $transaction->update([
            'status' => ResellerTransaction::STATUS_PENDING,
            'failed_at' => null,
        ]);

        // Try billing again
        $result = $this->billImmediateOverage($usagePeriod);

        return $result !== null && $result->isCompleted();
    }

    /**
     * Get billing summary for reseller
     */
    public function getBillingSummary(string $resellerId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ResellerTransaction::where('reseller_id', $resellerId)
            ->where('type', ResellerTransaction::TYPE_OVERAGE);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return [
            'reseller_id' => $resellerId,
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->where('status', ResellerTransaction::STATUS_COMPLETED)->sum('amount'),
            'pending_amount' => $transactions->where('status', ResellerTransaction::STATUS_PENDING)->sum('amount'),
            'failed_amount' => $transactions->where('status', ResellerTransaction::STATUS_FAILED)->sum('amount'),
            'completed_count' => $transactions->where('status', ResellerTransaction::STATUS_COMPLETED)->count(),
            'failed_count' => $transactions->where('status', ResellerTransaction::STATUS_FAILED)->count(),
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                    'processed_at' => $transaction->processed_at,
                    'failed_at' => $transaction->failed_at,
                    'overage_details' => $transaction->overage_details,
                ];
            }),
        ];
    }
}

