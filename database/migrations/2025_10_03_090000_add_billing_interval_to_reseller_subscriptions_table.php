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
        Schema::table('reseller_subscriptions', function (Blueprint $table) {
            $table->string('billing_interval')->default('monthly')->after('custom_amount');
            $table->index(['billing_interval', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['billing_interval', 'status']);
            $table->dropColumn('billing_interval');
        });
    }
};
