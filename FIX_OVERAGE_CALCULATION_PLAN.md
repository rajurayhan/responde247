# Fix Overage Calculation Logic - Implementation Plan

**Date**: September 30, 2025  
**Issue**: Incorrect overage calculation for minute-limited packages  
**Priority**: High  
**Status**: Planning

---

## 🎯 **Problem Summary**

The current billing system incorrectly treats minute-limited packages as cost-limited packages, causing:

1. **Wrong Cost Limit Calculation**: `monthly_cost_limit = minutes_limit × extra_per_minute_charge`
2. **Incorrect Overage Logic**: Uses cost-based calculation instead of duration-based
3. **Wrong Billing Decisions**: Undercharges or overcharges resellers

---

## 📊 **Current vs Expected Behavior**

### **Current (Incorrect)**
```php
// Package: 100 minutes, $0.50 per extra minute
monthly_cost_limit = 100 × $0.50 = $50.00  // ❌ Wrong
overage_cost = total_cost - monthly_cost_limit  // ❌ Wrong logic
```

### **Expected (Correct)**
```php
// Package: 100 minutes included, $0.50 per extra minute
monthly_cost_limit = 0  // ✅ No cost limit
overage_minutes = total_minutes - 100  // ✅ Calculate overage minutes
overage_cost = overage_minutes × $0.50  // ✅ Calculate overage cost
```

---

## 🔧 **Implementation Plan**

### **Phase 1: Fix Core Logic**

#### **1.1 Update ResellerUsageTracker::createUsagePeriod()**
**File**: `app/Services/ResellerUsageTracker.php`  
**Line**: ~140

**Current Code:**
```php
$monthlyCostLimit = $package->monthly_minutes_limit * $package->extra_per_minute_charge;
```

**New Code:**
```php
// For minute-limited packages: no cost limit, only minutes limit
if ($package->monthly_minutes_limit > 0) {
    $monthlyCostLimit = 0; // No cost limit for minute packages
    $monthlyMinutesLimit = $package->monthly_minutes_limit;
} else {
    // For unlimited packages: cost limit = package price
    $monthlyCostLimit = $package->price;
    $monthlyMinutesLimit = -1;
}
```

#### **1.2 Update ResellerUsageTracker::calculateOverage()**
**File**: `app/Services/ResellerUsageTracker.php`  
**Method**: `calculateOverage()`

**Current Logic:**
```php
// Cost-based calculation (wrong for minute packages)
if ($trackingMethod === 'cost') {
    return $this->calculateCostBasedOverage($period);
}
```

**New Logic:**
```php
// Duration-based calculation for minute packages
if ($period->monthly_minutes_limit > 0) {
    return $this->calculateDurationBasedOverage($period);
} else {
    // Cost-based only for unlimited packages
    return $this->calculateCostBasedOverage($period);
}
```

#### **1.3 Update calculateDurationBasedOverage() Method**
**File**: `app/Services/ResellerUsageTracker.php`  
**Method**: `calculateDurationBasedOverage()`

**Current Logic:**
```php
// Uses total_cost - monthly_cost_limit (wrong)
$overageCost = max(0, $period->total_cost - $period->monthly_cost_limit);
```

**New Logic:**
```php
// Calculate overage based on minutes
$totalMinutes = $period->total_duration_seconds / 60;
$overageMinutes = max(0, $totalMinutes - $period->monthly_minutes_limit);
$overageCost = $overageMinutes * $period->extra_per_minute_charge;
```

---

### **Phase 2: Update Database Records**

#### **2.1 Create Migration for Existing Data**
**File**: `database/migrations/2025_10_01_fix_overage_calculations.php`

**Purpose**: Fix existing usage periods with incorrect calculations

**Steps:**
1. Find all minute-limited packages (monthly_minutes_limit > 0)
2. Recalculate monthly_cost_limit = 0
3. Recalculate overage_cost based on minutes
4. Update overage_minutes correctly

#### **2.2 Create Artisan Command**
**File**: `app/Console/Commands/FixOverageCalculations.php`

**Command**: `php artisan reseller:fix-overage-calculations`

**Features:**
- Dry-run mode
- Specific reseller filtering
- Progress reporting
- Rollback capability

---

### **Phase 3: Update Models and Relationships**

#### **3.1 Update ResellerUsagePeriod Model**
**File**: `app/Models/ResellerUsagePeriod.php`

**Add Methods:**
```php
public function getOverageMinutesAttribute()
{
    if ($this->monthly_minutes_limit <= 0) {
        return 0;
    }
    
    $totalMinutes = $this->total_duration_seconds / 60;
    return max(0, $totalMinutes - $this->monthly_minutes_limit);
}

public function getOverageCostAttribute()
{
    if ($this->monthly_minutes_limit <= 0) {
        // For unlimited packages, use cost-based calculation
        return max(0, $this->total_cost - $this->monthly_cost_limit);
    }
    
    // For minute packages, use duration-based calculation
    return $this->overage_minutes * $this->extra_per_minute_charge;
}
```

#### **3.2 Update ResellerPackage Model**
**File**: `app/Models/ResellerPackage.php`

**Add Methods:**
```php
public function isUnlimitedMinutes()
{
    return $this->monthly_minutes_limit === -1;
}

public function isMinuteLimited()
{
    return $this->monthly_minutes_limit > 0;
}
```

---

### **Phase 4: Update Billing Service**

#### **4.1 Update ResellerBillingService**
**File**: `app/Services/ResellerBillingService.php`

**Update Methods:**
- `calculateOverageCharges()`
- `getBillingSummary()`
- `processEndOfPeriodBilling()`

**Changes:**
- Use correct overage calculation method
- Handle minute vs cost-based packages
- Update transaction descriptions

#### **4.2 Update Billing Command**
**File**: `app/Console/Commands/ProcessResellerBilling.php`

**Updates:**
- Better logging for overage calculations
- Show minutes vs cost breakdown
- Handle both package types correctly

---

### **Phase 5: Testing and Validation**

#### **5.1 Unit Tests**
**File**: `tests/Unit/ResellerUsageTrackerTest.php`

**Test Cases:**
- Minute-limited package overage calculation
- Unlimited package overage calculation
- Edge cases (exact limit, zero usage)
- Large overage amounts

#### **5.2 Integration Tests**
**File**: `tests/Feature/ResellerBillingTest.php`

**Test Scenarios:**
- End-to-end billing process
- Different package types
- Threshold-based billing decisions
- Carry-forward functionality

#### **5.3 Manual Testing**
**Steps:**
1. Create test subscriptions with different package types
2. Generate usage data with overages
3. Run billing command
4. Verify correct calculations and billing decisions

---

## 📋 **Implementation Checklist**

### **Phase 1: Core Logic** ⏳
- [ ] Update `createUsagePeriod()` method
- [ ] Update `calculateOverage()` method
- [ ] Update `calculateDurationBasedOverage()` method
- [ ] Test with new usage periods

### **Phase 2: Data Migration** ⏳
- [ ] Create migration for existing data
- [ ] Create fix command
- [ ] Test migration with sample data
- [ ] Run migration on production data

### **Phase 3: Model Updates** ⏳
- [ ] Add overage calculation methods to model
- [ ] Add package type helper methods
- [ ] Update model relationships
- [ ] Test model methods

### **Phase 4: Service Updates** ⏳
- [ ] Update billing service methods
- [ ] Update billing command
- [ ] Update transaction descriptions
- [ ] Test billing flow

### **Phase 5: Testing** ⏳
- [ ] Write unit tests
- [ ] Write integration tests
- [ ] Manual testing scenarios
- [ ] Performance testing

---

## 🚨 **Risk Assessment**

### **High Risk**
- **Data Migration**: Existing overage calculations will change
- **Billing Accuracy**: Wrong calculations could over/under charge customers

### **Medium Risk**
- **Performance**: Recalculating all existing periods
- **Downtime**: Migration might require brief service interruption

### **Low Risk**
- **Code Changes**: Well-contained changes
- **Testing**: Can be thoroughly tested before deployment

---

## 🛡️ **Mitigation Strategies**

### **1. Gradual Rollout**
- Deploy to staging environment first
- Test with small subset of data
- Monitor billing accuracy

### **2. Rollback Plan**
- Keep backup of original calculations
- Create rollback migration
- Test rollback procedure

### **3. Validation**
- Compare old vs new calculations
- Manual verification of sample data
- Monitor for billing discrepancies

---

## 📊 **Expected Results**

### **Before Fix**
```json
{
  "monthly_cost_limit": 50.0000,  // Wrong
  "overage_cost": 3.0000,         // Wrong
  "overage_minutes": 70           // Correct
}
```

### **After Fix**
```json
{
  "monthly_cost_limit": 0.0000,   // Correct
  "overage_cost": 35.2500,        // Correct (70.5 × $0.50)
  "overage_minutes": 70.5         // Correct
}
```

### **Billing Command Output**
```
Processing: Sulus.AI
  Period: 2025-09-30 to 2025-10-30
  Total calls: 4
  Total minutes: 170.5
  Minutes limit: 100
  Overage minutes: 70.5
  Overage cost: $35.25
  ✓ Billed overage: $35.25
```

---

## 🎯 **Success Criteria**

1. **Correct Calculations**: Overage costs calculated based on minutes, not total cost
2. **Accurate Billing**: Resellers charged correct overage amounts
3. **Data Integrity**: All existing data updated correctly
4. **No Downtime**: System continues to work during migration
5. **Performance**: No significant impact on system performance

---

## 📅 **Timeline**

- **Phase 1**: 2-3 hours (Core logic fixes)
- **Phase 2**: 1-2 hours (Data migration)
- **Phase 3**: 1 hour (Model updates)
- **Phase 4**: 1-2 hours (Service updates)
- **Phase 5**: 2-3 hours (Testing)

**Total Estimated Time**: 7-11 hours

---

## 🚀 **Next Steps**

1. **Review and approve** this plan
2. **Start with Phase 1** (Core logic fixes)
3. **Test thoroughly** before moving to next phase
4. **Deploy incrementally** with monitoring
5. **Validate results** in production

This plan will fix the overage calculation logic and ensure accurate billing for resellers! 🎉
