<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Services\VapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class WebhookUrlEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the VapiService
        $this->mock(VapiService::class, function ($mock) {
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
                ],
                'metadata' => [
                    'company_name' => 'Test Company',
                    'industry' => 'Technology'
                ]
            ]);
            
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
    }

    public function test_webhook_url_is_loaded_in_edit_form()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        // Create an assistant with webhook URL
        $assistant = Assistant::factory()->create([
            'user_id' => $user->id,
            'vapi_assistant_id' => 'assistant_test_123',
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents'
        ]);

        // Get the assistant data (simulating the edit form load)
        $response = $this->actingAs($user)->getJson("/api/assistants/{$assistant->vapi_assistant_id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Verify webhook URL is included in the response
        $this->assertEquals('https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents', $data['webhook_url']);
        
        // Verify webhook URL is also in vapi_data metadata
        $this->assertEquals('https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents', $data['vapi_data']['metadata']['webhook_url']);
    }

    public function test_webhook_url_default_value_in_create_form()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        // Test that the default webhook URL is set when creating a new assistant
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
                // webhook_url not provided, should use default
            ]
        ]);

        $response->assertStatus(201);
        
        // Verify the default webhook URL was saved
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents'
        ]);
    }
} 