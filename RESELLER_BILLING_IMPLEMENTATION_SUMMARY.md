# Reseller Usage-Based Billing System - Implementation Summary

**Status**: ✅ **Phase 1-3 COMPLETED**  
**Date**: September 30, 2025  
**Implementation**: Following `RESELLER_USAGE_BILLING_PLAN.md`

---

## 🎉 What's Been Implemented

### ✅ Phase 1: Foundation (COMPLETED)

#### 1. Database Migrations
**Location**: `database/migrations/`

- ✅ **`reseller_usage_periods` table** - Tracks usage and billing per period
  - All usage metrics (calls, duration, cost)
  - Overage tracking and status
  - Carried forward amounts
  - Foreign keys to resellers, subscriptions, packages, transactions
  - Proper indexes for performance

- ✅ **`reseller_subscriptions` enhancements** - Real-time usage tracking
  - `current_period_usage_cost` - Running cost total
  - `current_period_calls` - Call count
  - `current_period_duration` - Total duration
  - `pending_overage_cost` - Pending charges
  - `last_usage_calculated_at` - Last calculation timestamp

- ✅ **`reseller_transactions` enhancements** - Overage transaction linking
  - `usage_period_id` - Links to usage period
  - `overage_details` - JSON field for detailed overage info
  - Foreign key constraint to reseller_usage_periods

**All migrations use `SafeMigrationTrait` for idempotent operations**

#### 2. Models Created/Updated
**Location**: `app/Models/`

- ✅ **ResellerUsagePeriod.php** (NEW)
  - Complete relationships (reseller, subscription, package, transaction, callLogs)
  - Scopes: `currentPeriod()`, `pendingBilling()`, `withOverage()`
  - Helper methods: `hasOverage()`, `isOverThreshold()`, `getOverageAmount()`
  - Formatted attributes for display
  - Full casting and fillable definitions

- ✅ **ResellerSubscription.php** (UPDATED)
  - Added 5 new fillable fields for usage tracking
  - Added casts for new decimal and datetime fields
  - New relationship: `usagePeriods()`
  - New methods:
    - `getCurrentUsagePeriod()` - Get active billing period
    - `hasUsageOverage()` - Check for pending overages
    - `getUsagePercentage()` - Calculate usage against limits
    - `resetPeriodUsage()` - Reset counters at period end

- ✅ **ResellerTransaction.php** (UPDATED)
  - Added `usage_period_id` and `overage_details` fields
  - Added overage_details to casts
  - New relationship: `usagePeriod()`
  - Already had `TYPE_OVERAGE` constant

#### 3. Configuration File
**Location**: `config/reseller-billing.php`

```php
'overage_threshold' => 10.00,
'payment_failure_grace_period' => 7,
'payment_retry_attempts' => 3,
'auto_billing_enabled' => true,
'carry_forward_enabled' => true,
'tracking_method' => 'cost',
'usage_alert_thresholds' => [75, 90, 100],
// ... and more
```

---

### ✅ Phase 2: Core Services (COMPLETED)

#### 1. ResellerUsageTracker Service
**Location**: `app/Services/ResellerUsageTracker.php`

**Key Features**:
- ✅ Real-time usage tracking from webhooks
- ✅ Database transaction locking to prevent race conditions
- ✅ Automatic threshold detection and billing trigger
- ✅ Support for both cost-based and duration-based tracking
- ✅ Carried forward amount handling
- ✅ Usage statistics and summaries

**Key Methods**:
```php
trackCallUsage(CallLog $callLog): void
getCurrentUsagePeriod(string $resellerId): ?ResellerUsagePeriod
createUsagePeriod(ResellerSubscription $subscription): ResellerUsagePeriod
calculateOverage(ResellerUsagePeriod $period): array
shouldBillOverage(float $overageAmount): bool
getUsageStats(string $resellerId, ?Carbon $startDate, ?Carbon $endDate): array
getCurrentUsageSummary(string $resellerId): array
```

**Flow**:
1. Receives CallLog from webhook
2. Finds/creates current usage period
3. Updates usage metrics (calls, duration, cost) with row locking
4. Calculates overage
5. If overage >= $10, triggers immediate billing
6. Updates subscription's real-time counters
7. Logs all operations

#### 2. ResellerBillingService
**Location**: `app/Services/ResellerBillingService.php`

**Key Features**:
- ✅ Immediate overage billing via Stripe
- ✅ Carry forward for small overages (<$10)
- ✅ End-of-period processing
- ✅ Transaction record creation
- ✅ Failed payment retry logic
- ✅ Comprehensive error handling

**Key Methods**:
```php
billImmediateOverage(ResellerUsagePeriod $period): ?ResellerTransaction
carryForwardOverage(ResellerUsagePeriod $period): void
processEndOfPeriodBilling(ResellerSubscription $subscription): void
retryFailedPayment(ResellerTransaction $transaction): bool
getBillingSummary(string $resellerId, ?Carbon $startDate, ?Carbon $endDate): array
```

**Billing Flow**:
1. Checks if overage meets threshold
2. Validates subscription and Stripe customer
3. Creates transaction record (STATUS_PENDING)
4. Creates Stripe PaymentIntent and confirms
5. If successful:
   - Updates transaction to COMPLETED
   - Marks usage period as 'billed'
   - Resets pending overage
6. If failed:
   - Updates transaction to FAILED
   - Logs error for retry

---

### ✅ Phase 3: Integration (COMPLETED)

#### 1. Webhook Integration
**Location**: `app/Http/Controllers/VapiWebhookController.php`

**Changes**:
```php
// Added to handleEndOfCallReport()
$usageTracker = new ResellerUsageTracker();
$usageTracker->trackCallUsage($callLog);
```

**Features**:
- ✅ Automatically tracks usage when call completes
- ✅ Wrapped in try-catch to not fail webhook
- ✅ Detailed logging of usage and costs
- ✅ Non-blocking (webhook succeeds even if tracking fails)

#### 2. Scheduled Command
**Location**: `app/Console/Commands/ProcessResellerBilling.php`

**Command**: `php artisan reseller:process-billing`

**Features**:
- ✅ Processes ended billing periods
- ✅ Bills or carries forward pending overages
- ✅ Creates new usage periods for next cycle
- ✅ Progress bar for monitoring
- ✅ Comprehensive summary output
- ✅ Dry-run mode for testing
- ✅ Process specific reseller option

**Options**:
```bash
# Normal run
php artisan reseller:process-billing

# Dry run (no changes)
php artisan reseller:process-billing --dry-run

# Process specific reseller
php artisan reseller:process-billing --reseller=uuid-here
```

**Scheduled**: Daily at 2:00 AM (configured in `bootstrap/app.php`)

```php
$schedule->command('reseller:process-billing')->dailyAt('02:00');
```

---

## 🔄 Complete Flow Diagram

### Real-Time Usage Tracking (During Calls)
```
1. Call completes on Vapi.ai
2. Webhook → VapiWebhookController
3. VapiCallReportProcessor creates CallLog
4. ResellerUsageTracker.trackCallUsage()
5. Find/Create usage period
6. Lock period record (prevent race conditions)
7. Update: total_calls++, total_cost += call.cost, etc.
8. Calculate overage
9. If overage >= $10:
   → ResellerBillingService.billImmediateOverage()
   → Create Stripe PaymentIntent
   → Charge customer
   → Update transaction & period status
10. Update subscription counters
11. Return success
```

### End-of-Period Processing (Daily at 2 AM)
```
1. Scheduled command runs
2. Find subscriptions where current_period_end <= today
3. For each subscription:
   a. Get ending usage period
   b. Check overage amount
   c. If >= $10: Bill immediately
   d. If < $10: Carry forward to next period
   e. Reset subscription counters
   f. Create new usage period for next cycle
4. Display summary
5. Log results
```

---

## 📊 Database Schema

### reseller_usage_periods
```
id, reseller_id, reseller_subscription_id, reseller_package_id
period_start, period_end
total_calls, total_duration_seconds, total_cost
monthly_cost_limit, monthly_minutes_limit, extra_per_minute_charge
overage_cost, overage_minutes, overage_status
overage_billed_at, overage_transaction_id, carried_forward_amount
metadata, created_at, updated_at
```

### reseller_subscriptions (new columns)
```
current_period_usage_cost, current_period_calls, current_period_duration
pending_overage_cost, last_usage_calculated_at
```

### reseller_transactions (new columns)
```
usage_period_id, overage_details
```

---

## ⚙️ Configuration

### Environment Variables
Add to `.env`:
```env
RESELLER_OVERAGE_THRESHOLD=10.00
RESELLER_PAYMENT_GRACE_PERIOD=7
RESELLER_PAYMENT_RETRIES=3
RESELLER_AUTO_BILLING=true
RESELLER_CARRY_FORWARD=true
RESELLER_TRACKING_METHOD=cost
RESELLER_BILLING_LOG_DETAIL=true
```

### Tracking Method
**Cost-Based** (Recommended & Implemented):
- Tracks actual costs from Vapi.ai
- More accurate as it reflects real provider costs
- Simple: SUM(call.cost) per period

**Duration-Based** (Available):
- Calculates: (minutes_used - minutes_included) × per_minute_charge
- More predictable for resellers

---

## 🧪 Testing

### Test the Scheduled Command
```bash
# Dry run (no changes)
php artisan reseller:process-billing --dry-run

# Real run
php artisan reseller:process-billing

# Check scheduled tasks
php artisan schedule:list
```

### Test Webhook Integration
Use sample webhook payload from `test-webhook-with-recording.json`

### Manual Testing Scenarios

#### Scenario 1: Real-time Overage Billing
1. Create reseller subscription with limits
2. Generate calls via Vapi.ai
3. Webhook triggers usage tracking
4. When overage reaches $10, auto-billing occurs
5. Check transaction in `reseller_transactions`

#### Scenario 2: Carry Forward
1. Generate $8 overage in period 1
2. Run `reseller:process-billing` at period end
3. Verify overage carried to period 2
4. Generate $5 more overage in period 2
5. Verify total $13 is billed immediately

#### Scenario 3: Unlimited Package
1. Set package `monthly_minutes_limit = -1`
2. Generate calls
3. Verify no overage is calculated

---

## 📈 Monitoring & Logs

### Log Entries to Monitor
```php
// Usage tracking
'Usage tracked successfully'
'Overage billed successfully'
'Overage carried forward to next period'

// Billing process
'Reseller billing process completed'
'End-of-period billing processed'

// Errors
'Error tracking call usage'
'Overage billing failed'
'Error processing subscription in billing command'
```

### Database Queries for Monitoring
```sql
-- Check usage periods
SELECT * FROM reseller_usage_periods 
WHERE overage_status = 'pending' 
AND overage_cost > 0;

-- Check overage transactions
SELECT * FROM reseller_transactions 
WHERE type = 'overage' 
AND status = 'pending';

-- Check reseller current usage
SELECT reseller_id, current_period_usage_cost, pending_overage_cost
FROM reseller_subscriptions
WHERE status = 'active';
```

---

## 🚀 Next Steps (Optional - Phase 4-6)

### Phase 4: API Endpoints (Not Yet Implemented)
- `GET /api/reseller/usage/current` - Current period usage
- `GET /api/reseller/usage/history` - Historical usage
- `GET /api/reseller/usage/overages` - Overage transactions
- Controller: `ResellerUsageController`

### Phase 5: Frontend Dashboard (Not Yet Implemented)
- Vue.js usage widget
- Usage history charts
- Overage alerts
- Real-time updates

### Phase 6: Notifications (Not Yet Implemented)
- Email notifications for usage alerts (75%, 90%, 100%)
- Overage billing notifications
- Payment failure notifications
- Monthly usage reports

---

## 🔐 Security Considerations

✅ **Implemented**:
- Database transaction locking (prevents race conditions)
- Stripe secure payment processing
- Comprehensive error logging
- Input validation in usage tracking

**Recommended**:
- Verify Vapi.ai webhook signatures
- Implement rate limiting on webhook endpoint
- Encrypt sensitive billing data
- Regular security audits

---

## 🐛 Troubleshooting

### Usage Not Being Tracked
1. Check if CallLog has `reseller_id` and `cost`
2. Verify webhook integration in VapiWebhookController
3. Check logs for errors in `trackCallUsage()`
4. Verify reseller has active subscription

### Overage Not Billing
1. Check if overage >= threshold ($10)
2. Verify `auto_billing_enabled = true` in config
3. Check subscription has `stripe_customer_id`
4. Review Stripe API errors in logs

### Scheduled Command Not Running
1. Verify schedule is configured in `bootstrap/app.php`
2. Check cron is running: `php artisan schedule:work`
3. Test manually: `php artisan reseller:process-billing`
4. Check server cron configuration

---

## 📝 Implementation Checklist

### Phase 1: Foundation
- [x] Create reseller_usage_periods migration
- [x] Create reseller_subscriptions enhancement migration
- [x] Create reseller_transactions enhancement migration
- [x] Run migrations successfully
- [x] Create ResellerUsagePeriod model
- [x] Update ResellerSubscription model
- [x] Update ResellerTransaction model
- [x] Create config/reseller-billing.php

### Phase 2: Core Services
- [x] Create ResellerUsageTracker service
- [x] Create ResellerBillingService
- [x] Implement cost-based tracking
- [x] Implement duration-based tracking
- [x] Implement threshold detection
- [x] Implement Stripe integration
- [x] Implement carry forward logic
- [x] Add comprehensive logging

### Phase 3: Integration
- [x] Integrate with VapiWebhookController
- [x] Create ProcessResellerBilling command
- [x] Add scheduling to bootstrap/app.php
- [x] Test webhook integration
- [x] Test scheduled command

### Phase 4-6: Enhancement (PENDING)
- [ ] Create API endpoints
- [ ] Build Vue.js dashboard components
- [ ] Implement email notifications
- [ ] Create admin alerts
- [ ] Build reporting system
- [ ] Implement usage forecasting

---

## 🎯 Key Achievements

✅ **Complete automated billing system** for reseller usage  
✅ **Real-time tracking** with race condition prevention  
✅ **Flexible threshold-based billing** ($10 configurable)  
✅ **Carry forward** for small overages  
✅ **Stripe integration** for automatic payments  
✅ **Comprehensive logging** for debugging and auditing  
✅ **Scheduled processing** for period rollovers  
✅ **Production-ready** with error handling  

---

## 📞 Support

For issues or questions about the billing system:
1. Check logs in `storage/logs/laravel.log`
2. Review this document and `RESELLER_USAGE_BILLING_PLAN.md`
3. Test with `--dry-run` flag first
4. Monitor database for consistency

---

**Status**: ✅ Core implementation complete and ready for production testing  
**Version**: 1.0  
**Last Updated**: September 30, 2025

