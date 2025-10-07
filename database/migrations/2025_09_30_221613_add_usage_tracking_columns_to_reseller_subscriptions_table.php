<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\SafeMigrationTrait;

return new class extends Migration
{
    use SafeMigrationTrait;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add usage tracking columns to reseller_subscriptions table
        $this->safeAddColumn('reseller_subscriptions', 'current_period_usage_cost', function (Blueprint $table) {
            $table->decimal('current_period_usage_cost', 10, 4)->default(0.0000)->after('metadata');
        });

        $this->safeAddColumn('reseller_subscriptions', 'current_period_calls', function (Blueprint $table) {
            $table->integer('current_period_calls')->default(0)->after('current_period_usage_cost');
        });

        $this->safeAddColumn('reseller_subscriptions', 'current_period_duration', function (Blueprint $table) {
            $table->integer('current_period_duration')->default(0)->after('current_period_calls');
        });

        $this->safeAddColumn('reseller_subscriptions', 'pending_overage_cost', function (Blueprint $table) {
            $table->decimal('pending_overage_cost', 10, 4)->default(0.0000)->after('current_period_duration');
        });

        $this->safeAddColumn('reseller_subscriptions', 'last_usage_calculated_at', function (Blueprint $table) {
            $table->datetime('last_usage_calculated_at')->nullable()->after('pending_overage_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->safeDropColumn('reseller_subscriptions', 'last_usage_calculated_at');
        $this->safeDropColumn('reseller_subscriptions', 'pending_overage_cost');
        $this->safeDropColumn('reseller_subscriptions', 'current_period_duration');
        $this->safeDropColumn('reseller_subscriptions', 'current_period_calls');
        $this->safeDropColumn('reseller_subscriptions', 'current_period_usage_cost');
    }
};
