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
        // Drop the existing unique constraint on key
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
        });
        
        // Add a new unique constraint on reseller_id and key combination
        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['reseller_id', 'key'], 'settings_reseller_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the composite unique constraint
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_reseller_key_unique');
        });
        
        // Restore the original unique constraint on key only
        Schema::table('settings', function (Blueprint $table) {
            $table->unique('key');
        });
    }
};