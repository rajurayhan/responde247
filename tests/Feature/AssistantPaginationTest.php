<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssistantPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'user',
            'status' => 'active'
        ]);
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);
    }

    public function test_user_can_get_paginated_assistants()
    {
        // Create 15 assistants for the user
        Assistant::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=1&per_page=9');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to'
                    ]
                ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(9, $data['data']); // First page should have 9 items
        $this->assertEquals(1, $data['pagination']['current_page']);
        $this->assertEquals(2, $data['pagination']['last_page']); // 15 items / 9 per page = 2 pages
        $this->assertEquals(15, $data['pagination']['total']);
    }

    public function test_user_can_search_assistants_by_name()
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
            'name' => 'Another Assistant'
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/assistants?search=Test');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Assistant 1', $data['data'][0]['name']);
    }

    public function test_user_can_search_assistants_by_phone_number()
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
            'phone_number' => '+0987654321'
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/assistants?phone_search=123');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('+1234567890', $data['data'][0]['phone_number']);
    }

    public function test_admin_can_get_paginated_assistants()
    {
        // Create assistants for different users
        Assistant::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id
        ]);
        
        Assistant::factory()->count(5)->create([
            'user_id' => $this->admin->id,
            'created_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/assistants?page=1&per_page=5');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(5, $data['data']);
        $this->assertEquals(10, $data['pagination']['total']);
        $this->assertEquals(2, $data['pagination']['last_page']);
    }

    public function test_admin_can_search_assistants_by_phone_number()
    {
        // Create assistants with different phone numbers
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'phone_number' => '+1234567890'
        ]);
        
        Assistant::factory()->create([
            'user_id' => $this->admin->id,
            'created_by' => $this->admin->id,
            'phone_number' => '+0987654321'
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/assistants?phone_search=098');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('+0987654321', $data['data'][0]['phone_number']);
    }

    public function test_admin_can_search_assistants_by_user()
    {
        // Create assistants for different users
        Assistant::factory()->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id,
            'name' => 'User Assistant'
        ]);
        
        Assistant::factory()->create([
            'user_id' => $this->admin->id,
            'created_by' => $this->admin->id,
            'name' => 'Admin Assistant'
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/assistants?user_search=' . $this->user->name);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('User Assistant', $data['data'][0]['name']);
    }

    public function test_pagination_respects_per_page_parameter()
    {
        // Create 20 assistants
        Assistant::factory()->count(20)->create([
            'user_id' => $this->user->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/assistants?page=1&per_page=5');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(5, $data['data']);
        $this->assertEquals(5, $data['pagination']['per_page']);
        $this->assertEquals(20, $data['pagination']['total']);
        $this->assertEquals(4, $data['pagination']['last_page']);
    }

    public function test_sorting_works_with_pagination()
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

        $response = $this->actingAs($this->user)->getJson('/api/assistants?sort_by=name&sort_order=asc&page=1&per_page=10');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Alpha Assistant', $data['data'][0]['name']);
        $this->assertEquals('Zebra Assistant', $data['data'][1]['name']);
    }
} 
 