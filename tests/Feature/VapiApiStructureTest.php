<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\VapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class VapiApiStructureTest extends TestCase
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
        });
    }

    public function test_vapi_api_structure_with_webhook_url()
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
        
        // Verify the assistant was created with webhook URL
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-nora-vapi-call-ends'
        ]);
    }

    public function test_vapi_api_structure_without_webhook_url()
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
                'services_products' => 'Software Development'
                // No webhook_url
            ]
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created with default webhook URL
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents'
        ]);
    }
} 