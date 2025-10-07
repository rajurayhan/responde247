# UK Phone Number Bundle Requirement Fix

## Issue Description

When trying to purchase UK phone numbers from the Create Assistant page, users were getting this error:

```json
{
    "success": false,
    "message": "Failed to purchase phone number: [HTTP 400] Unable to create record: Bundle required and not provided for country: [GB] and numberType: [LOCAL]"
}
```

## Root Cause

Twilio requires a **Bundle SID** for UK phone numbers, not just an Address SID. A Bundle in Twilio is a collection of regulatory compliance information that includes addresses, business information, and other required documentation for specific countries.

For UK phone numbers, Twilio requires:
- **Bundle SID**: A complete regulatory compliance bundle
- **Business Information**: Company details, business type, etc.
- **Address Verification**: UK-specific address requirements
- **Regulatory Compliance**: UK telecommunications regulations

## Solution Implemented

### 1. Updated Services Configuration

Added Bundle SID configuration in `config/services.php`:

```php
'bundle_sids' => [
    'GB' => env('TWILIO_UK_BUNDLE_SID', 'BU4c3f06239dbde51132ce10dc187822e6'),
],
```

### 2. Updated TwilioService.php

- **Added `getBundleSidForCountry()` method** to retrieve Bundle SIDs
- **Modified `purchaseNumber()` method** to use Bundle SIDs for UK
- **Enhanced error handling** for bundle-related errors
- **Added specific error messages** for UK bundle requirements

### 3. Key Changes

#### TwilioService.php
```php
// Before
$addressSid = $this->getAddressSidForCountry($countryCode);
if ($addressSid) {
    $purchaseParams['addressSid'] = $addressSid;
}

// After
$bundleSid = $this->getBundleSidForCountry($countryCode);
if ($bundleSid) {
    $purchaseParams['bundleSid'] = $bundleSid;
} else {
    $addressSid = $this->getAddressSidForCountry($countryCode);
    if ($addressSid) {
        $purchaseParams['addressSid'] = $addressSid;
    }
}
```

## Environment Variables Required

Add these environment variables to your `.env` file:

```env
# UK Bundle SID (required for UK phone numbers)
TWILIO_UK_BUNDLE_SID=BU4c3f06239dbde51132ce10dc187822e6

# UK Address SID (fallback, but Bundle is preferred for UK)
TWILIO_UK_ADDRESS_SID=AD4c3f06239dbde51132ce10dc187822e6
```

## Countries and Requirements

### **Countries Requiring Bundle SIDs**
- **United Kingdom (GB)** - ✅ **Requires Bundle SID**
  - Country Code: `GB`
  - Phone Prefix: `+44`
  - Bundle Required: **YES**

### **Countries Requiring Address SIDs**
- **Australia (AU)** - ✅ **Requires Address SID**
- **Canada (CA)** - ✅ **Requires Address SID**
- **New Zealand (NZ)** - ✅ **Requires Address SID**
- **Ireland (IE)** - ✅ **Requires Address SID**

### **Countries Not Requiring Verification**
- **United States (US)** - ✅ **No verification required**

## Setting Up UK Bundle in Twilio Console

### 1. Create a Bundle
1. Go to [Twilio Console > Regulatory Compliance > Bundles](https://console.twilio.com/us1/develop/phone-numbers/regulatory-compliance/bundles)
2. Click "Create a new Bundle"
3. Select "United Kingdom" as the country
4. Fill in all required business information
5. Add and verify your UK address
6. Submit for verification

### 2. Get the Bundle SID
1. Once your bundle is approved, copy the Bundle SID
2. Add it to your `.env` file as `TWILIO_UK_BUNDLE_SID`

### 3. Bundle Requirements for UK
- **Business Information**: Company name, business type, website
- **Address**: UK address with proper verification
- **Business Type**: Must be a legitimate business
- **Regulatory Compliance**: Must comply with UK telecommunications regulations

## Error Handling

The system now provides specific error messages:

- **For bundle requirements**: "Bundle verification required for this country. For UK numbers, please contact support to set up bundle verification in your Twilio account."
- **For address requirements**: "Address verification required for this country. Please contact support to set up address verification in your Twilio account."
- **For unavailable numbers**: "Phone number is not available for purchase. Please select a different number from the available list."

## Testing

### Test UK Number Purchase:
1. Create an assistant with country set to "United Kingdom"
2. Select a UK phone number
3. The system will now use the Bundle SID for purchase

### Test US Number Purchase:
1. Create an assistant with country set to "United States"
2. Select a US phone number
3. The purchase should work normally without any verification

## Next Steps for Full Resolution

### For Users:
1. **Contact Support**: If you need UK phone numbers, contact support to set up bundle verification in your Twilio account
2. **Use US Numbers**: For immediate testing, use US phone numbers which don't require verification
3. **Alternative Countries**: Consider using numbers from countries that don't require bundle verification

### For Developers:
1. **Implement Bundle Management**: Add functionality to create and manage Twilio bundles
2. **Add Bundle UI**: Create interface for users to manage their bundles
3. **Bundle Validation**: Implement bundle validation and verification
4. **Country-Specific Logic**: Add more sophisticated country-specific handling

## Logging

The system now logs bundle usage:

```
Twilio Purchase Number: Using bundle SID BU4c3f06239dbde51132ce10dc187822e6 for country GB
```

## Important Notes

- **Bundle SID takes precedence** over Address SID for UK numbers
- **Bundle verification is more comprehensive** than address verification
- **UK has stricter requirements** than other countries
- **Bundle creation requires business verification** in Twilio console 