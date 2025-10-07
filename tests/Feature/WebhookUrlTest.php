<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Services\VapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class WebhookUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the VapiService
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
            
            $mock->shouldReceive('updateAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Updated Assistant',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o'
                ],
                'voice' => [
                    'provider' => 'vapi',
                    'voiceId' => 'elliot'
                ]
            ]);
            
            $mock->shouldReceive('getAssistant')->andReturn([
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
    }

    public function test_can_create_assistant_with_webhook_url()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
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
                'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-nora-vapi-call-ends'
            ]
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-nora-vapi-call-ends'
        ]);
    }

    public function test_can_update_assistant_webhook_url()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $assistant = Assistant::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://old-webhook.com',
            'vapi_assistant_id' => 'assistant_test_123'
        ]);

        $response = $this->actingAs($user)->putJson("/api/assistants/{$assistant->vapi_assistant_id}", [
            'name' => 'Updated Assistant',
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
                'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-nora-vapi-call-ends'
            ]
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('assistants', [
            'id' => $assistant->id,
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-nora-vapi-call-ends'
        ]);
    }

    public function test_webhook_url_validation()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
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
                'webhook_url' => 'invalid-url'
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['metadata.webhook_url']);
    }

    public function test_webhook_url_can_be_null()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
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
                'webhook_url' => null
            ]
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents'
        ]);
    }
} 