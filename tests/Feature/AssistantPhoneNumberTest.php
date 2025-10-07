<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\VapiService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AssistantPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_assistant_with_selected_phone_number()
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
                'country' => 'United States',
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

    public function test_can_create_assistant_without_phone_number()
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
                'country' => 'United States',
                'services_products' => 'Software Development'
            ]
            // No selected_phone_number
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created without phone number
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'phone_number' => null
        ]);
    }

    public function test_phone_number_purchase_failure_handling()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock TwilioService to return failure
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('purchaseNumber')->andReturn([
                'success' => false,
                'message' => 'Insufficient funds'
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
                'country' => 'United States',
                'services_products' => 'Software Development'
            ],
            'selected_phone_number' => '+1234567890'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to purchase phone number: Insufficient funds'
        ]);
    }
} 