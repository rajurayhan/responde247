<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Assistant;
use App\Models\User;

class AssistantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => 'user',
                'status' => 'active'
            ]
        );

        // Create some test assistants
        Assistant::firstOrCreate(
            ['vapi_assistant_id' => 'test-assistant-1'],
            [
                'name' => 'Customer Service Assistant',
                'user_id' => $user->id,
                'created_by' => $user->id,
            ]
        );

        Assistant::firstOrCreate(
            ['vapi_assistant_id' => 'test-assistant-2'],
            [
                'name' => 'Sales Assistant',
                'user_id' => $user->id,
                'created_by' => $user->id,
            ]
        );

        // Create an admin user if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'status' => 'active'
            ]
        );

        // Create some assistants for admin
        Assistant::firstOrCreate(
            ['vapi_assistant_id' => 'admin-assistant-1'],
            [
                'name' => 'Admin Support Assistant',
                'user_id' => $admin->id,
                'created_by' => $admin->id,
            ]
        );
    }
}
