<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reseller_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('reseller_id');
            $table->foreignId('reseller_package_id')->constrained()->onDelete('cascade');
            $table->string('status'); // active, cancelled, expired, trial
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('stripe_subscription_id')->nullable(); // For Stripe integration
            $table->string('stripe_customer_id')->nullable(); // For Stripe integration
            $table->string('payment_link_id')->nullable();
            $table->string('payment_link_url')->nullable();
            $table->decimal('custom_amount', 10, 2)->nullable(); // For custom pricing
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('reseller_id')
                  ->references('id')
                  ->on('resellers')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index(['reseller_id', 'status']);
            $table->index(['status', 'current_period_end']);
            $table->index('stripe_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_subscriptions');
    }
};
