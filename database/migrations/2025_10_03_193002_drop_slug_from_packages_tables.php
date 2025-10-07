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
        // Drop slug column from subscription_packages table
        if (Schema::hasColumn('subscription_packages', 'slug')) {
            Schema::table('subscription_packages', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }

        // Drop slug column from reseller_packages table
        if (Schema::hasColumn('reseller_packages', 'slug')) {
            Schema::table('reseller_packages', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add slug column back to subscription_packages table
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
        });

        // Add slug column back to reseller_packages table
        Schema::table('reseller_packages', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
            $table->index('slug');
        });
    }
};