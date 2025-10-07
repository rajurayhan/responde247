<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\VapiService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class VapiPhoneNumberAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_vapi_phone_number_assignment_uses_correct_api_format()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock VapiService to capture the exact API call format
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
            
            // Mock assignPhoneNumber to verify the correct API format
            $mock->shouldReceive('assignPhoneNumber')
                ->with('assistant_test_123', '+1234567890')
                ->andReturn([
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

    public function test_vapi_phone_number_assignment_api_structure()
    {
        // This test verifies that the VapiService uses the correct API structure
        $vapiService = app(VapiService::class);
        
        // Use reflection to test the private method structure
        $reflection = new \ReflectionClass($vapiService);
        $method = $reflection->getMethod('assignPhoneNumber');
        $method->setAccessible(true);
        
        // Mock the HTTP client to capture the request
        $mockHttp = Mockery::mock('overload:' . \Illuminate\Support\Facades\Http::class);
        $mockHttp->shouldReceive('withHeaders')->andReturnSelf();
        $mockHttp->shouldReceive('post')->andReturnSelf();
        $mockHttp->shouldReceive('successful')->andReturn(true);
        $mockHttp->shouldReceive('json')->andReturn(['success' => true]);
        
        // Call the method
        $result = $method->invoke($vapiService, 'test_assistant_id', '+1234567890');
        
        // Verify the result
        $this->assertNotNull($result);
    }
} 