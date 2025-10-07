<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use App\Models\CallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CallLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_call_logs()
    {
        $user = User::factory()->create();
        $assistant = Assistant::factory()->create(['user_id' => $user->id]);
        
        // Create some call logs
        CallLog::factory()->count(5)->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/call-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta'
            ]);
    }

    public function test_user_can_filter_call_logs_by_assistant()
    {
        $user = User::factory()->create();
        $assistant1 = Assistant::factory()->create(['user_id' => $user->id]);
        $assistant2 = Assistant::factory()->create(['user_id' => $user->id]);
        
        // Create call logs for both assistants
        CallLog::factory()->count(3)->create([
            'assistant_id' => $assistant1->id,
            'user_id' => $user->id,
        ]);
        
        CallLog::factory()->count(2)->create([
            'assistant_id' => $assistant2->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/call-logs?assistant_id=' . $assistant1->id);

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        // Verify all call logs belong to the specified assistant
        foreach ($data as $callLog) {
            $this->assertEquals($assistant1->id, $callLog['assistant_id']);
        }
    }

    public function test_user_can_filter_call_logs_by_status()
    {
        $user = User::factory()->create();
        $assistant = Assistant::factory()->create(['user_id' => $user->id]);
        
        // Create call logs with different statuses
        CallLog::factory()->count(3)->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        
        CallLog::factory()->count(2)->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/call-logs?status=completed');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        // Verify all call logs have completed status
        foreach ($data as $callLog) {
            $this->assertEquals('completed', $callLog['status']);
        }
    }

    public function test_user_can_get_call_logs_statistics()
    {
        $user = User::factory()->create();
        $assistant = Assistant::factory()->create(['user_id' => $user->id]);
        
        // Create call logs with different statuses
        CallLog::factory()->count(5)->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        
        CallLog::factory()->count(2)->create([
            'assistant_id' => $assistant->id,
            'user_id' => $user->id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/call-logs/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalCalls',
                    'completedCalls',
                    'failedCalls',
                    'inboundCalls',
                    'outboundCalls',
                    'totalCost',
                    'averageDuration',
                    'statusBreakdown',
                    'assistantPerformance'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(7, $data['totalCalls']);
        $this->assertEquals(5, $data['completedCalls']);
        $this->assertEquals(2, $data['failedCalls']);
    }

    public function test_user_cannot_access_other_users_call_logs()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $assistant1 = Assistant::factory()->create(['user_id' => $user1->id]);
        $assistant2 = Assistant::factory()->create(['user_id' => $user2->id]);
        
        // Create call logs for both users
        CallLog::factory()->count(3)->create([
            'assistant_id' => $assistant1->id,
            'user_id' => $user1->id,
        ]);
        
        CallLog::factory()->count(2)->create([
            'assistant_id' => $assistant2->id,
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/call-logs');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        // Verify user1 only sees their own call logs
        foreach ($data as $callLog) {
            $this->assertEquals($user1->id, $callLog['user_id']);
        }
    }
} 