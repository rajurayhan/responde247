<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    private $client;
    private $accountSid;
    private $authToken;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->client = new Client($this->accountSid, $this->authToken);
    }

    /**
     * Get available phone numbers for purchase
     */
    public function getAvailableNumbers($countryCode = 'US', $limit = 10, $areaCode = null)
    {
        try {
            // First try: Local numbers with voice and SMS
            $params = [
                'limit' => $limit,
                'voiceEnabled' => true,
                'smsEnabled' => true
            ];
            
            // Add area code filter for supported countries (US, CA, AU, MX)
            if ($areaCode && in_array($countryCode, ['US', 'CA', 'AU', 'MX'])) {
                $params['areaCode'] = $areaCode;
            }
            
            $availableNumbers = $this->client->availablePhoneNumbers($countryCode)
                ->local
                ->read($params);

            // If no local numbers found, try without voice/SMS requirements
            if (empty($availableNumbers)) {
                Log::info('No local numbers with voice+SMS found, trying without restrictions');
                
                $params = ['limit' => $limit];
                if ($areaCode && in_array($countryCode, ['US', 'CA', 'AU', 'MX'])) {
                    $params['areaCode'] = $areaCode;
                }
                
                $availableNumbers = $this->client->availablePhoneNumbers($countryCode)
                    ->local
                    ->read($params);
            }

            // If still no local numbers, try toll-free numbers
            if (empty($availableNumbers)) {
                Log::info('No local numbers found, trying toll-free numbers');
                
                $params = ['limit' => $limit];
                $availableNumbers = $this->client->availablePhoneNumbers($countryCode)
                    ->tollFree
                    ->read($params);
            }

            $formattedNumbers = [];
            foreach ($availableNumbers as $number) {
                $formattedNumbers[] = [
                    'phone_number' => $number->phoneNumber,
                    'locality' => $number->locality ?? null,
                    'region' => $number->region ?? null,
                    'capabilities' => [
                        'voice' => $number->capabilities->voice ?? false,
                        'sms' => $number->capabilities->sms ?? false,
                        'mms' => $number->capabilities->mms ?? false
                    ]
                ];
            }

            Log::info('Twilio Get Available Numbers Success: ' . count($formattedNumbers) . ' numbers found for country: ' . $countryCode);
            return [
                'success' => true,
                'data' => $formattedNumbers
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Get Available Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get available numbers from Twilio: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Get Available Numbers Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while getting available numbers'
            ];
        }
    }

    /**
     * Purchase a phone number
     */
    public function purchaseNumber($phoneNumber, $countryCode = null)
    {
        try {
            // Determine country code if not provided
            if (!$countryCode) {
                $countryCode = $this->determineCountryFromPhoneNumber($phoneNumber);
            }
            
            // Ensure phone number is in E.164 format with correct country code
            $phoneNumber = $this->formatPhoneNumber($phoneNumber, $countryCode);
            
            Log::info('Twilio Purchase Number Attempt', [
                'phone_number' => $phoneNumber,
                'country_code' => $countryCode,
                'original_phone_number' => $phoneNumber
            ]);
            
            // Check if the number is already owned by this account
            $existingNumbers = $this->client->incomingPhoneNumbers
                ->read(['phoneNumber' => $phoneNumber], 1);
            
            if (!empty($existingNumbers)) {
                Log::info('Twilio Purchase Number: Number already owned by account');
                return [
                    'success' => true,
                    'data' => [
                        'sid' => $existingNumbers[0]->sid,
                        'phone_number' => $existingNumbers[0]->phoneNumber,
                        'friendly_name' => $existingNumbers[0]->friendlyName,
                        'status' => 'active'
                    ],
                    'message' => 'Phone number already owned by this account'
                ];
            }
            
            // Check if the number is available for purchase
            $availableNumbers = $this->client->availablePhoneNumbers($countryCode)
                ->local
                ->read(['phoneNumber' => $phoneNumber], 1);
            
            if (empty($availableNumbers)) {
                Log::warning('Twilio Purchase Number: Number not available - ' . $phoneNumber);
                return [
                    'success' => false,
                    'message' => 'Phone number is not available for purchase. Please select a different number from the available list.'
                ];
            }
            
            // Prepare purchase parameters
            $purchaseParams = [
                'phoneNumber' => $phoneNumber,
                // 'voiceUrl' => config('app.url') . '/api/twilio/voice',
                // 'smsUrl' => config('app.url') . '/api/twilio/sms',
                'voiceMethod' => 'POST',
                'smsMethod' => 'POST'
            ];
            
            // Add bundle SID for countries that require it (like UK and Mexico)
            $bundleSid = $this->getBundleSidForCountry($countryCode);
            
            // For Mexico, a valid Bundle SID is required (must start with BU)
            if ($countryCode === 'MX') {
                if (!$bundleSid) {
                    return [
                        'success' => false,
                        'message' => 'Mexico requires a valid Bundle SID. Please ensure TWILIO_MEXICO_BUNDLE_SID in your .env file is set to a valid Bundle SID (starts with "BU"). The current value appears to be invalid or missing.'
                    ];
                }
                $purchaseParams['bundleSid'] = $bundleSid;
                Log::info('Twilio Purchase Number: Using bundle SID ' . $bundleSid . ' for country ' . $countryCode);
                // Note: For Mexico, we don't send Address SID when using Bundle SID (Bundle includes address info)
            } elseif ($bundleSid) {
                // For other countries (like UK), use Bundle SID if available
                $purchaseParams['bundleSid'] = $bundleSid;
                Log::info('Twilio Purchase Number: Using bundle SID ' . $bundleSid . ' for country ' . $countryCode);
            }
            
            // Add address SID for countries that require it (but not for Mexico when using Bundle)
            if ($countryCode !== 'MX' || !$bundleSid) {
                $addressSid = $this->getAddressSidForCountry($countryCode);
                if ($addressSid) {
                    $purchaseParams['addressSid'] = $addressSid;
                    Log::info('Twilio Purchase Number: Using address SID ' . $addressSid . ' for country ' . $countryCode);
                }
            }
            
            // Log the complete purchase parameters for debugging
            Log::info('Twilio Purchase Parameters', [
                'phone_number' => $phoneNumber,
                'country_code' => $countryCode,
                'purchase_params' => $purchaseParams
            ]);
            
            // Purchase the phone number
            $incomingPhoneNumber = $this->client->incomingPhoneNumbers
                ->create($purchaseParams);

            Log::info('Twilio Purchase Number Success: ' . $phoneNumber . ' - SID: ' . $incomingPhoneNumber->sid);
            
            return [
                'success' => true,
                'data' => [
                    'sid' => $incomingPhoneNumber->sid,
                    'phone_number' => $incomingPhoneNumber->phoneNumber,
                    'friendly_name' => $incomingPhoneNumber->friendlyName,
                    'status' => $incomingPhoneNumber->status
                ],
                'message' => 'Phone number purchased successfully'
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Purchase Number Error: ' . $e->getMessage());
            Log::error('Twilio Purchase Number Request Data: ' . json_encode([
                'PhoneNumber' => $phoneNumber,
                'VoiceUrl' => config('app.url') . '/api/twilio/voice',
                'SmsUrl' => config('app.url') . '/api/twilio/sms',
                'VoiceMethod' => 'POST',
                'SmsMethod' => 'POST'
            ]));
            
            $errorMessage = 'Failed to purchase number from Twilio';
            if ($e->getMessage()) {
                $errorMessage = $e->getMessage();
                
                // Provide more specific error messages
                if (str_contains($errorMessage, 'did or area_code is required')) {
                    $errorMessage = 'Phone number is not available for purchase. Please select a different number from the available list.';
                } elseif (str_contains($errorMessage, 'not available')) {
                    $errorMessage = 'Phone number is no longer available. Please select a different number.';
                } elseif (str_contains($errorMessage, 'already owned')) {
                    $errorMessage = 'Phone number is already owned by another account. Please select a different number.';
                } elseif (str_contains($errorMessage, 'Bundle required')) {
                    $errorMessage = 'Bundle verification required for this country. Please create a Bundle in Twilio Console and add the Bundle SID to your environment variables. Bundle SIDs start with "BU" and are different from Address SIDs.';
                } elseif (str_contains($errorMessage, 'AddressSid') || str_contains($errorMessage, 'is invalid')) {
                    if (str_contains($errorMessage, 'AD') && $countryCode === 'MX') {
                        $errorMessage = 'Invalid Bundle or Address SID for Mexico. Please ensure TWILIO_MEXICO_BUNDLE_SID in your .env file is a valid Bundle SID (starts with "BU") and TWILIO_MEXICO_ADDRESS_SID is a valid Address SID (starts with "AD") if required.';
                    } else {
                        $errorMessage = 'Address or Bundle verification issue. Please verify that the Bundle SID (starts with "BU") and Address SID (starts with "AD") are correct in your environment variables.';
                    }
                }
            }
            
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Purchase Number Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while purchasing number'
            ];
        }
    }

    /**
     * Get purchased phone numbers
     */
    public function getPurchasedNumbers($limit = 50)
    {
        try {
            $incomingPhoneNumbers = $this->client->incomingPhoneNumbers
                ->read([], $limit);

            $formattedNumbers = [];
            foreach ($incomingPhoneNumbers as $number) {
                $formattedNumbers[] = [
                    'sid' => $number->sid,
                    'phone_number' => $number->phoneNumber,
                    'friendly_name' => $number->friendlyName,
                    'status' => $number->status,
                    'voice_url' => $number->voiceUrl,
                    'sms_url' => $number->smsUrl
                ];
            }

            Log::info('Twilio Get Purchased Numbers Success: ' . count($formattedNumbers) . ' numbers found');
            return [
                'success' => true,
                'data' => $formattedNumbers
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Get Purchased Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get purchased numbers from Twilio'
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Get Purchased Numbers Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while getting purchased numbers'
            ];
        }
    }

    /**
     * Release a phone number
     */
    public function releaseNumber($phoneNumberSid)
    {
        try {
            Log::info('Twilio Release Number Attempt: ' . $phoneNumberSid);
            
            $this->client->incomingPhoneNumbers($phoneNumberSid)->delete();
            
            Log::info('Twilio Release Number Success: ' . $phoneNumberSid);
            return [
                'success' => true,
                'message' => 'Phone number released successfully'
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio Release Number Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to release number from Twilio: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Twilio Release Number Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service error while releasing number'
            ];
        }
    }

    /**
     * Format phone number to E.164 format with country-specific handling
     */
    private function formatPhoneNumber($phoneNumber, $countryCode = 'US')
    {
        // Remove all non-digit characters except +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // If it doesn't start with +, add country code
        if (!str_starts_with($phoneNumber, '+')) {
            $countryPrefixes = [
                'US' => '+1',
                'CA' => '+1',
                'AU' => '+61',
                'GB' => '+44',
                'MX' => '+52'
            ];
            
            $phoneNumber = ($countryPrefixes[$countryCode] ?? '+1') . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    /**
     * Run comprehensive diagnostics to identify API issues
     */
    public function runDiagnostics()
    {
        $results = [];
        
        // Test 1: Check credentials
        $results['credentials'] = !empty($this->accountSid) && !empty($this->authToken);
        Log::info('Credentials Check: ' . ($results['credentials'] ? 'PASS' : 'FAIL'));
        
        // Test 2: Test API connection
        $results['api_connection'] = $this->testApiConnection();
        
        // Test 3: Test available numbers for different countries
        $countries = ['US'];
        foreach ($countries as $country) {
            $results['available_numbers_' . $country] = $this->testAvailableNumbersEndpoint($country);
        }
        
        // Test 4: Check account status
        try {
            $account = $this->client->api->accounts($this->accountSid)->fetch();
            $results['account_status'] = $account->status;
            $results['account_type'] = $account->type ?? 'unknown';
            Log::info('Account Status: ' . $account->status);
            Log::info('Account Type: ' . ($account->type ?? 'unknown'));
        } catch (\Exception $e) {
            $results['account_status'] = 'error: ' . $e->getMessage();
            Log::error('Account Status Check Failed: ' . $e->getMessage());
        }
        
        Log::info('Twilio Diagnostics Results: ' . json_encode($results));
        return $results;
    }

    /**
     * Test basic API connection
     */
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

    /**
     * Test available numbers endpoint for specific country
     */
    public function testAvailableNumbersEndpoint($countryCode = 'US')
    {
        try {
            Log::info('Testing Available Numbers for: ' . $countryCode);
            
            $numbers = $this->client->availablePhoneNumbers($countryCode)
                ->local
                ->read(['limit' => 1]);
            
            Log::info('Available Numbers Test Successful for ' . $countryCode);
            Log::info('Numbers Found: ' . count($numbers));
            
            if (count($numbers) > 0) {
                Log::info('Sample Number: ' . $numbers[0]->phoneNumber);
            }
            
            return count($numbers) > 0;
        } catch (TwilioException $e) {
            Log::error('Available Numbers Test Failed for ' . $countryCode . ': ' . $e->getMessage());
            Log::error('Error Code: ' . $e->getCode());
            return false;
        }
    }

    /**
     * Check account status and type
     */
    public function checkAccountStatus()
    {
        try {
            $account = $this->client->api->accounts($this->accountSid)->fetch();
            Log::info('Account Status: ' . $account->status);
            Log::info('Account Type: ' . ($account->type ?? 'unknown'));
            return $account;
        } catch (TwilioException $e) {
            Log::error('Account Status Check Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get available countries for this account
     */
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

    /**
     * Test different number types (local, toll-free, mobile)
     */
    public function testNumberTypes($countryCode = 'US')
    {
        $results = [];
        
        try {
            // Test local numbers
            $localNumbers = $this->client->availablePhoneNumbers($countryCode)->local->read(['limit' => 1]);
            $results['local'] = count($localNumbers) > 0;
            Log::info('Local numbers available: ' . (count($localNumbers) > 0 ? 'YES' : 'NO'));
        } catch (TwilioException $e) {
            $results['local'] = false;
            Log::error('Local numbers test failed: ' . $e->getMessage());
        }
        
        try {
            // Test toll-free numbers
            $tollFreeNumbers = $this->client->availablePhoneNumbers($countryCode)->tollFree->read(['limit' => 1]);
            $results['toll_free'] = count($tollFreeNumbers) > 0;
            Log::info('Toll-free numbers available: ' . (count($tollFreeNumbers) > 0 ? 'YES' : 'NO'));
        } catch (TwilioException $e) {
            $results['toll_free'] = false;
            Log::error('Toll-free numbers test failed: ' . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Determine country code from phone number
     */
    private function determineCountryFromPhoneNumber($phoneNumber)
    {
        // Remove any non-digit characters except +
        $cleanNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Country code mapping based on phone number prefixes
        if (preg_match('/^\+1/', $cleanNumber)) {
            return 'US'; // Default to US for +1, could be Canada but US is more common
        } elseif (preg_match('/^\+52/', $cleanNumber)) {
            return 'MX';
        } elseif (preg_match('/^\+61/', $cleanNumber)) {
            return 'AU';
        } elseif (preg_match('/^\+44/', $cleanNumber)) {
            return 'GB';
        } elseif (preg_match('/^\+64/', $cleanNumber)) {
            return 'NZ';
        } elseif (preg_match('/^\+353/', $cleanNumber)) {
            return 'IE';
        } else {
            return 'US'; // Default fallback
        }
    }

    /**
     * Get address SID for country if required
     */
    private function getAddressSidForCountry($countryCode)
    {
        $addressSids = config('services.twilio.address_sids', []);
        
        // Countries that require address verification
        $countriesRequiringAddress = ['AU', 'CA', 'GB', 'NZ', 'IE', 'MX'];
        
        if (in_array($countryCode, $countriesRequiringAddress) && isset($addressSids[$countryCode])) {
            return $addressSids[$countryCode];
        }
        
        return null;
    }

    /**
     * Get bundle SID for country if required
     */
    private function getBundleSidForCountry($countryCode)
    {
        $bundleSids = config('services.twilio.bundle_sids', []);
        
        // Countries that require bundle verification
        $countriesRequiringBundle = ['GB', 'MX'];
        
        if (in_array($countryCode, $countriesRequiringBundle) && isset($bundleSids[$countryCode])) {
            $bundleSid = $bundleSids[$countryCode];
            
            // Validate that the SID is actually a Bundle SID (starts with BU)
            // Address SIDs start with AD and cannot be used as Bundle SIDs
            if (!str_starts_with($bundleSid, 'BU')) {
                Log::error('Twilio Bundle SID Validation: Invalid format - Bundle SID must start with "BU", but got: ' . $bundleSid);
                
                // For Mexico, provide a helpful error message
                if ($countryCode === 'MX') {
                    Log::error('Twilio Mexico Bundle SID Error: The configured Bundle SID appears to be an Address SID. Please ensure TWILIO_MEXICO_BUNDLE_SID in your .env file starts with "BU" (not "AD").');
                }
                
                return null;
            }
            
            Log::info('Twilio Bundle SID Lookup: Found bundle SID ' . $bundleSid . ' for country ' . $countryCode);
            
            // For Mexico, we'll use the bundle SID directly without validation for now
            // since the bundle might be in a different status
            if ($countryCode === 'MX') {
                Log::info('Twilio Bundle SID: Using Mexican bundle SID without validation: ' . $bundleSid);
                return $bundleSid;
            }
            
            // Validate the bundle SID with Twilio for other countries
            if ($this->validateBundleSid($bundleSid)) {
                return $bundleSid;
            } else {
                Log::error('Twilio Bundle SID Validation: Invalid bundle SID ' . $bundleSid . ' for country ' . $countryCode);
                return null;
            }
        }
        
        Log::info('Twilio Bundle SID Lookup: No bundle SID found for country ' . $countryCode);
        return null;
    }



    /**
     * Validate bundle SID with Twilio
     */
    private function validateBundleSid($bundleSid)
    {
        try {
            $bundle = $this->client->numbers->v2->regulatoryCompliance->bundles($bundleSid)->fetch();
            Log::info('Twilio Bundle Validation: Bundle ' . $bundleSid . ' is valid - Status: ' . $bundle->status);
            return $bundle->status === 'APPROVED';
        } catch (TwilioException $e) {
            Log::error('Twilio Bundle Validation Error: ' . $e->getMessage() . ' for bundle SID ' . $bundleSid);
            return false;
        }
    }
} 