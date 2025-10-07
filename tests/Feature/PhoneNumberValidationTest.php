<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\VapiService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class PhoneNumberValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_assistant_with_twilio_selected_phone_number()
    {
        $admin = User::factory()->create(['role' => 'admin']);

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

        $response = $this->actingAs($admin)->postJson('/api/assistants', [
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
                'services_products' => 'Software Development'
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

    public function test_can_create_assistant_with_manually_entered_phone_number()
    {
        $admin = User::factory()->create(['role' => 'admin']);

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

        $response = $this->actingAs($admin)->postJson('/api/assistants', [
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
                'assistant_phone_number' => '+1987654321'
            ]
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created with phone number
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'phone_number' => '+1987654321'
        ]);
    }

    public function test_can_create_assistant_without_phone_number()
    {
        $admin = User::factory()->create(['role' => 'admin']);

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

        $response = $this->actingAs($admin)->postJson('/api/assistants', [
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
                'services_products' => 'Software Development'
            ]
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created without phone number
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'phone_number' => null
        ]);
    }

    public function test_validation_error_for_invalid_phone_number_format()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/assistants', [
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
                'assistant_phone_number' => 'invalid-phone-number'
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['metadata.assistant_phone_number']);
    }
} 