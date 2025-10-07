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
        // Add usage tracking columns to reseller_transactions table
        $this->safeAddColumn('reseller_transactions', 'usage_period_id', function (Blueprint $table) {
            $table->unsignedBigInteger('usage_period_id')->nullable()->after('reseller_subscription_id');
        });

        $this->safeAddColumn('reseller_transactions', 'overage_details', function (Blueprint $table) {
            $table->json('overage_details')->nullable()->after('metadata');
        });

        // Add foreign key constraint if usage_period_id column was added and table exists
        if (Schema::hasColumn('reseller_transactions', 'usage_period_id') && Schema::hasTable('reseller_usage_periods')) {
            Schema::table('reseller_transactions', function (Blueprint $table) {
                try {
                    $table->foreign('usage_period_id')
                        ->references('id')
                        ->on('reseller_usage_periods')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist, ignore
                    \Log::info('Foreign key constraint already exists or failed to add: ' . $e->getMessage());
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key first if it exists
        if (Schema::hasColumn('reseller_transactions', 'usage_period_id')) {
            Schema::table('reseller_transactions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['usage_period_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore
                    \Log::info('Foreign key does not exist or failed to drop: ' . $e->getMessage());
                }
            });
        }

        $this->safeDropColumn('reseller_transactions', 'overage_details');
        $this->safeDropColumn('reseller_transactions', 'usage_period_id');
    }
};
