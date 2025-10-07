# Reseller Usage-Based Billing System - Implementation Plan

## Overview
This document outlines the plan for implementing a usage-based billing system for resellers. The system will track call usage costs against package limits and automatically bill resellers when they exceed their allocated usage.

---

## Current System Analysis

### Existing Models & Data
1. **CallLog Model** - Stores individual call records with:
   - `reseller_id` - Links call to reseller
   - `cost` - Cost in USD (decimal 4 places)
   - `duration` - Duration in seconds
   - `user_id`, `assistant_id` - Links to user/assistant

2. **ResellerSubscription Model** - Reseller subscription details:
   - `reseller_id` - Links to reseller
   - `reseller_package_id` - Links to package
   - `status` - active, pending, cancelled, expired
   - `current_period_start` - Billing period start
   - `current_period_end` - Billing period end
   - `stripe_subscription_id`, `stripe_customer_id` - Stripe integration

3. **ResellerPackage Model** - Package configuration:
   - `monthly_minutes_limit` - Minutes included (-1 = unlimited)
   - `extra_per_minute_charge` - Cost per minute when over limit
   - `price` - Base monthly subscription price

4. **ResellerTransaction Model** - Payment records:
   - Already has `TYPE_OVERAGE` constant
   - Supports Stripe payments
   - Tracks amount, status, metadata

5. **VapiWebhookController** - Processes call events:
   - `handleEndOfCallReport()` - Creates CallLog with reseller_id and cost
   - Integration point for real-time usage tracking

6. **StripeService** - Payment processing:
   - Methods for creating payment intents
   - Charging customers
   - Reseller-specific Stripe configurations

---

## Business Logic Requirements

### Billing Rules
1. **Usage Tracking**: Track total call costs per reseller per billing period
2. **Overage Threshold**: 
   - If overage >= $10: Bill immediately (auto-charge)
   - If overage < $10: Carry forward to next billing period
3. **Billing Frequency**: 
   - Immediate billing for $10+ overages
   - Monthly billing for accumulated overages < $10
4. **Unlimited Packages**: Packages with `monthly_minutes_limit = -1` have no usage limits

### Cost Calculation Methods

#### Option A: Cost-Based Tracking (Recommended)
- Track actual `cost` field from CallLog (Vapi.ai charges)
- More accurate as it reflects actual provider costs
- Simpler calculation: SUM(cost) per billing period
- Package limit would need to be converted to cost equivalent

#### Option B: Duration-Based Tracking  
- Track duration and apply package's `extra_per_minute_charge`
- Convert minutes to cost: `(duration_seconds / 60) * extra_per_minute_charge`
- More predictable for resellers

**Recommended**: Use **Cost-Based** tracking as Vapi.ai already provides accurate cost per call.

---

## Database Schema Changes

### 1. New Table: `reseller_usage_periods`
Tracks usage and charges per billing period.

```sql
CREATE TABLE reseller_usage_periods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    reseller_id CHAR(36) NOT NULL,
    reseller_subscription_id BIGINT UNSIGNED NOT NULL,
    reseller_package_id BIGINT UNSIGNED NOT NULL,
    
    -- Period tracking
    period_start DATETIME NOT NULL,
    period_end DATETIME NOT NULL,
    
    -- Usage metrics
    total_calls INT DEFAULT 0,
    total_duration_seconds INT DEFAULT 0,
    total_cost DECIMAL(10, 4) DEFAULT 0.0000,
    
    -- Package limits
    monthly_cost_limit DECIMAL(10, 4) DEFAULT 0.0000,
    monthly_minutes_limit INT DEFAULT -1,
    extra_per_minute_charge DECIMAL(8, 4) DEFAULT 0.0000,
    
    -- Overage tracking
    overage_cost DECIMAL(10, 4) DEFAULT 0.0000,
    overage_minutes INT DEFAULT 0,
    overage_status ENUM('none', 'pending', 'billed', 'carried_forward') DEFAULT 'none',
    
    -- Billing
    overage_billed_at DATETIME NULL,
    overage_transaction_id BIGINT UNSIGNED NULL,
    carried_forward_amount DECIMAL(10, 4) DEFAULT 0.0000,
    
    -- Metadata
    metadata JSON NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_reseller_period (reseller_id, period_start, period_end),
    INDEX idx_subscription (reseller_subscription_id),
    INDEX idx_overage_status (overage_status),
    
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE CASCADE,
    FOREIGN KEY (reseller_subscription_id) REFERENCES reseller_subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (reseller_package_id) REFERENCES reseller_packages(id),
    FOREIGN KEY (overage_transaction_id) REFERENCES reseller_transactions(id) ON DELETE SET NULL
);
```

### 2. Add Columns to `reseller_subscriptions`
Track current period usage in real-time.

```sql
ALTER TABLE reseller_subscriptions ADD COLUMN (
    current_period_usage_cost DECIMAL(10, 4) DEFAULT 0.0000,
    current_period_calls INT DEFAULT 0,
    current_period_duration INT DEFAULT 0,
    pending_overage_cost DECIMAL(10, 4) DEFAULT 0.0000,
    last_usage_calculated_at DATETIME NULL,
    metadata JSON NULL
);
```

### 3. Add Columns to `reseller_transactions`
Enhance overage transaction tracking.

```sql
ALTER TABLE reseller_transactions ADD COLUMN (
    usage_period_id BIGINT UNSIGNED NULL,
    overage_details JSON NULL,
    
    FOREIGN KEY (usage_period_id) REFERENCES reseller_usage_periods(id) ON DELETE SET NULL
);
```

---

## Service Architecture

### 1. New Service: `ResellerUsageTracker`
**Location**: `app/Services/ResellerUsageTracker.php`

**Responsibilities**:
- Track usage in real-time when calls complete
- Update current period metrics
- Calculate overage amounts
- Trigger billing when threshold is reached

**Key Methods**:
```php
// Called from VapiWebhookController after call log creation
public function trackCallUsage(CallLog $callLog): void

// Get current usage period for reseller
public function getCurrentUsagePeriod(string $resellerId): ?ResellerUsagePeriod

// Create new usage period when subscription starts/renews
public function createUsagePeriod(ResellerSubscription $subscription): ResellerUsagePeriod

// Calculate overage for current period
public function calculateOverage(ResellerUsagePeriod $period): array

// Check if billing threshold is met
public function shouldBillOverage(float $overageAmount): bool

// Get usage statistics
public function getUsageStats(string $resellerId, ?Carbon $startDate, ?Carbon $endDate): array
```

### 2. New Service: `ResellerBillingService`
**Location**: `app/Services/ResellerBillingService.php`

**Responsibilities**:
- Handle overage billing (auto-charge)
- Create overage transactions
- Carry forward small balances
- Process end-of-period billing

**Key Methods**:
```php
// Bill immediate overage (>= $10)
public function billImmediateOverage(ResellerUsagePeriod $period): ?ResellerTransaction

// Carry forward small overage to next period
public function carryForwardOverage(ResellerUsagePeriod $period): void

// Process end-of-period billing (monthly)
public function processEndOfPeriodBilling(ResellerSubscription $subscription): void

// Create overage transaction
private function createOverageTransaction(Reseller $reseller, float $amount, array $metadata): ResellerTransaction

// Charge reseller via Stripe
private function chargeResellerStripe(Reseller $reseller, float $amount, string $description): ?string
```

### 3. New Model: `ResellerUsagePeriod`
**Location**: `app/Models/ResellerUsagePeriod.php`

**Relationships**:
```php
public function reseller(): BelongsTo
public function subscription(): BelongsTo
public function package(): BelongsTo
public function transaction(): BelongsTo // overage transaction
public function callLogs(): HasMany
```

**Scopes & Helpers**:
```php
public function scopeCurrentPeriod($query)
public function scopePendingBilling($query)
public function hasOverage(): bool
public function isOverThreshold(): bool
public function getOverageAmount(): float
```

### 4. Extend Existing Model: `ResellerSubscription`
Add helper methods for usage tracking:

```php
public function getCurrentUsagePeriod(): ?ResellerUsagePeriod
public function hasUsageOverage(): bool
public function getUsagePercentage(): float
public function resetPeriodUsage(): void
```

---

## Integration Points

### 1. VapiWebhookController - Real-Time Usage Tracking
**File**: `app/Http/Controllers/VapiWebhookController.php`

**Modification in `handleEndOfCallReport()` method**:
```php
private function handleEndOfCallReport(array $payload, Assistant $assistant)
{
    $processor = new VapiCallReportProcessor();
    $callLog = $processor->processEndCallReport($payload);

    if ($callLog) {
        // NEW: Track reseller usage
        $usageTracker = new ResellerUsageTracker();
        $usageTracker->trackCallUsage($callLog);
        
        Log::info('End-of-call-report processed with usage tracking', [
            'call_id' => $callLog->call_id,
            'reseller_id' => $callLog->reseller_id,
            'cost' => $callLog->cost,
        ]);
    }
}
```

### 2. Scheduled Command - End of Period Processing
**New Command**: `app/Console/Commands/ProcessResellerBilling.php`

Run daily via cron to:
- Check for ended billing periods
- Process any pending overages < $10
- Create usage periods for new billing cycles
- Send usage reports/notifications

```php
php artisan reseller:process-billing
```

**Schedule in `app/Console/Kernel.php`**:
```php
protected function schedule(Schedule $schedule)
{
    // Run daily at 2 AM
    $schedule->command('reseller:process-billing')->dailyAt('02:00');
    
    // Optional: Send weekly usage reports
    $schedule->command('reseller:send-usage-reports')->weekly()->mondays()->at('09:00');
}
```

### 3. API Endpoints - Usage Visibility
**New Routes** (`routes/api.php`):

```php
// Reseller usage endpoints
Route::middleware(['auth:sanctum'])->group(function () {
    // Get current period usage
    Route::get('/reseller/usage/current', [ResellerUsageController::class, 'currentUsage']);
    
    // Get usage history
    Route::get('/reseller/usage/history', [ResellerUsageController::class, 'usageHistory']);
    
    // Get overage transactions
    Route::get('/reseller/usage/overages', [ResellerUsageController::class, 'overages']);
    
    // Get usage by date range
    Route::get('/reseller/usage/range', [ResellerUsageController::class, 'usageByRange']);
});
```

**New Controller**: `app/Http/Controllers/ResellerUsageController.php`

---

## Billing Flow Diagrams

### Flow 1: Call Completed → Usage Tracking
```
1. Vapi.ai sends end-of-call-report webhook
2. VapiWebhookController receives webhook
3. VapiCallReportProcessor creates CallLog (with cost, reseller_id)
4. ResellerUsageTracker.trackCallUsage() called
5. Find/Create current usage period
6. Update usage metrics:
   - total_calls++
   - total_cost += call.cost
   - total_duration_seconds += call.duration
7. Calculate overage
8. If overage >= $10:
   → Call ResellerBillingService.billImmediateOverage()
9. If overage < $10:
   → Mark as pending, will bill at period end
```

### Flow 2: Immediate Overage Billing (>= $10)
```
1. ResellerBillingService.billImmediateOverage() triggered
2. Get reseller subscription & Stripe customer ID
3. Create ResellerTransaction (type: OVERAGE, status: PENDING)
4. Call StripeService to charge customer:
   - Create PaymentIntent
   - Attach to customer
   - Confirm payment
5. If payment successful:
   - Update transaction status → COMPLETED
   - Update usage_period.overage_status → 'billed'
   - Update usage_period.overage_billed_at → now()
   - Reset pending_overage_cost
6. If payment fails:
   - Update transaction status → FAILED
   - Log error
   - Send notification to reseller
   - Retry logic (3 attempts)
```

### Flow 3: End of Billing Period
```
1. Scheduled command runs daily
2. Find all subscriptions where current_period_end <= today
3. For each subscription:
   a. Get current usage period
   b. Finalize usage calculations
   c. If pending overage < $10:
      → Carry forward to next period
      → Add to next period's carried_forward_amount
   d. Create new usage period for next billing cycle
   e. Reset subscription's current_period_usage_cost
   f. Send usage summary email to reseller
4. Archive completed usage period
```

### Flow 4: Carry Forward Small Overages
```
1. When period ends with overage < $10
2. ResellerBillingService.carryForwardOverage() called
3. Create next usage period with:
   - carried_forward_amount = previous overage
4. When calculating future overage:
   - Include carried_forward_amount in threshold calculation
5. If accumulated amount reaches $10:
   - Bill immediately
```

---

## Calculation Examples

### Example 1: Package with Minutes Limit
**Package**: 
- monthly_minutes_limit: 1000 minutes
- extra_per_minute_charge: $0.05

**Scenario**: Reseller uses 1,200 minutes
- Included: 1,000 minutes (covered by subscription)
- Overage: 200 minutes
- Overage Cost: 200 × $0.05 = **$10.00**
- **Action**: Bill immediately ($10 threshold met)

### Example 2: Cost-Based with Carried Forward
**Billing Period 1**:
- Call costs from Vapi.ai: $8.50 overage
- **Action**: Carry forward $8.50 to Period 2

**Billing Period 2**:
- Call costs from Vapi.ai: $6.75 overage
- Carried forward: $8.50
- Total: $8.50 + $6.75 = **$15.25**
- **Action**: Bill immediately $15.25

### Example 3: Unlimited Package
**Package**:
- monthly_minutes_limit: -1 (unlimited)
- **Action**: No overage billing, all calls included

---

## Error Handling & Edge Cases

### 1. Payment Failures
**Scenario**: Stripe payment fails when billing overage

**Handling**:
- Log failure with error details
- Keep transaction status as FAILED
- Implement retry mechanism (3 attempts, exponential backoff)
- Send notification to reseller admin
- If all retries fail:
  - Flag subscription for review
  - Optionally suspend service after grace period
  - Carry overage to next attempt

### 2. Webhook Delays/Failures
**Scenario**: Webhook not received or processed

**Handling**:
- Implement fallback sync mechanism
- Scheduled command to fetch missing call logs from Vapi.ai
- Backfill usage periods if gaps detected
- Idempotency: Check if call_id already processed

### 3. Subscription Changes Mid-Period
**Scenario**: Reseller upgrades/downgrades package during billing period

**Handling**:
- Pro-rate the period:
  - Calculate usage up to change date
  - Create new usage period with new package limits
  - Transfer any pending overage
- Stripe webhook will update subscription
- Recalculate limits based on new package

### 4. Time Zone Considerations
**Handling**:
- Store all timestamps in UTC
- Convert to reseller's timezone for display/reporting
- Use Carbon for date calculations
- Ensure period_start/period_end are timezone-aware

### 5. Concurrent Calls / Race Conditions
**Scenario**: Multiple webhooks processed simultaneously

**Handling**:
- Use database transactions for usage updates
- Lock usage_period record during update
- Use atomic increment operations where possible
- Implement idempotency checks on call_id

### 6. Missing or Invalid Data
**Scenario**: CallLog missing cost or reseller_id

**Handling**:
- Validate data before tracking
- Log warnings for invalid data
- Skip usage tracking but don't fail webhook processing
- Alert admin if pattern of missing data detected

---

## Configuration & Settings

### 1. Config File: `config/reseller-billing.php`
```php
return [
    // Overage billing threshold (in USD)
    'overage_threshold' => env('RESELLER_OVERAGE_THRESHOLD', 10.00),
    
    // Grace period before suspending service (days)
    'payment_failure_grace_period' => env('RESELLER_PAYMENT_GRACE_PERIOD', 7),
    
    // Number of payment retry attempts
    'payment_retry_attempts' => env('RESELLER_PAYMENT_RETRIES', 3),
    
    // Retry delay (minutes): 30, 120, 1440 (1 day)
    'retry_delays' => [30, 120, 1440],
    
    // Enable automatic overage billing
    'auto_billing_enabled' => env('RESELLER_AUTO_BILLING', true),
    
    // Enable carry forward of small overages
    'carry_forward_enabled' => env('RESELLER_CARRY_FORWARD', true),
    
    // Usage tracking method: 'cost' or 'duration'
    'tracking_method' => env('RESELLER_TRACKING_METHOD', 'cost'),
    
    // Send usage alerts at percentages
    'usage_alert_thresholds' => [75, 90, 100],
    
    // Currency
    'currency' => 'USD',
    
    // Enable detailed logging
    'detailed_logging' => env('RESELLER_BILLING_LOG_DETAIL', true),
];
```

### 2. Environment Variables (`.env`)
```env
# Reseller Usage Billing
RESELLER_OVERAGE_THRESHOLD=10.00
RESELLER_PAYMENT_GRACE_PERIOD=7
RESELLER_PAYMENT_RETRIES=3
RESELLER_AUTO_BILLING=true
RESELLER_CARRY_FORWARD=true
RESELLER_TRACKING_METHOD=cost
RESELLER_BILLING_LOG_DETAIL=true
```

---

## Notifications & Alerts

### 1. Email Notifications
**ResellerUsageAlertNotification** - When usage reaches threshold (75%, 90%, 100%)
**ResellerOverageBilledNotification** - When overage is billed
**ResellerPaymentFailedNotification** - When payment fails
**ResellerMonthlyUsageReport** - End of period summary

### 2. Admin Alerts
**AdminResellerOverageAlert** - Alert admins of high overages
**AdminPaymentFailureAlert** - Alert admins of payment failures
**AdminUsageAnomalyAlert** - Detect unusual usage patterns

---

## Testing Requirements

### 1. Unit Tests
- Test usage calculation logic
- Test threshold detection
- Test carry forward calculations
- Test overage amount calculation
- Test period creation/completion

### 2. Integration Tests
- Test webhook → usage tracking flow
- Test Stripe payment integration
- Test scheduled command execution
- Test concurrent webhook handling

### 3. Feature Tests
- Test complete overage billing flow
- Test period rollover
- Test package upgrade/downgrade
- Test payment failure scenarios

### 4. Edge Case Tests
- Test unlimited package (no billing)
- Test zero usage period
- Test negative overage (shouldn't happen)
- Test missing data scenarios
- Test timezone edge cases (period boundaries)

---

## Monitoring & Observability

### 1. Metrics to Track
- Total usage cost per reseller per period
- Overage frequency and amounts
- Payment success/failure rates
- Average time to bill overage
- Carried forward amounts
- API response times for usage endpoints

### 2. Logging
- All usage updates
- All billing attempts
- Payment successes/failures
- Webhook processing
- Scheduled job executions

### 3. Alerting
- Failed payments
- High usage anomalies
- Missing webhooks
- Processing errors
- Threshold breaches

---

## Implementation Phases

### Phase 1: Foundation (Week 1)
- [ ] Create database migrations
  - [ ] `reseller_usage_periods` table
  - [ ] Add columns to `reseller_subscriptions`
  - [ ] Add columns to `reseller_transactions`
- [ ] Create models
  - [ ] `ResellerUsagePeriod` model with relationships
  - [ ] Update `ResellerSubscription` with new methods
  - [ ] Update `ResellerTransaction` with new columns
- [ ] Create configuration file
  - [ ] `config/reseller-billing.php`

### Phase 2: Core Services (Week 2)
- [ ] Build `ResellerUsageTracker` service
  - [ ] `trackCallUsage()` method
  - [ ] `getCurrentUsagePeriod()` method
  - [ ] `createUsagePeriod()` method
  - [ ] `calculateOverage()` method
- [ ] Build `ResellerBillingService`
  - [ ] `billImmediateOverage()` method
  - [ ] `carryForwardOverage()` method
  - [ ] `processEndOfPeriodBilling()` method
  - [ ] Integrate with `StripeService`

### Phase 3: Integration (Week 3)
- [ ] Integrate with `VapiWebhookController`
  - [ ] Add usage tracking to `handleEndOfCallReport()`
  - [ ] Test with sample webhooks
- [ ] Create scheduled command
  - [ ] `ProcessResellerBilling` command
  - [ ] Add to `Kernel.php` schedule
  - [ ] Test daily execution

### Phase 4: API & Frontend (Week 4)
- [ ] Create API endpoints
  - [ ] `ResellerUsageController` with all endpoints
  - [ ] Add routes to `api.php`
- [ ] Add usage dashboard components (Vue.js)
  - [ ] Current period usage widget
  - [ ] Usage history chart
  - [ ] Overage alerts
- [ ] Create email notifications
  - [ ] Usage alert emails
  - [ ] Overage billed emails
  - [ ] Monthly reports

### Phase 5: Testing & Refinement (Week 5)
- [ ] Write comprehensive tests
  - [ ] Unit tests for calculations
  - [ ] Integration tests for flows
  - [ ] Feature tests for user scenarios
- [ ] Error handling and edge cases
  - [ ] Payment failures
  - [ ] Webhook delays
  - [ ] Concurrent processing
- [ ] Performance optimization
  - [ ] Database indexing
  - [ ] Query optimization
  - [ ] Caching where appropriate

### Phase 6: Deployment (Week 6)
- [ ] Documentation
  - [ ] API documentation
  - [ ] Admin guide
  - [ ] Reseller guide
- [ ] Monitoring setup
  - [ ] Logs configuration
  - [ ] Metrics dashboard
  - [ ] Alerting rules
- [ ] Gradual rollout
  - [ ] Test with pilot resellers
  - [ ] Monitor for issues
  - [ ] Full deployment

---

## Future Enhancements

### 1. Advanced Features
- **Usage Forecasting**: Predict overage based on historical data
- **Auto-scaling Packages**: Automatically suggest package upgrades
- **Usage Budgets**: Set spending limits per reseller
- **Prepaid Credits**: Allow resellers to buy usage credits in advance
- **Volume Discounts**: Tiered pricing for high-volume resellers

### 2. Reporting
- **Advanced Analytics**: Detailed usage breakdown by assistant, time, etc.
- **Cost Attribution**: Break down costs by end user
- **Comparative Reports**: Compare usage across periods
- **Export Options**: PDF/CSV exports of usage data

### 3. Optimization
- **Real-time Usage Updates**: WebSocket updates for dashboard
- **Caching Layer**: Redis caching for frequently accessed usage data
- **Batch Processing**: Process multiple calls in batches for efficiency
- **Archive Old Data**: Move old usage periods to archive table

---

## Security Considerations

### 1. Data Protection
- Encrypt sensitive billing information
- Secure Stripe keys in environment variables
- Use HTTPS for all API communications
- Implement rate limiting on usage API endpoints

### 2. Access Control
- Resellers can only view their own usage
- Admin-only endpoints for global statistics
- Role-based access for billing operations
- Audit log for all billing transactions

### 3. Webhook Security
- Verify Vapi.ai webhook signatures
- Implement replay attack prevention
- Log all webhook attempts
- Rate limit webhook endpoint

---

## Success Metrics

### Key Performance Indicators (KPIs)
1. **Billing Accuracy**: 99.9% accurate billing
2. **Payment Success Rate**: >95% successful payments
3. **Processing Time**: <5 seconds from webhook to usage update
4. **API Response Time**: <500ms for usage queries
5. **Overage Detection**: Real-time detection within 1 minute of threshold
6. **System Uptime**: 99.9% availability
7. **Error Rate**: <0.1% webhook processing errors

### Business Metrics
1. Revenue from overage billing
2. Average overage per reseller
3. Package upgrade conversion rate
4. Payment failure recovery rate
5. Customer satisfaction with billing transparency

---

## Rollback Plan

If issues arise during deployment:

1. **Immediate Actions**:
   - Disable auto-billing via config flag
   - Stop scheduled command
   - Revert webhook integration

2. **Data Preservation**:
   - Keep all usage_periods data
   - Don't delete transactions
   - Maintain audit trail

3. **Communication**:
   - Notify affected resellers
   - Provide manual billing option
   - Set timeline for fix

4. **Recovery**:
   - Identify root cause
   - Fix issues in staging environment
   - Gradual re-enable with monitoring

---

## Conclusion

This plan provides a comprehensive approach to implementing usage-based billing for resellers. The system will:

✅ Track usage in real-time as calls complete
✅ Calculate overages based on package limits
✅ Bill automatically when threshold ($10) is reached
✅ Carry forward small overages to next period
✅ Provide transparency via API and dashboard
✅ Handle edge cases and failures gracefully
✅ Scale with the business

**Next Steps**: 
1. Review and approve this plan
2. Prioritize phases based on business needs
3. Assign development resources
4. Begin Phase 1 implementation

---

**Document Version**: 1.0
**Created**: September 30, 2025
**Status**: Draft - Pending Approval

