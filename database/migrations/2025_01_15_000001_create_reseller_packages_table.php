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
        Schema::create('reseller_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Monthly price
            $table->decimal('yearly_price', 10, 2)->nullable(); // Yearly price
            $table->integer('voice_agents_limit')->default(-1); // -1 for unlimited
            $table->integer('monthly_minutes_limit')->default(-1); // -1 for unlimited
            $table->decimal('extra_per_minute_charge', 6, 4)->default(0.0000);
            $table->json('features')->nullable(); // Array of features
            $table->string('support_level')->default('standard'); // standard, priority, enterprise
            $table->string('analytics_level')->default('basic'); // basic, advanced, enterprise
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'is_popular']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_packages');
    }
};
