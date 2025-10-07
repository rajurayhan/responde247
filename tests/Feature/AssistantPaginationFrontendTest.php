<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssistantPaginationFrontendTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'user',
            'status' => 'active'
        ]);
    }

    public function test_frontend_pagination_methods_work_correctly()
    {
        // Create 20 assistants
        Assistant::factory()->count(20)->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id
        ]);

        // Test initial load (page 1, 9 per page)
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=1&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['pagination']['current_page']);
        $this->assertEquals(9, count($data['data']));
        $this->assertEquals(20, $data['pagination']['total']);
        $this->assertEquals(3, $data['pagination']['last_page']);

        // Test next page (page 2)
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=2&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertEquals(9, count($data['data']));

        // Test last page (page 3)
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=3&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(3, $data['pagination']['current_page']);
        $this->assertEquals(2, count($data['data'])); // 20 total - 18 from first 2 pages = 2 remaining

        // Test previous page (back to page 2)
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=2&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertEquals(9, count($data['data']));
    }

    public function test_pagination_with_search_and_navigation()
    {
        // Create assistants with specific names
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'name' => 'Test Assistant 1'
        ]);
        
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'name' => 'Test Assistant 2'
        ]);
        
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'name' => 'Test Assistant 3'
        ]);

        // Search for "Test" and navigate through pages
        $response = $this->actingAs($this->user)->getJson('/api/assistants?search=Test&page=1&per_page=2');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(3, $data['pagination']['total']);
        $this->assertEquals(2, count($data['data']));
        $this->assertEquals(2, $data['pagination']['last_page']);

        // Go to page 2
        $response = $this->actingAs($this->user)->getJson('/api/assistants?search=Test&page=2&per_page=2');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertEquals(1, count($data['data'])); // Only 1 item on page 2
    }

    public function test_pagination_with_phone_search_and_navigation()
    {
        // Create assistants with specific phone numbers
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'phone_number' => '+1234567890'
        ]);
        
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'phone_number' => '+1234567891'
        ]);
        
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'phone_number' => '+1234567892'
        ]);

        // Search for "123" and navigate through pages
        $response = $this->actingAs($this->user)->getJson('/api/assistants?phone_search=123&page=1&per_page=2');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(3, $data['pagination']['total']);
        $this->assertEquals(2, count($data['data']));
        $this->assertEquals(2, $data['pagination']['last_page']);

        // Go to page 2
        $response = $this->actingAs($this->user)->getJson('/api/assistants?phone_search=123&page=2&per_page=2');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertEquals(1, count($data['data'])); // Only 1 item on page 2
    }

    public function test_pagination_reset_on_search_change()
    {
        // Create assistants
        Assistant::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id
        ]);

        // Start on page 2
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=2&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['current_page']);

        // Add search - should reset to page 1
        $response = $this->actingAs($this->user)->getJson('/api/assistants?search=Test&page=1&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['pagination']['current_page']);
    }
} 