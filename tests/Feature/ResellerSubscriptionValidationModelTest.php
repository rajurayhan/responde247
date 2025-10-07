<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Reseller;
use App\Models\ResellerSubscription;
use App\Models\ResellerPackage;
use App\Models\UserSubscription;
use App\Models\SubscriptionPackage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResellerSubscriptionValidationModelTest extends TestCase
{
    use RefreshDatabase;

    protected $reseller;
    protected $resellerPackage;
    protected $userPackage;
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

    public function test_user_cannot_create_assistant_without_reseller_subscription()
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

    public function test_user_cannot_create_assistant_with_inactive_reseller()
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

        // Test the method directly
        $this->assertFalse($this->regularUser->canCreateAssistantWithReseller());
        $this->assertFalse($this->regularUser->canCreateAssistant());
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

        // Test the method directly
        $this->assertTrue($this->regularUser->canCreateAssistantWithReseller());
        $this->assertTrue($this->regularUser->canCreateAssistant());
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

        // Test the method directly
        $this->assertTrue($standaloneUser->canCreateAssistantWithReseller());
        $this->assertTrue($standaloneUser->canCreateAssistant());
    }

    public function test_validation_message_for_reseller_without_subscription()
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

    public function test_validation_message_for_inactive_reseller()
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

    public function test_validation_message_for_user_without_subscription()
    {
        $message = $this->regularUser->getAssistantCreationValidationMessage();
        $this->assertEquals('You need an active subscription to create assistants. Please subscribe to a plan to get started.', $message);
    }

    public function test_validation_message_for_user_with_active_subscriptions()
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

        $message = $this->regularUser->getAssistantCreationValidationMessage();
        $this->assertEquals('You can create assistants.', $message);
    }
}
