<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssistantPaginationNavigationTest extends TestCase
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

    public function test_pagination_navigation_works_correctly()
    {
        // Create 25 assistants (3 pages with 9 per page)
        Assistant::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id
        ]);

        // Test first page
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=1&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['pagination']['current_page']);
        $this->assertEquals(9, count($data['data']));
        $this->assertEquals(25, $data['pagination']['total']);
        $this->assertEquals(3, $data['pagination']['last_page']);

        // Test second page
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=2&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertEquals(9, count($data['data']));

        // Test third page
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=3&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(3, $data['pagination']['current_page']);
        $this->assertEquals(7, count($data['data'])); // Last page should have 7 items (25 total - 18 from first 2 pages)

        // Test invalid page (should return empty results)
        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=10&per_page=9');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, count($data['data']));
    }

    public function test_pagination_with_search()
    {
        // Create assistants with different names
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
            'name' => 'Another Assistant'
        ]);

        // Search for "Test" - should find 2 assistants
        $response = $this->actingAs($this->user)->getJson('/api/assistants?search=Test&page=1&per_page=5');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['total']);
        $this->assertEquals(2, count($data['data']));
    }

    public function test_pagination_with_phone_search()
    {
        // Create assistants with different phone numbers
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
            'phone_number' => '+0987654321'
        ]);

        // Search for "123" - should find 2 assistants
        $response = $this->actingAs($this->user)->getJson('/api/assistants?phone_search=123&page=1&per_page=5');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['total']);
        $this->assertEquals(2, count($data['data']));
    }

    public function test_pagination_with_sorting()
    {
        // Create assistants with different names
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'name' => 'Zebra Assistant'
        ]);
        
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'name' => 'Alpha Assistant'
        ]);

        // Sort by name ascending
        $response = $this->actingAs($this->user)->getJson('/api/assistants?sort_by=name&sort_order=asc&page=1&per_page=10');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Alpha Assistant', $data['data'][0]['name']);
        $this->assertEquals('Zebra Assistant', $data['data'][1]['name']);

        // Sort by name descending
        $response = $this->actingAs($this->user)->getJson('/api/assistants?sort_by=name&sort_order=desc&page=1&per_page=10');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Zebra Assistant', $data['data'][0]['name']);
        $this->assertEquals('Alpha Assistant', $data['data'][1]['name']);
    }
} 