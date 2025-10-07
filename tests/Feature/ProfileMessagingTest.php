<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileMessagingTest extends TestCase
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
        
        Storage::fake('public');
    }

    public function test_profile_update_shows_success_message()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user', [
            'name' => 'Updated Name',
            'phone' => '+1234567890',
            'company' => 'Updated Company',
            'bio' => 'Updated bio information'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'phone' => '+1234567890',
            'company' => 'Updated Company',
            'bio' => 'Updated bio information'
        ]);
    }

    public function test_profile_update_shows_validation_errors()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user', [
            'phone' => '+1234567890',
            'company' => 'Updated Company'
            // Missing required 'name' field
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    public function test_profile_update_with_invalid_file_shows_error()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)->postJson('/api/user', [
            'name' => 'Updated Name',
            'profile_picture' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['profile_picture']);
    }

    public function test_password_change_shows_success_message()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password' => 'password', // Default password from factory
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
    }

    public function test_password_change_shows_validation_errors()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password' => 'password',
            'new_password' => 'short',
            'new_password_confirmation' => 'different'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['new_password']);
    }

    public function test_password_change_with_incorrect_current_password_shows_error()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ]);
    }

    public function test_profile_update_with_picture_shows_success_message()
    {
        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->actingAs($this->user)->postJson('/api/user', [
            'name' => 'Updated Name',
            'phone' => '+1234567890',
            'company' => 'Updated Company',
            'bio' => 'Updated bio information',
            'profile_picture' => $file
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        // Check that the file was stored
        $this->user->refresh();
        $this->assertNotNull($this->user->profile_picture);
        $this->assertTrue(Storage::disk('public')->exists(str_replace('/storage/', '', $this->user->profile_picture)));
    }

    public function test_profile_update_with_large_file_shows_error()
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB

        $response = $this->actingAs($this->user)->postJson('/api/user', [
            'name' => 'Updated Name',
            'profile_picture' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['profile_picture']);
    }

    public function test_profile_update_with_long_fields_shows_validation_errors()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user', [
            'name' => str_repeat('a', 300), // Too long
            'phone' => str_repeat('1', 25), // Too long
            'company' => str_repeat('b', 300), // Too long
            'bio' => str_repeat('c', 1100) // Too long
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'phone', 'company', 'bio']);
    }

    public function test_user_can_get_profile_without_errors()
    {
        $response = $this->actingAs($this->user)->getJson('/api/user');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email
                    ]
                ]);
    }
} 