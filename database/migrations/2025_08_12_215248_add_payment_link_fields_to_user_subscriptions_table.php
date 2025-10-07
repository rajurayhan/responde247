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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('payment_link_id')->nullable()->after('stripe_customer_id');
            $table->text('payment_link_url')->nullable()->after('payment_link_id');
            $table->decimal('custom_amount', 10, 2)->nullable()->after('payment_link_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_link_id', 'payment_link_url', 'custom_amount']);
        });
    }
};
