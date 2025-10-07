<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\VapiService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AdminAssistantCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_create_assistant_without_subscription_limits()
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
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created with admin as the user
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'user_id' => $admin->id,
            'created_by' => $admin->id
        ]);
    }

    public function test_admin_can_create_assistant_for_other_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user']);

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
                'country' => 'United States'
            ],
            'user_id' => $regularUser->id
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created for the specified user
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'user_id' => $regularUser->id,
            'created_by' => $admin->id
        ]);
    }

    public function test_regular_user_still_has_subscription_limits()
    {
        $user = User::factory()->create(['role' => 'user']);

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
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
        ]);
    }
} 