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
        Schema::table('resellers', function (Blueprint $table) {
            // Add composite index for domain + status lookup (most common query)
            $table->index(['domain', 'status'], 'idx_resellers_domain_status');
        });

        Schema::table('reseller_settings', function (Blueprint $table) {
            // Add index for reseller_id + group (common query pattern)
            $table->index(['reseller_id', 'group'], 'idx_reseller_settings_reseller_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropIndex('idx_resellers_domain_status');
        });

        Schema::table('reseller_settings', function (Blueprint $table) {
            $table->dropIndex('idx_reseller_settings_reseller_group');
        });
    }
};