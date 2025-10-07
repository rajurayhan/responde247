# Twilio Address Requirements by Country

## Overview

Twilio requires address verification for phone numbers in certain countries due to regulatory compliance requirements. This document lists all countries and their address verification status.

## Countries Requiring Address Verification

### **Primary Countries (Definitely Required)**
These countries **always** require address verification when purchasing phone numbers:

1. **Australia (AU)** - ✅ **Confirmed Required**
   - Country Code: `AU`
   - Phone Prefix: `+61`
   - Address Required: **YES**

2. **United Kingdom (GB)** - ✅ **Confirmed Required**
   - Country Code: `GB`
   - Phone Prefix: `+44`
   - Address Required: **YES**

3. **Canada (CA)** - ✅ **Confirmed Required**
   - Country Code: `CA`
   - Phone Prefix: `+1`
   - Address Required: **YES**

4. **New Zealand (NZ)** - ✅ **Confirmed Required**
   - Country Code: `NZ`
   - Phone Prefix: `+64`
   - Address Required: **YES**

5. **Ireland (IE)** - ✅ **Confirmed Required**
   - Country Code: `IE`
   - Phone Prefix: `+353`
   - Address Required: **YES**

### **Secondary Countries (May Require)**
These countries **may** require address verification depending on regulatory changes:

6. **Germany (DE)** - ⚠️ **May Require**
   - Country Code: `DE`
   - Phone Prefix: `+49`
   - Address Required: **POTENTIALLY**

7. **France (FR)** - ⚠️ **May Require**
   - Country Code: `FR`
   - Phone Prefix: `+33`
   - Address Required: **POTENTIALLY**

8. **Spain (ES)** - ⚠️ **May Require**
   - Country Code: `ES`
   - Phone Prefix: `+34`
   - Address Required: **POTENTIALLY**

9. **Italy (IT)** - ⚠️ **May Require**
   - Country Code: `IT`
   - Phone Prefix: `+39`
   - Address Required: **POTENTIALLY**

10. **Netherlands (NL)** - ⚠️ **May Require**
    - Country Code: `NL`
    - Phone Prefix: `+31`
    - Address Required: **POTENTIALLY**

## Countries That Don't Require Address Verification

### **North America**
- **United States (US)** - ✅ **No Address Required**
  - Country Code: `US`
  - Phone Prefix: `+1`
  - Address Required: **NO**

- **Mexico (MX)** - ✅ **No Address Required**
  - Country Code: `MX`
  - Phone Prefix: `+52`
  - Address Required: **NO**

### **South America**
- **Brazil (BR)** - ✅ **No Address Required**
  - Country Code: `BR`
  - Phone Prefix: `+55`
  - Address Required: **NO**

- **Argentina (AR)** - ✅ **No Address Required**
  - Country Code: `AR`
  - Phone Prefix: `+54`
  - Address Required: **NO**

- **Chile (CL)** - ✅ **No Address Required**
  - Country Code: `CL`
  - Phone Prefix: `+56`
  - Address Required: **NO**

### **Asia**
- **India (IN)** - ✅ **No Address Required**
  - Country Code: `IN`
  - Phone Prefix: `+91`
  - Address Required: **NO**

- **Japan (JP)** - ✅ **No Address Required**
  - Country Code: `JP`
  - Phone Prefix: `+81`
  - Address Required: **NO**

- **South Korea (KR)** - ✅ **No Address Required**
  - Country Code: `KR`
  - Phone Prefix: `+82`
  - Address Required: **NO**

- **Singapore (SG)** - ✅ **No Address Required**
  - Country Code: `SG`
  - Phone Prefix: `+65`
  - Address Required: **NO**

- **China (CN)** - ✅ **No Address Required**
  - Country Code: `CN`
  - Phone Prefix: `+86`
  - Address Required: **NO**

### **Other Regions**
- **South Africa (ZA)** - ✅ **No Address Required**
  - Country Code: `ZA`
  - Phone Prefix: `+27`
  - Address Required: **NO**

- **Israel (IL)** - ✅ **No Address Required**
  - Country Code: `IL`
  - Phone Prefix: `+972`
  - Address Required: **NO**

## Implementation in Code

### **Current Implementation**
The system now uses a validated address SID for countries that require address verification:

```php
private function getAddressSidForCountry($countryCode)
{
    // Countries that require address verification
    $countriesRequiringAddress = ['AU', 'GB', 'CA', 'NZ', 'IE', 'DE', 'FR', 'ES', 'IT', 'NL'];
    
    if (!in_array($countryCode, $countriesRequiringAddress)) {
        return null;
    }
    
    // Use the validated address SID for all countries that require it
    $validatedAddressSid = 'ADf6726b97d6cf8b06546eeaca6417b418';
    
    return $validatedAddressSid;
}
```

### **Address SID Used**
- **Validated Address SID**: `ADf6726b97d6cf8b06546eeaca6417b418`
- **Status**: ✅ **Validated and Approved**
- **Usage**: Used for all countries requiring address verification

## Error Messages

### **When Address is Required but Not Provided**
```
"Address verification required for this country. Please contact support to set up address verification in your Twilio account."
```

### **When Address is Provided but Invalid**
```
"Invalid address SID provided. Please contact support to verify your address."
```

## Testing

### **Test Countries with Address Requirements**
1. **Australia**: Should use address SID automatically
2. **United Kingdom**: Should use address SID automatically
3. **Canada**: Should use address SID automatically
4. **New Zealand**: Should use address SID automatically
5. **Ireland**: Should use address SID automatically

### **Test Countries without Address Requirements**
1. **United States**: Should work without address SID
2. **Mexico**: Should work without address SID
3. **Brazil**: Should work without address SID
4. **India**: Should work without address SID
5. **Japan**: Should work without address SID

## Regulatory Compliance

### **Why Address Verification is Required**
- **Regulatory Compliance**: Many countries require address verification for phone number ownership
- **Anti-Fraud Measures**: Prevents misuse of phone numbers
- **Legal Requirements**: Ensures compliance with local telecommunications laws
- **Audit Trail**: Provides documentation for regulatory audits

### **Address Validation Process**
1. **Submit Address**: Address details are submitted to Twilio
2. **Verification**: Twilio verifies the address with local authorities
3. **Approval**: Address is approved for use with phone numbers
4. **Usage**: Address SID can be used for phone number purchases

## Support Information

### **For Address Setup**
- **Email**: support@xpartfone.com
- **Phone**: (231) 444-5797

### **For Technical Issues**
- **Documentation**: Check this file for country-specific requirements
- **Logs**: Check Laravel logs for detailed error information
- **Testing**: Use the provided test cases to verify functionality

## Future Considerations

### **Potential Changes**
- **New Countries**: More countries may require address verification in the future
- **Regulatory Updates**: Existing countries may change their requirements
- **Address Expiration**: Addresses may need periodic re-verification

### **Monitoring**
- **Error Tracking**: Monitor address-related errors
- **Success Rates**: Track successful purchases by country
- **Regulatory Updates**: Stay informed about new requirements

## Quick Reference

### **Always Requires Address**
- Australia (AU)
- United Kingdom (GB)
- Canada (CA)
- New Zealand (NZ)
- Ireland (IE)

### **May Require Address**
- Germany (DE)
- France (FR)
- Spain (ES)
- Italy (IT)
- Netherlands (NL)

### **Never Requires Address**
- United States (US)
- Mexico (MX)
- Brazil (BR)
- India (IN)
- Japan (JP)
- South Korea (KR)
- Singapore (SG)
- China (CN)
- South Africa (ZA)
- Israel (IL) 