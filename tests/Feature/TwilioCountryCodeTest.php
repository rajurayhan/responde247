<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class TwilioCountryCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_united_states_country_code_mapping()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')
                ->with('US', 10, null)
                ->andReturn([
                    'success' => true,
                    'data' => [['phone_number' => '+1234567890']]
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=United%20States');
        $response->assertStatus(200);
    }



    public function test_canada_country_code_mapping()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')
                ->with('CA', 10, null)
                ->andReturn([
                    'success' => true,
                    'data' => [['phone_number' => '+1234567890']]
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=Canada');
        $response->assertStatus(200);
    }

    public function test_australia_country_code_mapping()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')
                ->with('AU', 10, null)
                ->andReturn([
                    'success' => true,
                    'data' => [['phone_number' => '+6123456789']]
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=Australia');
        $response->assertStatus(200);
    }





    public function test_area_code_validation_for_supported_countries()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Test that area codes work for US, Canada, and Australia
        $supportedCountries = ['United States', 'Canada', 'Australia'];

        foreach ($supportedCountries as $country) {
            $this->mock(TwilioService::class, function ($mock) {
                $mock->shouldReceive('getAvailableNumbers')
                    ->andReturn([
                        'success' => true,
                        'data' => []
                    ]);
            });

            $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=' . urlencode($country) . '&area_code=212');

            $response->assertStatus(200);
        }
    }

    public function test_area_code_validation_for_unsupported_countries()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Test that area codes are rejected for unsupported countries
        $unsupportedCountries = ['United Kingdom', 'New Zealand', 'Ireland'];

        foreach ($unsupportedCountries as $country) {
            $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=' . urlencode($country) . '&area_code=212');

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['country']);
        }
    }

    public function test_area_code_format_validation()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Test invalid area code formats
        $invalidAreaCodes = ['12', '1234', 'abc', '12a'];

        foreach ($invalidAreaCodes as $areaCode) {
            $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=United%20States&area_code=' . $areaCode);

            $response->assertStatus(422);
        }
    }

    public function test_country_parameter_takes_precedence_over_country_code()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService to expect CA (Canada) instead of US
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')
                ->with('CA', 10, null) // Should use CA from country parameter, not US from country_code
                ->andReturn([
                    'success' => true,
                    'data' => []
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country_code=US&country=Canada');

        $response->assertStatus(200);
    }

    public function test_default_country_code_is_us()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService to expect US as default
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')
                ->with('US', 10, null) // Should default to US
                ->andReturn([
                    'success' => true,
                    'data' => []
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers');

        $response->assertStatus(200);
    }

    public function test_invalid_country_parameter_is_rejected()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=InvalidCountry');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['country']);
    }

    public function test_twilio_service_logs_country_information()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')
                ->with('CA', 10, '416') // Canada with area code
                ->andReturn([
                    'success' => true,
                    'data' => []
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=Canada&area_code=416');

        $response->assertStatus(200);
    }
} 