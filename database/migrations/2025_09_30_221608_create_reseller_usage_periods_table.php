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
        Schema::create('reseller_usage_periods', function (Blueprint $table) {
            $table->id();
            $table->char('reseller_id', 36);
            $table->unsignedBigInteger('reseller_subscription_id');
            $table->unsignedBigInteger('reseller_package_id');
            
            // Period tracking
            $table->datetime('period_start');
            $table->datetime('period_end');
            
            // Usage metrics
            $table->integer('total_calls')->default(0);
            $table->integer('total_duration_seconds')->default(0);
            $table->decimal('total_cost', 10, 4)->default(0.0000);
            
            // Package limits
            $table->decimal('monthly_cost_limit', 10, 4)->default(0.0000);
            $table->integer('monthly_minutes_limit')->default(-1);
            $table->decimal('extra_per_minute_charge', 8, 4)->default(0.0000);
            
            // Overage tracking
            $table->decimal('overage_cost', 10, 4)->default(0.0000);
            $table->integer('overage_minutes')->default(0);
            $table->enum('overage_status', ['none', 'pending', 'billed', 'carried_forward'])->default('none');
            
            // Billing
            $table->datetime('overage_billed_at')->nullable();
            $table->unsignedBigInteger('overage_transaction_id')->nullable();
            $table->decimal('carried_forward_amount', 10, 4)->default(0.0000);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['reseller_id', 'period_start', 'period_end'], 'idx_reseller_period');
            $table->index('reseller_subscription_id', 'idx_subscription');
            $table->index('overage_status', 'idx_overage_status');
            
            // Foreign keys
            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            $table->foreign('reseller_subscription_id')->references('id')->on('reseller_subscriptions')->onDelete('cascade');
            $table->foreign('reseller_package_id')->references('id')->on('reseller_packages');
            $table->foreign('overage_transaction_id')->references('id')->on('reseller_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_usage_periods');
    }
};
