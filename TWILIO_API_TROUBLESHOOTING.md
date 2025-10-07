# Twilio API Troubleshooting Guide

## Problem: Numbers Visible in Dashboard but Not Accessible via API

This is a common issue with Twilio API access. Here are the most likely causes and solutions:

## 🔍 **1. Account SID and Auth Token Issues**

### **Check Your Credentials:**
```bash
# Check if credentials are properly set
php artisan tinker
>>> config('services.twilio.account_sid')
>>> config('services.twilio.auth_token')
```

### **Common Issues:**
- **Wrong Account**: You might be using a different account's credentials
- **Test vs Live Account**: Ensure you're using the correct account type
- **Credential Format**: Make sure there are no extra spaces or characters

### **Solution:**
```bash
# Verify your credentials in .env file
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
```

## 🔍 **2. Account Type Restrictions**

### **Trial Account Limitations:**
- **Trial accounts** have limited access to phone numbers
- **Geographic restrictions** on available numbers
- **Limited countries** available for trial accounts

### **Check Your Account Status:**
```bash
# Add this to TwilioService to check account status
public function checkAccountStatus()
{
    try {
        $account = $this->client->api->accounts($this->accountSid)->fetch();
        Log::info('Account Status: ' . $account->status);
        Log::info('Account Type: ' . $account->type);
        return $account;
    } catch (TwilioException $e) {
        Log::error('Account Status Check Error: ' . $e->getMessage());
        return null;
    }
}
```

## 🔍 **3. Geographic Restrictions**

### **Trial Account Geographic Limits:**
- **US/Canada**: Usually available
- **Other countries**: May be restricted for trial accounts
- **Local numbers**: May not be available in all areas

### **Check Available Countries:**
```bash
# Add this method to check available countries
public function getAvailableCountries()
{
    try {
        $countries = $this->client->availablePhoneNumbers->read();
        Log::info('Available Countries: ' . json_encode($countries));
        return $countries;
    } catch (TwilioException $e) {
        Log::error('Countries Check Error: ' . $e->getMessage());
        return [];
    }
}
```

## 🔍 **4. API Permissions**

### **Check API Permissions:**
- **Read permissions** for available phone numbers
- **Account-level permissions** for your API key
- **Subaccount restrictions** if using subaccounts

### **Test API Access:**
```bash
# Test with curl to verify API access
curl -X GET "https://api.twilio.com/2010-04-01/Accounts/YOUR_ACCOUNT_SID/AvailablePhoneNumbers/US/Local.json" \
  -u "YOUR_ACCOUNT_SID:YOUR_AUTH_TOKEN"
```

## 🔍 **5. Number Type Restrictions**

### **Local vs Toll-Free:**
- **Local numbers**: May have geographic restrictions
- **Toll-free numbers**: Usually more widely available
- **Mobile numbers**: May not be available via API

### **Test Different Number Types:**
```php
// Test local numbers
$localNumbers = $this->client->availablePhoneNumbers('US')->local->read();

// Test toll-free numbers
$tollFreeNumbers = $this->client->availablePhoneNumbers('US')->tollFree->read();

// Test mobile numbers (if available)
$mobileNumbers = $this->client->availablePhoneNumbers('US')->mobile->read();
```

## 🔍 **6. Rate Limiting and Quotas**

### **Check API Limits:**
- **Rate limiting**: Too many requests
- **Daily quotas**: Exceeded daily limits
- **Account quotas**: Account-level restrictions

### **Monitor API Usage:**
```php
// Add rate limiting check
public function checkRateLimits()
{
    try {
        $response = $this->client->api->accounts($this->accountSid)->usage->records->read();
        Log::info('API Usage: ' . json_encode($response));
        return $response;
    } catch (TwilioException $e) {
        Log::error('Rate Limit Check Error: ' . $e->getMessage());
        return null;
    }
}
```

## 🔍 **7. Network and Firewall Issues**

### **Check Network Access:**
- **Firewall blocking**: API calls blocked
- **Proxy issues**: Network proxy interfering
- **SSL/TLS issues**: Certificate problems

### **Test Network Connectivity:**
```bash
# Test basic connectivity
curl -I https://api.twilio.com

# Test with verbose output
curl -v -X GET "https://api.twilio.com/2010-04-01/Accounts/YOUR_ACCOUNT_SID/AvailablePhoneNumbers/US/Local.json" \
  -u "YOUR_ACCOUNT_SID:YOUR_AUTH_TOKEN"
```

## 🔍 **8. Debugging Steps**

### **Step 1: Enable Detailed Logging**
```php
// Add to TwilioService constructor
public function __construct()
{
    $this->accountSid = config('services.twilio.account_sid');
    $this->authToken = config('services.twilio.auth_token');
    
    // Enable detailed logging
    Log::info('Twilio Service Initialized');
    Log::info('Account SID: ' . substr($this->accountSid, 0, 10) . '...');
    Log::info('Auth Token: ' . (empty($this->authToken) ? 'EMPTY' : 'SET'));
    
    $this->client = new Client($this->accountSid, $this->authToken);
}
```

### **Step 2: Test Basic API Call**
```php
// Add this method to test basic API access
public function testApiConnection()
{
    try {
        $account = $this->client->api->accounts($this->accountSid)->fetch();
        Log::info('API Connection Successful');
        Log::info('Account Name: ' . $account->friendlyName);
        Log::info('Account Status: ' . $account->status);
        return true;
    } catch (TwilioException $e) {
        Log::error('API Connection Failed: ' . $e->getMessage());
        Log::error('Error Code: ' . $e->getCode());
        return false;
    }
}
```

### **Step 3: Test Available Numbers Endpoint**
```php
// Add this method to test available numbers endpoint
public function testAvailableNumbersEndpoint($countryCode = 'US')
{
    try {
        Log::info('Testing Available Numbers for: ' . $countryCode);
        
        $numbers = $this->client->availablePhoneNumbers($countryCode)
            ->local
            ->read(['limit' => 1]);
        
        Log::info('Available Numbers Test Successful');
        Log::info('Numbers Found: ' . count($numbers));
        
        if (count($numbers) > 0) {
            Log::info('Sample Number: ' . $numbers[0]->phoneNumber);
        }
        
        return count($numbers) > 0;
    } catch (TwilioException $e) {
        Log::error('Available Numbers Test Failed: ' . $e->getMessage());
        Log::error('Error Code: ' . $e->getCode());
        return false;
    }
}
```

## 🔍 **9. Common Error Codes and Solutions**

### **Error 20003 - Authentication Error:**
- **Cause**: Invalid credentials
- **Solution**: Check Account SID and Auth Token

### **Error 20008 - Resource Not Found:**
- **Cause**: Invalid country code or endpoint
- **Solution**: Verify country code and API endpoint

### **Error 20012 - Resource Not Available:**
- **Cause**: No numbers available in requested area
- **Solution**: Try different area codes or countries

### **Error 20013 - Resource Not Accessible:**
- **Cause**: Account restrictions
- **Solution**: Check account status and permissions

## 🔍 **10. Quick Diagnostic Script**

Create this diagnostic script to check all potential issues:

```php
// Add to TwilioService
public function runDiagnostics()
{
    $results = [];
    
    // Test 1: Check credentials
    $results['credentials'] = !empty($this->accountSid) && !empty($this->authToken);
    
    // Test 2: Test API connection
    $results['api_connection'] = $this->testApiConnection();
    
    // Test 3: Test available numbers for different countries
    $countries = ['US', 'CA', 'GB', 'AU'];
    foreach ($countries as $country) {
        $results['available_numbers_' . $country] = $this->testAvailableNumbersEndpoint($country);
    }
    
    // Test 4: Check account status
    try {
        $account = $this->client->api->accounts($this->accountSid)->fetch();
        $results['account_status'] = $account->status;
        $results['account_type'] = $account->type ?? 'unknown';
    } catch (Exception $e) {
        $results['account_status'] = 'error: ' . $e->getMessage();
    }
    
    Log::info('Twilio Diagnostics Results: ' . json_encode($results));
    return $results;
}
```

## 🔍 **11. Most Likely Solutions**

### **For Trial Accounts:**
1. **Upgrade to paid account** for full access
2. **Use US/Canada numbers** only (most available for trials)
3. **Remove geographic restrictions** in your search

### **For Paid Accounts:**
1. **Check account status** and billing
2. **Verify API permissions** and keys
3. **Contact Twilio support** if issues persist

### **For All Accounts:**
1. **Clear API cache** and retry
2. **Check network connectivity**
3. **Verify credentials** are correct

## 🔍 **12. Testing Commands**

```bash
# Test your current setup
php artisan tinker
>>> app(\App\Services\TwilioService::class)->runDiagnostics()

# Check logs for detailed error information
tail -f storage/logs/laravel.log

# Test API directly with curl
curl -X GET "https://api.twilio.com/2010-04-01/Accounts/YOUR_ACCOUNT_SID/AvailablePhoneNumbers/US/Local.json?Limit=1" \
  -u "YOUR_ACCOUNT_SID:YOUR_AUTH_TOKEN"
```

## 🎯 **Next Steps:**

1. **Run the diagnostic script** to identify the specific issue
2. **Check your account type** (trial vs paid)
3. **Verify credentials** are correct
4. **Test with different countries** (start with US/Canada)
5. **Contact Twilio support** if issues persist

The most common cause is **trial account restrictions** - if you're on a trial account, you'll likely only be able to access US/Canada numbers via API, even though you can see more countries in the dashboard. 