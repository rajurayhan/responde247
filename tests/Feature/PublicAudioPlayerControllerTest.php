<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Models\CallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicAudioPlayerControllerTest extends TestCase
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

    public function test_displays_public_audio_player_page()
    {
        // Create a call log with recording
        $callLog = CallLog::create([
            'call_id' => 'test_call_123',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'reseller_id' => $this->user->reseller_id,
            'call_record_file_name' => 'testfile123.wav',
            'status' => 'completed',
            'duration' => 300,
            'phone_number' => '+1234567890',
            'caller_number' => '+0987654321',
            'direction' => 'inbound',
            'transcript' => 'Test transcript content',
            'summary' => 'Test call summary',
            'metadata' => [
                'recording_url' => 'https://example.com/recording.wav'
            ]
        ]);

        $response = $this->get('/play/testfile123.wav');

        $response->assertStatus(200);
        $response->assertViewIs('public.audio-player');
        $response->assertViewHas('callInfo');
        
        // Check that call information is displayed
        $response->assertSee('test_call_123');
        $response->assertSee('Test transcript content');
        $response->assertSee('Test call summary');
        $response->assertSee('+1234567890');
        $response->assertSee('+0987654321');
        
        // Check that audio player is included
        $response->assertSee('audio-player');
        $response->assertSee('Call Recording');
    }

    public function test_returns_404_for_invalid_filename()
    {
        $response = $this->get('/play/invalid-filename.mp3');
        $response->assertStatus(404);
    }

    public function test_returns_404_when_call_log_not_found()
    {
        $response = $this->get('/play/nonexistent.wav');
        $response->assertStatus(404);
    }

    public function test_displays_call_without_recording()
    {
        // Create a call log without recording
        $callLog = CallLog::create([
            'call_id' => 'test_call_456',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'reseller_id' => $this->user->reseller_id,
            'call_record_file_name' => null,
            'status' => 'completed',
            'duration' => 180,
            'transcript' => 'Test transcript without recording',
            'metadata' => []
        ]);

        $response = $this->get('/play/testfile456.wav');

        $response->assertStatus(404); // Should return 404 since no recording file
    }
}
