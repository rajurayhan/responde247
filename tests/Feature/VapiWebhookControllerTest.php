<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Models\CallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class VapiWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Assistant $assistant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and assistant
        $this->user = User::factory()->create();
        $this->assistant = Assistant::factory()->create([
            'user_id' => $this->user->id,
            'vapi_assistant_id' => 'test_assistant_123'
        ]);
    }

    public function test_webhook_returns_call_log_data_for_call_start()
    {
        $payload = [
            'message' => [
                'type' => 'call-start',
                'call' => ['id' => 'test_call_123'],
                'assistant' => ['id' => 'test_assistant_123']
            ],
            'callId' => 'test_call_123',
            'phoneNumber' => '+1234567890',
            'callerNumber' => '+0987654321',
            'status' => 'initiated',
            'direction' => 'inbound'
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'call_id',
                    'assistant_id',
                    'user_id',
                    'reseller_id',
                    'phone_number',
                    'caller_number',
                    'status',
                    'direction',
                    'start_time',
                    'end_time',
                    'duration',
                    'formatted_duration',
                    'transcript',
                    'summary',
                    'cost',
                    'currency',
                    'formatted_cost',
                    'call_record_file_name',
                    'has_recording',
                    'public_audio_url',
                    'metadata',
                    'webhook_data',
                    'created_at',
                    'updated_at'
                ],
                'event_type',
                'call_id',
                'assistant_id'
            ])
            ->assertJson([
                'success' => true,
                'event_type' => 'call-start',
                'call_id' => 'test_call_123',
                'assistant_id' => 'test_assistant_123',
                'data' => [
                    'call_id' => 'test_call_123',
                    'assistant_id' => $this->assistant->id,
                    'user_id' => $this->user->id,
                    'phone_number' => '+1234567890',
                    'caller_number' => '+0987654321',
                    'status' => 'initiated',
                    'direction' => 'inbound'
                ]
            ]);

        // Verify call log was created in database
        $this->assertDatabaseHas('call_logs', [
            'call_id' => 'test_call_123',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'phone_number' => '+1234567890',
            'caller_number' => '+0987654321',
            'status' => 'initiated',
            'direction' => 'inbound'
        ]);
    }

    public function test_webhook_returns_call_log_data_for_call_end()
    {
        // First create a call log
        $callLog = CallLog::create([
            'call_id' => 'test_call_123',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'reseller_id' => $this->user->reseller_id,
            'phone_number' => '+1234567890',
            'caller_number' => '+0987654321',
            'status' => 'initiated',
            'direction' => 'inbound',
            'start_time' => now()->subMinutes(5)
        ]);

        $payload = [
            'message' => [
                'type' => 'call-end',
                'call' => ['id' => 'test_call_123'],
                'assistant' => ['id' => 'test_assistant_123']
            ],
            'callId' => 'test_call_123',
            'status' => 'completed',
            'duration' => 300,
            'transcript' => 'Test transcript',
            'summary' => 'Test summary',
            'cost' => 0.05,
            'currency' => 'USD'
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'call_id',
                    'assistant_id',
                    'user_id',
                    'reseller_id',
                    'phone_number',
                    'caller_number',
                    'status',
                    'direction',
                    'start_time',
                    'end_time',
                    'duration',
                    'formatted_duration',
                    'transcript',
                    'summary',
                    'cost',
                    'currency',
                    'formatted_cost',
                    'call_record_file_name',
                    'has_recording',
                    'public_audio_url',
                    'metadata',
                    'webhook_data',
                    'created_at',
                    'updated_at'
                ],
                'event_type',
                'call_id',
                'assistant_id'
            ])
            ->assertJson([
                'success' => true,
                'event_type' => 'call-end',
                'call_id' => 'test_call_123',
                'assistant_id' => 'test_assistant_123',
                'data' => [
                    'call_id' => 'test_call_123',
                    'assistant_id' => $this->assistant->id,
                    'user_id' => $this->user->id,
                    'status' => 'completed',
                    'duration' => 300,
                    'transcript' => 'Test transcript',
                    'summary' => 'Test summary',
                    'cost' => 0.05,
                    'currency' => 'USD'
                ]
            ]);

        // Verify call log was updated in database
        $this->assertDatabaseHas('call_logs', [
            'call_id' => 'test_call_123',
            'status' => 'completed',
            'duration' => 300,
            'transcript' => 'Test transcript',
            'summary' => 'Test summary',
            'cost' => 0.05,
            'currency' => 'USD'
        ]);
    }

    public function test_webhook_returns_null_data_when_call_log_not_found()
    {
        $payload = [
            'message' => [
                'type' => 'call-update',
                'call' => ['id' => 'nonexistent_call'],
                'assistant' => ['id' => 'test_assistant_123']
            ],
            'callId' => 'nonexistent_call',
            'status' => 'in-progress'
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
                'event_type' => 'call-update',
                'call_id' => 'nonexistent_call',
                'assistant_id' => 'test_assistant_123'
            ]);
    }

    public function test_webhook_returns_error_for_missing_required_fields()
    {
        $payload = [
            'message' => [
                'type' => 'call-start',
                'call' => ['id' => 'test_call_123']
                // Missing assistant id
            ],
            'callId' => 'test_call_123'
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
    }

    public function test_webhook_returns_error_for_unknown_assistant()
    {
        $payload = [
            'message' => [
                'type' => 'call-start',
                'call' => ['id' => 'test_call_123'],
                'assistant' => ['id' => 'unknown_assistant']
            ],
            'callId' => 'test_call_123'
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Assistant not found'
            ]);
    }
}
