<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionPackage;
use App\Models\UserSubscription;
use App\Services\VapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SubscriptionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPackage $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a subscription package
        $this->package = SubscriptionPackage::create([
            'name' => 'Basic Plan',
            'description' => 'Basic plan for testing',
            'price' => 29.99,
            'voice_agents_limit' => 2,
            'monthly_minutes_limit' => 1000,
            'features' => 'Basic features',
            'support_level' => 'email',
            'analytics_level' => 'basic',
            'is_popular' => false,
            'is_active' => true,
        ]);
    }

    public function test_user_without_subscription_cannot_create_assistant()
    {
        $user = User::factory()->create(['role' => 'user']);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant'
            ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
        ]);
    }

    public function test_user_with_pending_subscription_cannot_create_assistant()
    {
        $user = User::factory()->create(['role' => 'user']);

        // Create a pending subscription for the user
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $this->package->id,
            'status' => 'pending',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant'
            ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
        ]);
    }

    public function test_user_with_active_subscription_can_create_assistant()
    {
        $user = User::factory()->create(['role' => 'user']);

        // Create an active subscription for the user
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $this->package->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o'
                ],
                'voice' => [
                    'provider' => 'vapi',
                    'voiceId' => 'elliot'
                ]
            ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'user_id' => $user->id
        ]);
    }

    public function test_user_with_expired_subscription_cannot_create_assistant()
    {
        $user = User::factory()->create(['role' => 'user']);

        // Create an expired subscription for the user
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $this->package->id,
            'status' => 'expired',
            'current_period_start' => Carbon::now()->subMonths(2),
            'current_period_end' => Carbon::now()->subMonth(),
        ]);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant'
            ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
        ]);
    }

    public function test_user_with_cancelled_subscription_cannot_create_assistant()
    {
        $user = User::factory()->create(['role' => 'user']);

        // Create a cancelled subscription for the user
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $this->package->id,
            'status' => 'cancelled',
            'current_period_start' => Carbon::now()->subMonth(),
            'current_period_end' => Carbon::now()->addDays(15),
            'cancelled_at' => Carbon::now()->subDays(5),
        ]);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant'
            ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
        ]);
    }

    public function test_user_reaches_assistant_limit_cannot_create_more()
    {
        $user = User::factory()->create(['role' => 'user']);

        // Create an active subscription for the user
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $this->package->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Create 2 assistants (reaching the limit)
        $user->assistants()->createMany([
            [
                'name' => 'Assistant 1',
                'vapi_assistant_id' => 'assistant_1',
                'created_by' => $user->id,
                'type' => 'demo'
            ],
            [
                'name' => 'Assistant 2',
                'vapi_assistant_id' => 'assistant_2',
                'created_by' => $user->id,
                'type' => 'demo'
            ]
        ]);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant'
            ]);
        });

        $response = $this->actingAs($user)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You have reached your assistant limit for your current subscription plan. Please upgrade your plan to create more assistants.'
        ]);
    }

    public function test_admin_can_create_assistant_regardless_of_subscription()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o'
                ],
                'voice' => [
                    'provider' => 'vapi',
                    'voiceId' => 'elliot'
                ]
            ]);
        });

        $response = $this->actingAs($admin)->postJson('/api/assistants', [
            'name' => 'Test Assistant',
            'type' => 'demo',
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ]
                ]
            ],
            'voice' => [
                'provider' => 'vapi',
                'voiceId' => 'elliot'
            ],
            'firstMessage' => 'Hello, how can I help you?',
            'endCallMessage' => 'Thank you for calling.',
            'metadata' => [
                'company_name' => 'Test Company',
                'industry' => 'Technology',
                'services_products' => 'Software Development',
                'country' => 'United States'
            ]
        ]);

        $response->assertStatus(201);
        
        // Verify the assistant was created
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'user_id' => $admin->id,
            'created_by' => $admin->id
        ]);
    }
} 