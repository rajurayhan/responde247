<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TwilioSDKTest extends TestCase
{
    use RefreshDatabase;

    public function test_twilio_sdk_available_numbers()
    {
        $twilioService = app(TwilioService::class);
        
        $result = $twilioService->getAvailableNumbers('US', 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        
        if ($result['success']) {
            $this->assertIsArray($result['data']);
            
            if (!empty($result['data'])) {
                $number = $result['data'][0];
                $this->assertArrayHasKey('phone_number', $number);
                $this->assertArrayHasKey('capabilities', $number);
                $this->assertArrayHasKey('voice', $number['capabilities']);
                $this->assertArrayHasKey('sms', $number['capabilities']);
            }
        }
    }

    public function test_twilio_sdk_purchased_numbers()
    {
        $twilioService = app(TwilioService::class);
        
        $result = $twilioService->getPurchasedNumbers(5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        
        if ($result['success']) {
            $this->assertIsArray($result['data']);
            
            if (!empty($result['data'])) {
                $number = $result['data'][0];
                $this->assertArrayHasKey('sid', $number);
                $this->assertArrayHasKey('phone_number', $number);
                $this->assertArrayHasKey('status', $number);
            }
        }
    }

    public function test_twilio_sdk_error_handling()
    {
        $twilioService = app(TwilioService::class);
        
        // Test with invalid phone number
        $result = $twilioService->purchaseNumber('invalid-number');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        
        // Should fail due to invalid number format
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }
} 