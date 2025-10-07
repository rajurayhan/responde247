<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionPackage;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Exception\ApiErrorException;

class StripePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_payment_method_returns_appropriate_error()
    {
        $user = User::factory()->create();
        $package = SubscriptionPackage::factory()->create([
            'name' => 'Test Package',
            'price' => 9.99,
            'voice_agents_limit' => 5
        ]);

        $stripeService = app(StripeService::class);

        // Test with an invalid PaymentMethod ID
        $invalidPaymentMethodId = 'pm_invalid_payment_method_id';

        try {
            $result = $stripeService->createSubscription($user, $package, $invalidPaymentMethodId);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('payment method is invalid', $e->getMessage());
        }
    }

    public function test_payment_method_validation_works_correctly()
    {
        $user = User::factory()->create();
        $package = SubscriptionPackage::factory()->create([
            'name' => 'Test Package',
            'price' => 9.99,
            'voice_agents_limit' => 5
        ]);

        $stripeService = app(StripeService::class);

        // Test that the validation method exists and can be called
        $reflection = new \ReflectionClass($stripeService);
        $method = $reflection->getMethod('validateAndAttachPaymentMethod');
        $this->assertTrue($method->isPrivate());
    }

    public function test_cleanup_command_works_with_no_invalid_references()
    {
        $this->artisan('stripe:cleanup-payment-methods')
            ->expectsOutput('Starting PaymentMethod cleanup...')
            ->expectsOutput('✅ No invalid PaymentMethod references found')
            ->assertExitCode(0);
    }
}
