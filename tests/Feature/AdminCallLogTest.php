<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Models\CallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminCallLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_all_call_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $assistant1 = Assistant::factory()->create(['user_id' => $user1->id]);
        $assistant2 = Assistant::factory()->create(['user_id' => $user2->id]);

        CallLog::factory()->create([
            'assistant_id' => $assistant1->id,
            'user_id' => $user1->id,
            'status' => 'completed'
        ]);

        CallLog::factory()->create([
            'assistant_id' => $assistant2->id,
            'user_id' => $user2->id,
            'status' => 'failed'
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/call-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta'
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_filter_call_logs_by_assistant()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $assistant1 = Assistant::factory()->create(['user_id' => $user1->id]);
        $assistant2 = Assistant::factory()->create(['user_id' => $user2->id]);

        CallLog::factory()->create([
            'assistant_id' => $assistant1->id,
            'user_id' => $user1->id,
            'status' => 'completed'
        ]);

        CallLog::factory()->create([
            'assistant_id' => $assistant2->id,
            'user_id' => $user2->id,
            'status' => 'failed'
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/call-logs?assistant_id=' . $assistant1->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($assistant1->id, $response->json('data.0.assistant_id'));
    }

    public function test_admin_can_filter_call_logs_by_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $assistant = Assistant::factory()->create(['user_id' => $user->id]);

        CallLog::factory()->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'completed'
        ]);

        CallLog::factory()->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'failed'
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/call-logs?status=completed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('completed', $response->json('data.0.status'));
    }

    public function test_admin_can_get_call_logs_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $assistant = Assistant::factory()->create(['user_id' => $user->id]);

        CallLog::factory()->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'duration' => 120
        ]);

        CallLog::factory()->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'failed',
            'duration' => 30
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/call-logs/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalCalls',
                    'completedCalls',
                    'failedCalls',
                    'averageDuration',
                    'statusBreakdown',
                    'directionBreakdown',
                    'topAssistants',
                    'costAnalysis'
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['totalCalls']);
        $this->assertEquals(1, $data['completedCalls']);
        $this->assertEquals(1, $data['failedCalls']);
    }

    public function test_admin_can_filter_statistics_by_assistant()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $assistant1 = Assistant::factory()->create(['user_id' => $user1->id]);
        $assistant2 = Assistant::factory()->create(['user_id' => $user2->id]);

        CallLog::factory()->create([
            'assistant_id' => $assistant1->id,
            'user_id' => $user1->id,
            'status' => 'completed'
        ]);

        CallLog::factory()->create([
            'assistant_id' => $assistant2->id,
            'user_id' => $user2->id,
            'status' => 'failed'
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/call-logs/stats?assistant_id=' . $assistant1->id);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['totalCalls']);
        $this->assertEquals(1, $data['completedCalls']);
    }

    public function test_admin_can_view_specific_call_log()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $assistant = Assistant::factory()->create(['user_id' => $user->id]);

        $callLog = CallLog::factory()->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/call-logs/' . $callLog->call_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'call_id',
                    'assistant_id',
                    'user_id',
                    'status',
                    'assistant'
                ]
            ]);

        $this->assertEquals($callLog->call_id, $response->json('data.call_id'));
    }

    public function test_non_admin_cannot_access_admin_call_logs()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/admin/call-logs');

        $response->assertStatus(403);
    }

    public function test_admin_can_search_call_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $assistant = Assistant::factory()->create(['user_id' => $user->id]);

        $callLog1 = CallLog::factory()->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'call_id' => 'call_123_test',
            'status' => 'completed'
        ]);

        $callLog2 = CallLog::factory()->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'call_id' => 'call_456_other',
            'status' => 'completed'
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/call-logs?search=test');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('call_123_test', $response->json('data.0.call_id'));
    }
} 