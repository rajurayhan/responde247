<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class TwilioPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_get_available_phone_numbers()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')->andReturn([
                'success' => true,
                'data' => [
                    [
                        'phone_number' => '+1234567890',
                        'friendly_name' => '+1 (234) 567-890',
                        'locality' => 'New York',
                        'region' => 'NY',
                        'country' => 'US'
                    ],
                    [
                        'phone_number' => '+1234567891',
                        'friendly_name' => '+1 (234) 567-891',
                        'locality' => 'Los Angeles',
                        'region' => 'CA',
                        'country' => 'US'
                    ]
                ]
            ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'phone_number' => '+1234567890',
                    'friendly_name' => '+1 (234) 567-890',
                    'locality' => 'New York',
                    'region' => 'NY',
                    'country' => 'US'
                ],
                [
                    'phone_number' => '+1234567891',
                    'friendly_name' => '+1 (234) 567-891',
                    'locality' => 'Los Angeles',
                    'region' => 'CA',
                    'country' => 'US'
                ]
            ]
        ]);
    }

    public function test_can_get_available_phone_numbers_with_area_code()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getAvailableNumbers')->with('US', 10, '212')->andReturn([
                'success' => true,
                'data' => [
                    [
                        'phone_number' => '+1212567890',
                        'friendly_name' => '+1 (212) 567-890',
                        'locality' => 'New York',
                        'region' => 'NY',
                        'country' => 'US'
                    ]
                ]
            ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?area_code=212');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'phone_number' => '+1212567890',
                    'friendly_name' => '+1 (212) 567-890',
                    'locality' => 'New York',
                    'region' => 'NY',
                    'country' => 'US'
                ]
            ]
        ]);
    }

    public function test_can_purchase_phone_number()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('purchaseNumber')->andReturn([
                'success' => true,
                'data' => [
                    'sid' => 'PN1234567890',
                    'phone_number' => '+1234567890',
                    'friendly_name' => '+1 (234) 567-890',
                    'status' => 'active'
                ]
            ]);
        });

        $response = $this->actingAs($user)->postJson('/api/twilio/purchase-number', [
            'phone_number' => '+1234567890'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Phone number purchased successfully',
            'data' => [
                'sid' => 'PN1234567890',
                'phone_number' => '+1234567890',
                'friendly_name' => '+1 (234) 567-890',
                'status' => 'active'
            ]
        ]);
    }

    public function test_can_get_purchased_phone_numbers()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('getPurchasedNumbers')->andReturn([
                'success' => true,
                'data' => [
                    [
                        'sid' => 'PN1234567890',
                        'phone_number' => '+1234567890',
                        'friendly_name' => '+1 (234) 567-890',
                        'status' => 'active'
                    ]
                ]
            ]);
        });

        $response = $this->actingAs($user)->getJson('/api/twilio/purchased-numbers');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'sid' => 'PN1234567890',
                    'phone_number' => '+1234567890',
                    'friendly_name' => '+1 (234) 567-890',
                    'status' => 'active'
                ]
            ]
        ]);
    }

    public function test_can_release_phone_number()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('releaseNumber')->andReturn([
                'success' => true,
                'message' => 'Phone number released successfully'
            ]);
        });

        $response = $this->actingAs($user)->deleteJson('/api/twilio/release-number', [
            'phone_number_sid' => 'PN1234567890'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Phone number released successfully'
        ]);
    }

    public function test_phone_number_purchase_validation()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/api/twilio/purchase-number', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number']);
    }

    public function test_phone_number_release_validation()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->deleteJson('/api/twilio/release-number', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number_sid']);
    }

    public function test_area_code_validation()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?area_code=1234');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['area_code']);
    }
} 