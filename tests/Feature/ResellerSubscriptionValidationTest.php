<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Reseller;
use App\Models\ResellerSubscription;
use App\Models\ResellerPackage;
use App\Models\UserSubscription;
use App\Models\SubscriptionPackage;
use App\Services\VapiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResellerSubscriptionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $reseller;
    protected $resellerPackage;
    protected $userPackage;
    protected $resellerAdmin;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        

        // Create reseller subscription package
        $this->resellerPackage = ResellerPackage::create([
            'name' => 'Reseller Pro',
            'description' => 'Professional reseller package',
            'price' => 99.99,
            'voice_agents_limit' => 100,
            'monthly_minutes_limit' => 10000,
            'features' => 'Advanced features',
            'support_level' => 'premium',
            'analytics_level' => 'advanced',
            'is_active' => true,
        ]);

        // Create user subscription package
        $this->userPackage = SubscriptionPackage::create([
            'name' => 'User Pro',
            'description' => 'Professional user package',
            'price' => 29.99,
            'voice_agents_limit' => 5,
            'monthly_minutes_limit' => 1000,
            'features' => 'Basic features',
            'support_level' => 'standard',
            'analytics_level' => 'basic',
            'is_active' => true,
        ]);

        // Create reseller
        $this->reseller = Reseller::create([
            'org_name' => 'Test Reseller',
            'company_email' => 'reseller@test.com',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        // Create reseller admin user
        $this->resellerAdmin = User::create([
            'name' => 'Reseller Admin',
            'email' => 'admin@test-reseller.com',
            'password' => bcrypt('password'),
            'role' => 'reseller_admin',
            'reseller_id' => $this->reseller->id,
            'status' => 'active',
        ]);

        // Create regular user under reseller
        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@test-reseller.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'reseller_id' => $this->reseller->id,
            'status' => 'active',
        ]);
    }

    public function test_reseller_without_subscription_cannot_create_assistant()
    {
        // Create active user subscription
        UserSubscription::create([
            'user_id' => $this->regularUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
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

        $response = $this->actingAs($this->regularUser)->postJson('/api/assistants', [
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
            'message' => 'Your reseller does not have an active subscription. Please contact your reseller administrator.'
        ]);
    }

    public function test_reseller_with_inactive_status_cannot_create_assistant()
    {
        // Create active reseller subscription
        ResellerSubscription::create([
            'reseller_id' => $this->reseller->id,
            'reseller_package_id' => $this->resellerPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Create active user subscription
        UserSubscription::create([
            'user_id' => $this->regularUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Make reseller inactive
        $this->reseller->update(['status' => 'inactive']);

        // Mock VapiService
        $this->mock(VapiService::class, function ($mock) {
            $mock->shouldReceive('createAssistant')->andReturn([
                'id' => 'assistant_test_123',
                'name' => 'Test Assistant'
            ]);
        });

        $response = $this->actingAs($this->regularUser)->postJson('/api/assistants', [
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
            'message' => 'Your reseller account is inactive. Please contact your reseller administrator.'
        ]);
    }

    public function test_user_without_subscription_cannot_create_assistant_even_with_active_reseller()
    {
        // Create active reseller subscription
        ResellerSubscription::create([
            'reseller_id' => $this->reseller->id,
            'reseller_package_id' => $this->resellerPackage->id,
            'status' => 'active',
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

        $response = $this->actingAs($this->regularUser)->postJson('/api/assistants', [
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

    public function test_user_can_create_assistant_with_active_reseller_and_user_subscriptions()
    {
        // Create active reseller subscription
        ResellerSubscription::create([
            'reseller_id' => $this->reseller->id,
            'reseller_package_id' => $this->resellerPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Create active user subscription
        UserSubscription::create([
            'user_id' => $this->regularUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
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

        $response = $this->actingAs($this->regularUser)->postJson('/api/assistants', [
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
        $response->assertJson([
            'success' => true,
            'message' => 'Assistant created successfully'
        ]);

        // Verify the assistant was created
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'user_id' => $this->regularUser->id,
            'reseller_id' => $this->reseller->id
        ]);
    }

    public function test_reseller_admin_can_create_assistant_with_active_reseller_subscription()
    {
        // Create active reseller subscription
        ResellerSubscription::create([
            'reseller_id' => $this->reseller->id,
            'reseller_package_id' => $this->resellerPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Create active user subscription for reseller admin
        UserSubscription::create([
            'user_id' => $this->resellerAdmin->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
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

        $response = $this->actingAs($this->resellerAdmin)->postJson('/api/assistants', [
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
        $response->assertJson([
            'success' => true,
            'message' => 'Assistant created successfully'
        ]);

        // Verify the assistant was created
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'user_id' => $this->resellerAdmin->id,
            'reseller_id' => $this->reseller->id
        ]);
    }

    public function test_user_without_reseller_can_create_assistant_with_active_subscription()
    {
        // Create user without reseller
        $standaloneUser = User::create([
            'name' => 'Standalone User',
            'email' => 'standalone@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'reseller_id' => null,
            'status' => 'active',
        ]);

        // Create active user subscription
        UserSubscription::create([
            'user_id' => $standaloneUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
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

        $response = $this->actingAs($standaloneUser)->postJson('/api/assistants', [
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
        $response->assertJson([
            'success' => true,
            'message' => 'Assistant created successfully'
        ]);

        // Verify the assistant was created
        $this->assertDatabaseHas('assistants', [
            'name' => 'Test Assistant',
            'user_id' => $standaloneUser->id,
            'reseller_id' => null
        ]);
    }

    public function test_user_model_can_create_assistant_with_reseller_method()
    {
        // Create active reseller subscription
        ResellerSubscription::create([
            'reseller_id' => $this->reseller->id,
            'reseller_package_id' => $this->resellerPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Create active user subscription
        UserSubscription::create([
            'user_id' => $this->regularUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Test the method directly
        $this->assertTrue($this->regularUser->canCreateAssistantWithReseller());
        $this->assertTrue($this->regularUser->canCreateAssistant());
    }

    public function test_user_model_cannot_create_assistant_without_reseller_subscription()
    {
        // Create active user subscription
        UserSubscription::create([
            'user_id' => $this->regularUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Test the method directly
        $this->assertFalse($this->regularUser->canCreateAssistantWithReseller());
        $this->assertFalse($this->regularUser->canCreateAssistant());
    }

    public function test_user_model_validation_message_for_reseller_subscription()
    {
        // Create active user subscription
        UserSubscription::create([
            'user_id' => $this->regularUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        $message = $this->regularUser->getAssistantCreationValidationMessage();
        $this->assertEquals('Your reseller does not have an active subscription. Please contact your reseller administrator.', $message);
    }

    public function test_user_model_validation_message_for_inactive_reseller()
    {
        // Create active user subscription
        UserSubscription::create([
            'user_id' => $this->regularUser->id,
            'subscription_package_id' => $this->userPackage->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Make reseller inactive
        $this->reseller->update(['status' => 'inactive']);

        $message = $this->regularUser->getAssistantCreationValidationMessage();
        $this->assertEquals('Your reseller account is inactive. Please contact your reseller administrator.', $message);
    }
}
