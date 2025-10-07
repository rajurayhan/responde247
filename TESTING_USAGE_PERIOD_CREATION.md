# Testing ResellerUsagePeriod Creation

**Status**: ✅ **READY FOR TESTING**  
**Date**: September 30, 2025  
**Purpose**: Easy testing of ResellerUsagePeriod creation without webhooks

---

## 🧪 **Testing Methods**

### **Method 1: Create New Subscription (Automatic)**
When you create a new reseller subscription via the API, it will automatically create a usage period.

**Endpoint**: `POST /api/super-admin/reseller-subscriptions`

**Request Body**:
```json
{
    "reseller_id": "your-reseller-id",
    "reseller_package_id": "your-package-id",
    "status": "active",
    "current_period_start": "2025-09-30 00:00:00",
    "current_period_end": "2025-10-30 23:59:59",
    "custom_amount": 99.00,
    "metadata": {
        "billing_interval": "monthly"
    }
}
```

**Expected Result**: 
- Subscription created
- Usage period created automatically
- Check logs for "Reseller usage period created in store method (testing)"

---

### **Method 2: Create Usage Period for Existing Subscription**
Manually create a usage period for an existing active subscription.

**Endpoint**: `POST /api/super-admin/reseller-subscriptions/{subscription_id}/create-usage-period`

**Example**:
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  http://localhost/api/super-admin/reseller-subscriptions/1/create-usage-period
```

**Expected Response**:
```json
{
    "success": true,
    "data": {
        "usage_period": {
            "id": 1,
            "reseller_id": "reseller-123",
            "reseller_subscription_id": 1,
            "period_start": "2025-09-30 00:00:00",
            "period_end": "2025-10-30 23:59:59",
            "total_calls": 0,
            "total_cost": 0,
            "overage_status": "none"
        },
        "subscription": {
            "id": 1,
            "reseller_id": "reseller-123",
            "status": "active",
            "package": {
                "name": "Basic Plan",
                "monthly_minutes_limit": 1000
            }
        }
    },
    "message": "Usage period created successfully"
}
```

---

## 🔍 **Verification Steps**

### **1. Check Database**
```sql
-- Check if usage period was created
SELECT 
    rup.id,
    rup.reseller_id,
    rup.reseller_subscription_id,
    rup.period_start,
    rup.period_end,
    rup.total_calls,
    rup.total_cost,
    rup.overage_status,
    rup.created_at
FROM reseller_usage_periods rup
WHERE rup.reseller_id = 'your-reseller-id'
ORDER BY rup.created_at DESC;
```

### **2. Check Logs**
```bash
# Look for usage period creation logs
tail -f storage/logs/laravel.log | grep "usage period created"
```

### **3. Test Usage Tracking**
```bash
# Test current usage endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost/api/reseller/usage/current
```

---

## 📊 **Expected Data Structure**

### **ResellerUsagePeriod Table**
```sql
CREATE TABLE reseller_usage_periods (
    id BIGINT PRIMARY KEY,
    reseller_id VARCHAR(255),
    reseller_subscription_id BIGINT,
    reseller_package_id BIGINT,
    period_start DATETIME,
    period_end DATETIME,
    total_calls INT DEFAULT 0,
    total_duration_seconds INT DEFAULT 0,
    total_cost DECIMAL(10,4) DEFAULT 0,
    monthly_cost_limit DECIMAL(10,4),
    monthly_minutes_limit INT,
    extra_per_minute_charge DECIMAL(8,4),
    overage_cost DECIMAL(10,4) DEFAULT 0,
    overage_minutes INT DEFAULT 0,
    overage_status ENUM('none', 'pending', 'billed', 'carried_forward') DEFAULT 'none',
    overage_billed_at DATETIME NULL,
    overage_transaction_id BIGINT NULL,
    carried_forward_amount DECIMAL(10,4) DEFAULT 0,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🚨 **Error Scenarios**

### **1. Subscription Not Active**
```json
{
    "success": false,
    "message": "Subscription must be active to create usage period"
}
```

### **2. Usage Period Already Exists**
```json
{
    "success": false,
    "message": "Usage period already exists for this subscription"
}
```

### **3. Missing Reseller/Package**
```json
{
    "success": false,
    "message": "Error creating usage period: [error details]"
}
```

---

## 🧪 **Test Scenarios**

### **Scenario 1: New Subscription Creation**
1. Create a new reseller subscription
2. **Expected**: Usage period created automatically
3. **Verify**: Check database and logs

### **Scenario 2: Manual Usage Period Creation**
1. Find an existing active subscription
2. Call the create usage period endpoint
3. **Expected**: Usage period created successfully
4. **Verify**: Check response and database

### **Scenario 3: Duplicate Prevention**
1. Try to create usage period for subscription that already has one
2. **Expected**: Error message about existing period
3. **Verify**: No duplicate created

### **Scenario 4: Inactive Subscription**
1. Try to create usage period for cancelled/expired subscription
2. **Expected**: Error about subscription needing to be active
3. **Verify**: No usage period created

---

## 📝 **Quick Test Commands**

### **Create Test Subscription**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reseller_id": "test-reseller-123",
    "reseller_package_id": 1,
    "status": "active",
    "current_period_start": "2025-09-30 00:00:00",
    "current_period_end": "2025-10-30 23:59:59"
  }' \
  http://localhost/api/super-admin/reseller-subscriptions
```

### **Create Usage Period**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost/api/super-admin/reseller-subscriptions/1/create-usage-period
```

### **Check Usage Period**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost/api/reseller/usage/current
```

---

## ✅ **Success Indicators**

1. **Database**: New record in `reseller_usage_periods` table
2. **Logs**: Success message in Laravel logs
3. **API Response**: Success response with usage period data
4. **Usage Tracking**: Current usage endpoint returns data

---

## 🔧 **Troubleshooting**

### **Common Issues**

1. **Missing Dependencies**: Ensure `ResellerUsageTracker` is properly injected
2. **Database Errors**: Check if all migrations are run
3. **Validation Errors**: Ensure subscription data is valid
4. **Permission Errors**: Ensure user has super admin access

### **Debug Steps**

1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Verify database: Check if tables exist and have data
3. Test API endpoints: Use Postman or curl
4. Check authentication: Ensure valid token

---

## 🎯 **Next Steps After Testing**

1. **Remove Testing Code**: Remove the automatic creation from `store` method
2. **Keep Webhook Logic**: Keep the webhook-based creation for production
3. **Deploy**: Deploy the webhook changes to production
4. **Monitor**: Watch for usage period creation in production logs

---

## 📋 **Summary**

✅ **ResellerUsagePeriod creation added to ResellerSubscriptionController**  
✅ **Automatic creation in store method for easy testing**  
✅ **Manual creation endpoint for existing subscriptions**  
✅ **Proper error handling and validation**  
✅ **Ready for testing without webhooks**  

You can now easily test the ResellerUsagePeriod creation functionality! 🚀
