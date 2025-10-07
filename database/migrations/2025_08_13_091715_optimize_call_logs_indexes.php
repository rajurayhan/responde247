<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            // Only add indexes that don't already exist
            // Most indexes are already there from the original migration
            
            // Add cost index for admin analysis (if it doesn't exist)
            try {
                $table->index(['cost', 'start_time'], 'idx_cost_time');
            } catch (\Exception $e) {
                Log::info('Cost index already exists or could not be created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            // Drop indexes we added
            try {
                $table->dropIndex('idx_cost_time');
            } catch (\Exception $e) {
                Log::info('Could not drop cost index');
            }
        });
    }
};
