<?php

namespace App\Http\Controllers;

use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TwilioController extends Controller
{
    private $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Get available phone numbers from Twilio
     */
    public function getAvailableNumbers(Request $request): JsonResponse
    {
        $request->validate([
            'country_code' => 'string|max:2',
            'country' => 'string|in:United States,Canada,Australia,United Kingdom,Mexico',
            'limit' => 'integer|min:1|max:50',
            'area_code' => 'nullable|string|max:3'
        ]);

        $countryCode = $request->input('country_code', 'US');
        $country = $request->input('country');
        $limit = $request->input('limit', 10);
        $areaCode = $request->input('area_code');

        // Map country names to Twilio country codes
        $countryCodeMap = [
            'United States' => 'US',
            'Canada' => 'CA',
            'Australia' => 'AU',
            'United Kingdom' => 'GB',
            'Mexico' => 'MX'
        ];

        // Use country parameter if provided, otherwise use country_code
        if ($country && isset($countryCodeMap[$country])) {
            $countryCode = $countryCodeMap[$country];
        }

        // Validate area code for countries that support it
        if ($areaCode && !$this->validateAreaCode($areaCode, $countryCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Area code is not supported for the selected country. Only US, CA, AU, GB, and MX support area codes.'
            ], 422);
        }

        $result = $this->twilioService->getAvailableNumbers($countryCode, $limit, $areaCode);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    /**
     * Purchase a phone number from Twilio
     */
    public function purchaseNumber(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'country_code' => 'nullable|string|max:2'
        ]);

        $phoneNumber = $request->input('phone_number');
        $countryCode = $request->input('country_code');

        $result = $this->twilioService->purchaseNumber($phoneNumber, $countryCode);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => 'Phone number purchased successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    /**
     * Get purchased phone numbers
     */
    public function getPurchasedNumbers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100'
        ]);

        $limit = $request->input('limit', 50);

        $result = $this->twilioService->getPurchasedNumbers($limit);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    /**
     * Release a phone number
     */
    public function releaseNumber(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number_sid' => 'required|string'
        ]);

        $phoneNumberSid = $request->input('phone_number_sid');

        $result = $this->twilioService->releaseNumber($phoneNumberSid);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    /**
     * Validate area code for specific country
     */
    private function validateAreaCode($areaCode, $countryCode)
    {
        if (!$areaCode) return true;
        
        // Only US, CA, AU, GB, and MX support area codes
        if (!in_array($countryCode, ['US', 'CA', 'AU', 'GB', 'MX'])) {
            return false;
        }
        
        // Validate area code format (2-3 digits for MX, 3 digits for US, CA, AU, GB)
        if ($countryCode === 'MX') {
            return preg_match('/^\d{2,3}$/', $areaCode);
        }
        return preg_match('/^\d{3}$/', $areaCode);
    }
} 