<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Services\VapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class WebhookUrlSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_syncs_webhook_url_from_vapi_when_local_is_empty()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        // Create an assistant with no webhook URL in local database
        $assistant = Assistant::factory()->create([
            'user_id' => $user->id,
            'vapi_assistant_id' => 'assistant_test_123',
            'webhook_url' => null // No webhook URL in local DB
        ]);

        // Mock VapiService to return data with webhook URL
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('getAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'server' => [
                    'url' => 'https://n8n.cloud.lhgdev.com/webhook/vapi-webhook-url'
                ],
                'metadata' => [
                    'company_name' => 'Test Company'
                ]
            ]);
        });

        // Get the assistant data (this should trigger sync)
        $response = $this->actingAs($user)->getJson("/api/assistants/{$assistant->vapi_assistant_id}");

        $response->assertStatus(200);
        
        // Verify the webhook URL was synced from Vapi to local database
        $this->assertDatabaseHas('assistants', [
            'id' => $assistant->id,
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/vapi-webhook-url'
        ]);
        
        $data = $response->json('data');
        
        // Verify webhook URL is included in the response
        $this->assertEquals('https://n8n.cloud.lhgdev.com/webhook/vapi-webhook-url', $data['webhook_url']);
        
        // Verify webhook URL is also in vapi_data metadata
        $this->assertEquals('https://n8n.cloud.lhgdev.com/webhook/vapi-webhook-url', $data['vapi_data']['metadata']['webhook_url']);
    }

    public function test_keeps_local_webhook_url_when_both_exist()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        // Create an assistant with webhook URL in local database
        $assistant = Assistant::factory()->create([
            'user_id' => $user->id,
            'vapi_assistant_id' => 'assistant_test_123',
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/local-webhook-url'
        ]);

        // Mock VapiService to return data with different webhook URL
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('getAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'server' => [
                    'url' => 'https://n8n.cloud.lhgdev.com/webhook/vapi-webhook-url'
                ],
                'metadata' => [
                    'company_name' => 'Test Company'
                ]
            ]);
        });

        // Get the assistant data
        $response = $this->actingAs($user)->getJson("/api/assistants/{$assistant->vapi_assistant_id}");

        $response->assertStatus(200);
        
        // Verify the local webhook URL was kept (not synced from Vapi)
        $this->assertDatabaseHas('assistants', [
            'id' => $assistant->id,
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/local-webhook-url'
        ]);
        
        $data = $response->json('data');
        
        // Verify local webhook URL is used in response
        $this->assertEquals('https://n8n.cloud.lhgdev.com/webhook/local-webhook-url', $data['webhook_url']);
    }

    public function test_syncs_webhook_url_during_update_when_local_is_empty()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        // Create an assistant with no webhook URL in local database
        $assistant = Assistant::factory()->create([
            'user_id' => $user->id,
            'vapi_assistant_id' => 'assistant_test_123',
            'webhook_url' => null
        ]);

        // Mock VapiService for update
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('updateAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Updated Assistant',
                'server' => [
                    'url' => 'https://n8n.cloud.lhgdev.com/webhook/updated-webhook-url'
                ],
                'metadata' => [
                    'company_name' => 'Updated Company'
                ]
            ]);
        });

        // Update the assistant
        $response = $this->actingAs($user)->putJson("/api/assistants/{$assistant->vapi_assistant_id}", [
            'name' => 'Updated Assistant',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Updated system prompt'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Updated first message',
            'endCallMessage' => 'Updated end call message',
            'metadata' => [
                'company_name' => 'Updated Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development'
                // No webhook_url provided
            ]
        ]);

        $response->assertStatus(200);
        
        // Verify the webhook URL was synced from Vapi response
        $this->assertDatabaseHas('assistants', [
            'id' => $assistant->id,
            'webhook_url' => 'https://n8n.cloud.lhgdev.com/webhook/updated-webhook-url'
        ]);
    }
} 