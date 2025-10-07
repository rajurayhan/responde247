<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileUpdateTest extends TestCase
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

    public function test_user_can_update_profile()
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

    public function test_user_can_update_profile_with_picture()
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

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'phone' => '+1234567890',
            'company' => 'Updated Company',
            'bio' => 'Updated bio information'
        ]);

        // Check that the file was stored
        $this->user->refresh();
        $this->assertNotNull($this->user->profile_picture);
        $this->assertTrue(Storage::disk('public')->exists(str_replace('/storage/', '', $this->user->profile_picture)));
    }

    public function test_user_can_change_password()
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

        // Verify password was actually changed
        $this->user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $this->user->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password()
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

    public function test_user_cannot_change_password_with_mismatched_confirmation()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'differentpassword'
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_change_password_with_short_password()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password' => 'password',
            'new_password' => 'short',
            'new_password_confirmation' => 'short'
        ]);

        $response->assertStatus(422);
    }

    public function test_profile_update_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->putJson('/api/user', [
            'phone' => '+1234567890',
            'company' => 'Updated Company'
            // Missing required 'name' field
        ]);

        $response->assertStatus(422);
    }

    public function test_profile_update_validates_file_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)->postJson('/api/user', [
            'name' => 'Updated Name',
            'profile_picture' => $file
        ]);

        $response->assertStatus(422);
    }

    public function test_profile_update_validates_file_size()
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB

        $response = $this->actingAs($this->user)->postJson('/api/user', [
            'name' => 'Updated Name',
            'profile_picture' => $file
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_get_profile()
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