<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Models\CallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class PublicAudioControllerTest extends TestCase
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

    public function test_serves_existing_audio_file()
    {
        // Create a call log with recording
        $callLog = CallLog::create([
            'call_id' => 'test_call_123',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'reseller_id' => $this->user->reseller_id,
            'call_record_file_name' => 'testfile123.wav',
            'status' => 'completed',
            'metadata' => [
                'recording_url' => 'https://example.com/recording.wav'
            ]
        ]);

        // Create a fake audio file
        Storage::disk('public')->put('recordings/testfile123.wav', 'fake audio content');

        $response = $this->get('/p/testfile123.wav');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'audio/wav');
    }

    public function test_downloads_missing_audio_file()
    {
        // Mock HTTP request to Vapi
        Http::fake([
            'https://example.com/recording.wav' => Http::response('fake audio content', 200, [
                'Content-Type' => 'audio/wav'
            ])
        ]);

        // Create a call log with recording URL but no local file
        $callLog = CallLog::create([
            'call_id' => 'test_call_123',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'reseller_id' => $this->user->reseller_id,
            'call_record_file_name' => 'testfile456.wav',
            'status' => 'completed',
            'metadata' => [
                'recording_url' => 'https://example.com/recording.wav'
            ]
        ]);

        // Ensure the file doesn't exist
        $this->assertFalse(Storage::disk('public')->exists('recordings/testfile456.wav'));

        $response = $this->get('/p/testfile456.wav');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'audio/wav');
        
        // Verify the file was downloaded and stored
        $this->assertTrue(Storage::disk('public')->exists('recordings/testfile456.wav'));
        $this->assertEquals('fake audio content', Storage::disk('public')->get('recordings/testfile456.wav'));
    }

    public function test_returns_404_for_invalid_filename()
    {
        $response = $this->get('/p/invalid-filename.mp3');
        $response->assertStatus(404);
    }

    public function test_returns_404_when_call_log_not_found()
    {
        $response = $this->get('/p/nonexistent.wav');
        $response->assertStatus(404);
    }

    public function test_returns_404_when_no_recording_url()
    {
        // Create a call log without recording URL
        $callLog = CallLog::create([
            'call_id' => 'test_call_123',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'reseller_id' => $this->user->reseller_id,
            'call_record_file_name' => 'testfile789.wav',
            'status' => 'completed',
            'metadata' => [] // No recording_url
        ]);

        $response = $this->get('/p/testfile789.wav');
        $response->assertStatus(404);
    }

    public function test_handles_download_failure_gracefully()
    {
        // Mock HTTP request to return error
        Http::fake([
            'https://example.com/recording.wav' => Http::response('', 404)
        ]);

        // Create a call log with invalid recording URL
        $callLog = CallLog::create([
            'call_id' => 'test_call_123',
            'assistant_id' => $this->assistant->id,
            'user_id' => $this->user->id,
            'reseller_id' => $this->user->reseller_id,
            'call_record_file_name' => 'testfile999.wav',
            'status' => 'completed',
            'metadata' => [
                'recording_url' => 'https://example.com/recording.wav'
            ]
        ]);

        $response = $this->get('/p/testfile999.wav');
        $response->assertStatus(404);
    }
}
