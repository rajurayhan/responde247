<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\VapiService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AustralianPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_australian_phone_number_purchase_with_address_error()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o'
                ],
                'voice' => [
                    'provider' => 'vapi',
                    'voiceId' => 'elliot'
                ]
            ]);
        });

        // Mock TwilioService to return address error for Australian numbers
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('purchaseNumber')
                ->with('+61412345678', 'AU')
                ->andReturn([
                    'success' => false,
                    'message' => 'Address verification required for this country. For Australian numbers, please contact support to set up address verification in your Twilio account.'
                ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'Australia'
            ],
            'selected_phone_number' => '+61412345678'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to purchase phone number: Address verification required for this country. For Australian numbers, please contact support to set up address verification in your Twilio account.'
        ]);
    }

    public function test_us_phone_number_purchase_works_normally()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o'
                ],
                'voice' => [
                    'provider' => 'vapi',
                    'voiceId' => 'elliot'
                ]
            ]);
            
            $mock->shouldReceive('assignPhoneNumber')->andReturn([
                'success' => true,
                'message' => 'Phone number assigned successfully'
            ]);
        });

        // Mock TwilioService to return success for US numbers
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('purchaseNumber')
                ->with('+1234567890', 'US')
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'sid' => 'PN1234567890',
                        'phone_number' => '+1234567890',
                        'friendly_name' => '+1 (234) 567-890',
                        'status' => 'active'
                    ]
                ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ],
            'selected_phone_number' => '+1234567890'
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created with phone number
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'phone_number' => '+1234567890'
        ]);
    }

    public function test_country_code_mapping_works_correctly()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o'
                ],
                'voice' => [
                    'provider' => 'vapi',
                    'voiceId' => 'elliot'
                ]
            ]);
        });

        // Mock TwilioService to verify correct country code is passed
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('purchaseNumber')
                ->with('+447123456789', 'CA')
                ->andReturn([
                    'success' => false,
                    'message' => 'Test message'
                ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'Canada'
            ],
            'selected_phone_number' => '+447123456789'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to purchase phone number: Test message'
        ]);
    }

    public function test_default_country_code_fallback()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o'
                ],
                'voice' => [
                    'provider' => 'vapi',
                    'voiceId' => 'elliot'
                ]
            ]);
        });

        // Mock TwilioService to verify default US country code is used
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('purchaseNumber')
                ->with('+1234567890', 'US')
                ->andReturn([
                    'success' => false,
                    'message' => 'Test message'
                ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States' // Default country
            ],
            'selected_phone_number' => '+1234567890'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to purchase phone number: Test message'
        ]);
    }
} 