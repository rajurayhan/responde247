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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_package_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_subscription_id')->nullable()->constrained()->onDelete('cascade');
            
            // Transaction details
            $table->string('transaction_id')->unique(); // Unique transaction identifier
            $table->string('external_transaction_id')->nullable(); // Stripe/PayPal transaction ID
            $table->decimal('amount', 10, 2); // Transaction amount
            $table->string('currency', 3)->default('USD');
            $table->string('status'); // pending, completed, failed, refunded, cancelled
            
            // Payment method
            $table->string('payment_method')->nullable(); // stripe, paypal, etc.
            $table->string('payment_method_id')->nullable(); // Payment method identifier
            $table->json('payment_details')->nullable(); // Additional payment details
            
            // Billing information
            $table->string('billing_email');
            $table->string('billing_name')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code')->nullable();
            
            // Transaction metadata
            $table->string('type'); // subscription, upgrade, renewal, refund, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional transaction data
            
            // Timestamps
            $table->timestamp('processed_at')->nullable(); // When transaction was processed
            $table->timestamp('failed_at')->nullable(); // When transaction failed
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('transaction_id');
            $table->index('external_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
